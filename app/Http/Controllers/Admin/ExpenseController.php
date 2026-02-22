<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $method = trim((string) $request->query('method', ''));
        $dateFrom = trim((string) $request->query('date_from', now()->startOfMonth()->toDateString()));
        $dateTo = trim((string) $request->query('date_to', now()->toDateString()));

        $base = Expense::query()
            ->with('user:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('description', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when(in_array($method, ['cash', 'card', 'transfer'], true), fn ($query) => $query->where('method', $method))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('expense_date', '<=', $dateTo));

        $expenses = (clone $base)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Expense $expense) => [
                'id' => $expense->id,
                'expense_date' => optional($expense->expense_date)->toDateString(),
                'description' => $expense->description,
                'category' => $expense->category,
                'method' => $expense->method,
                'amount' => (float) $expense->amount,
                'reference' => $expense->reference,
                'notes' => $expense->notes,
                'has_cash_link' => (bool) $expense->cash_movement_id,
                'cash_session_id' => $expense->cash_session_id,
                'created_by' => $expense->user?->name,
            ]);

        $summary = [
            'total' => round((float) (clone $base)->sum('amount'), 2),
            'cash' => round((float) (clone $base)->where('method', 'cash')->sum('amount'), 2),
            'card' => round((float) (clone $base)->where('method', 'card')->sum('amount'), 2),
            'transfer' => round((float) (clone $base)->where('method', 'transfer')->sum('amount'), 2),
        ];

        $openCashSession = CashSession::query()
            ->with('register:id,name,branch_name')
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => [
                'q' => $q,
                'method' => $method,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'summary' => $summary,
            'open_cash_session' => $openCashSession ? [
                'id' => $openCashSession->id,
                'register_name' => $openCashSession->register?->name,
                'branch_name' => $openCashSession->register?->branch_name,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        DB::transaction(function () use ($request, $data): void {
            $session = $this->openSessionForUser($request);

            $expense = Expense::create([
                'user_id' => $request->user()->id,
                'expense_date' => $data['expense_date'],
                'description' => trim((string) $data['description']),
                'category' => $data['category'] ? trim((string) $data['category']) : null,
                'method' => $data['method'],
                'amount' => round((float) $data['amount'], 2),
                'reference' => $data['reference'] ? trim((string) $data['reference']) : null,
                'notes' => $data['notes'] ? trim((string) $data['notes']) : null,
            ]);

            $this->syncCashMovement($expense, $session, $request->user()->id);
        });

        return back()->with('success', [
            'title' => 'Gasto registrado',
            'description' => 'El gasto fue guardado correctamente.',
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $this->validatePayload($request);

        DB::transaction(function () use ($request, $expense, $data): void {
            $expense->update([
                'expense_date' => $data['expense_date'],
                'description' => trim((string) $data['description']),
                'category' => $data['category'] ? trim((string) $data['category']) : null,
                'method' => $data['method'],
                'amount' => round((float) $data['amount'], 2),
                'reference' => $data['reference'] ? trim((string) $data['reference']) : null,
                'notes' => $data['notes'] ? trim((string) $data['notes']) : null,
            ]);

            $session = $this->openSessionForUser($request);
            $this->syncCashMovement($expense, $session, $request->user()->id);
        });

        return back()->with('success', [
            'title' => 'Gasto actualizado',
            'description' => 'Los cambios fueron guardados correctamente.',
        ]);
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense): void {
            if ($expense->cash_movement_id) {
                CashMovement::query()->where('id', $expense->cash_movement_id)->delete();
            }

            $expense->delete();
        });

        return back()->with('success', [
            'title' => 'Gasto eliminado',
            'description' => 'El gasto fue eliminado correctamente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'method' => ['required', Rule::in(['cash', 'card', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function openSessionForUser(Request $request): ?CashSession
    {
        return CashSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    private function syncCashMovement(Expense $expense, ?CashSession $session, int $userId): void
    {
        if ($expense->method !== 'cash' || ! $session) {
            if ($expense->cash_movement_id) {
                CashMovement::query()->where('id', $expense->cash_movement_id)->delete();
            }

            $expense->update([
                'cash_session_id' => null,
                'cash_movement_id' => null,
            ]);

            return;
        }

        $movementData = [
            'cash_session_id' => $session->id,
            'user_id' => $userId,
            'type' => 'expense',
            'method' => 'cash',
            'amount' => round((float) $expense->amount, 2),
            'note' => $expense->description,
            'meta' => [
                'expense_id' => $expense->id,
                'reference' => $expense->reference,
            ],
        ];

        if ($expense->cash_movement_id) {
            CashMovement::query()
                ->where('id', $expense->cash_movement_id)
                ->update($movementData);
        } else {
            $movement = CashMovement::create($movementData);
            $expense->update([
                'cash_session_id' => $session->id,
                'cash_movement_id' => $movement->id,
            ]);
        }
    }
}
