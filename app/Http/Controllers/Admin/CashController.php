<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\SalePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashController extends Controller
{
    public function index(Request $request): Response
    {
        $session = $this->openSessionForUser($request);

        $registers = CashRegister::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'branch_name']);

        $summary = [
            'opening' => 0,
            'cash_sales' => 0,
            'incomes' => 0,
            'expenses' => 0,
            'expected' => 0,
        ];

        $salesByMethod = [];
        $recentMovements = [];

        if ($session) {
            $salesByMethod = SalePayment::query()
                ->where('cash_session_id', $session->id)
                ->select('method')
                ->selectRaw('sum(amount) as total')
                ->groupBy('method')
                ->pluck('total', 'method')
                ->map(fn ($value) => (float) $value)
                ->toArray();

            $incomes = (float) CashMovement::query()
                ->where('cash_session_id', $session->id)
                ->where('type', 'income')
                ->sum('amount');

            $expenses = (float) CashMovement::query()
                ->where('cash_session_id', $session->id)
                ->where('type', 'expense')
                ->sum('amount');

            $cashSales = (float) ($salesByMethod['cash'] ?? 0);
            $opening = (float) $session->opening_amount;
            $expected = round($opening + $cashSales + $incomes - $expenses, 2);

            $summary = [
                'opening' => $opening,
                'cash_sales' => $cashSales,
                'incomes' => $incomes,
                'expenses' => $expenses,
                'expected' => $expected,
            ];

            $recentMovements = CashMovement::query()
                ->where('cash_session_id', $session->id)
                ->latest('id')
                ->limit(10)
                ->get(['id', 'type', 'method', 'amount', 'note', 'created_at'])
                ->map(fn (CashMovement $movement) => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'method' => $movement->method,
                    'amount' => (float) $movement->amount,
                    'note' => $movement->note,
                    'created_at' => optional($movement->created_at)->format('Y-m-d H:i:s'),
                ])
                ->values();
        }

        return Inertia::render('Admin/Cash/Index', [
            'registers' => $registers,
            'open_session' => $session ? [
                'id' => $session->id,
                'status' => $session->status,
                'opening_amount' => (float) $session->opening_amount,
                'opened_at' => optional($session->opened_at)->format('Y-m-d H:i:s'),
                'register_name' => $session->register?->name,
                'branch_name' => $session->register?->branch_name,
            ] : null,
            'summary' => $summary,
            'sales_by_method' => $salesByMethod,
            'recent_movements' => $recentMovements,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cash_register_id' => ['required', 'integer', 'exists:cash_registers,id'],
            'opening_amount' => ['required', 'numeric', 'min:0'],
            'open_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = $this->openSessionForUser($request);
        if ($existing) {
            return back()->withErrors(['cash' => 'Ya tienes una caja abierta.']);
        }

        DB::transaction(function () use ($data, $request): void {
            $session = CashSession::create([
                'cash_register_id' => (int) $data['cash_register_id'],
                'user_id' => $request->user()->id,
                'status' => 'open',
                'opening_amount' => $data['opening_amount'],
                'open_note' => $data['open_note'] ?? null,
                'opened_at' => now(),
            ]);

            CashMovement::create([
                'cash_session_id' => $session->id,
                'user_id' => $request->user()->id,
                'type' => 'opening',
                'method' => 'cash',
                'amount' => $session->opening_amount,
                'note' => 'Apertura de caja',
            ]);
        });

        return back()->with('success', [
            'title' => 'Caja abierta',
            'description' => 'La caja se abrió correctamente.',
        ]);
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $session = $this->openSessionForUser($request);
        if (! $session) {
            return back()->withErrors(['cash' => 'No tienes una caja abierta.']);
        }

        CashMovement::create([
            'cash_session_id' => $session->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'method' => 'cash',
            'amount' => $data['amount'],
            'note' => $data['note'],
        ]);

        return back()->with('success', [
            'title' => 'Movimiento registrado',
            'description' => 'El movimiento de caja se guardó correctamente.',
        ]);
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
            'close_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = $this->openSessionForUser($request);
        if (! $session) {
            return back()->withErrors(['cash' => 'No tienes una caja abierta.']);
        }

        $salesByMethod = SalePayment::query()
            ->where('cash_session_id', $session->id)
            ->select('method')
            ->selectRaw('sum(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $incomes = (float) CashMovement::query()
            ->where('cash_session_id', $session->id)
            ->where('type', 'income')
            ->sum('amount');

        $expenses = (float) CashMovement::query()
            ->where('cash_session_id', $session->id)
            ->where('type', 'expense')
            ->sum('amount');

        $cashSales = (float) ($salesByMethod['cash'] ?? 0);
        $expected = round((float) $session->opening_amount + $cashSales + $incomes - $expenses, 2);
        $counted = round((float) $data['counted_amount'], 2);
        $difference = round($counted - $expected, 2);

        DB::transaction(function () use ($session, $expected, $counted, $difference, $data, $request): void {
            $session->update([
                'status' => 'closed',
                'expected_amount' => $expected,
                'counted_amount' => $counted,
                'difference_amount' => $difference,
                'close_note' => $data['close_note'] ?? null,
                'closed_at' => now(),
            ]);

            if (abs($difference) > 0.009) {
                CashMovement::create([
                    'cash_session_id' => $session->id,
                    'user_id' => $request->user()->id,
                    'type' => 'closing_adjustment',
                    'method' => 'cash',
                    'amount' => abs($difference),
                    'note' => $difference >= 0 ? 'Sobrante en cierre' : 'Faltante en cierre',
                ]);
            }
        });

        return back()->with('success', [
            'title' => 'Caja cerrada',
            'description' => 'El cierre y arqueo quedaron registrados.',
        ]);
    }

    private function openSessionForUser(Request $request): ?CashSession
    {
        return CashSession::query()
            ->with('register:id,name,branch_name')
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }
}
