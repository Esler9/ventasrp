<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankPosTerminal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $accountId = (int) $request->query('account_id', 0);
        $dateFrom = trim((string) $request->query('date_from', now()->startOfMonth()->toDateString()));
        $dateTo = trim((string) $request->query('date_to', now()->toDateString()));

        $accounts = BankAccount::query()
            ->with(['posTerminals' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get()
            ->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'currency' => $account->currency,
                'current_balance' => (float) $account->current_balance,
                'is_active' => (bool) $account->is_active,
                'pos_terminals' => $account->posTerminals->map(fn (BankPosTerminal $terminal) => [
                    'id' => $terminal->id,
                    'name' => $terminal->name,
                    'commission_percent' => (float) $terminal->commission_percent,
                    'is_active' => (bool) $terminal->is_active,
                ])->values(),
            ])
            ->values();

        $posTerminals = BankPosTerminal::query()
            ->with('bankAccount:id,bank_name,account_name')
            ->orderBy('name')
            ->get()
            ->map(fn (BankPosTerminal $terminal) => [
                'id' => $terminal->id,
                'bank_account_id' => $terminal->bank_account_id,
                'bank_account_label' => $this->accountLabel($terminal->bankAccount),
                'name' => $terminal->name,
                'commission_percent' => (float) $terminal->commission_percent,
                'is_active' => (bool) $terminal->is_active,
            ])
            ->values();

        $movementBase = BankMovement::query()
            ->with(['account:id,bank_name,account_name', 'relatedAccount:id,bank_name,account_name', 'user:id,name'])
            ->when($accountId > 0, fn ($query) => $query->where('bank_account_id', $accountId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ->orWhere('type', 'like', "%{$q}%");
            }))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('movement_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('movement_date', '<=', $dateTo));

        $movements = (clone $movementBase)
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BankMovement $movement) => [
                'id' => $movement->id,
                'movement_date' => optional($movement->movement_date)->toDateString(),
                'type' => $movement->type,
                'amount' => (float) $movement->amount,
                'description' => $movement->description,
                'reference' => $movement->reference,
                'account_id' => $movement->bank_account_id,
                'account_label' => $this->accountLabel($movement->account),
                'related_account_label' => $this->accountLabel($movement->relatedAccount),
                'created_by' => $movement->user?->name,
            ]);

        $summary = [
            'total_balance' => round((float) $accounts->sum('current_balance'), 2),
            'deposits' => round((float) (clone $movementBase)->whereIn('type', ['deposit', 'transfer_in'])->sum('amount'), 2),
            'withdrawals' => round((float) (clone $movementBase)->whereIn('type', ['withdrawal', 'transfer_out'])->sum('amount'), 2),
        ];

        return Inertia::render('Admin/Banks/Index', [
            'accounts' => $accounts,
            'pos_terminals' => $posTerminals,
            'movements' => $movements,
            'summary' => $summary,
            'filters' => [
                'q' => $q,
                'account_id' => $accountId > 0 ? $accountId : null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:10'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BankAccount::create([
            'bank_name' => trim((string) $data['bank_name']),
            'account_name' => trim((string) $data['account_name']),
            'account_number' => trim((string) ($data['account_number'] ?? '')) ?: null,
            'currency' => strtoupper(trim((string) $data['currency'])),
            'current_balance' => round((float) $data['current_balance'], 2),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', [
            'title' => 'Cuenta bancaria creada',
            'description' => 'La cuenta se guardó correctamente.',
        ]);
    }

    public function updateAccount(Request $request, BankAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $account->update([
            'bank_name' => trim((string) $data['bank_name']),
            'account_name' => trim((string) $data['account_name']),
            'account_number' => trim((string) ($data['account_number'] ?? '')) ?: null,
            'currency' => strtoupper(trim((string) $data['currency'])),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', [
            'title' => 'Cuenta bancaria actualizada',
            'description' => 'Los cambios fueron guardados.',
        ]);
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'movement_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['deposit', 'withdrawal', 'transfer'])],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'to_bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            /** @var BankAccount|null $origin */
            $origin = BankAccount::query()->lockForUpdate()->find((int) $data['bank_account_id']);
            if (! $origin || ! $origin->is_active) {
                throw new \RuntimeException('La cuenta origen no está disponible.');
            }

            $amount = round((float) $data['amount'], 2);
            $type = (string) $data['type'];
            $description = trim((string) $data['description']);
            $reference = trim((string) ($data['reference'] ?? '')) ?: null;

            if ($type === 'deposit') {
                $origin->increment('current_balance', $amount);
                BankMovement::create([
                    'bank_account_id' => $origin->id,
                    'user_id' => $request->user()->id,
                    'movement_date' => $data['movement_date'],
                    'type' => 'deposit',
                    'amount' => $amount,
                    'description' => $description,
                    'reference' => $reference,
                ]);
                return;
            }

            if ($type === 'withdrawal') {
                if ((float) $origin->current_balance < $amount) {
                    throw new \RuntimeException('Saldo insuficiente en la cuenta origen.');
                }
                $origin->decrement('current_balance', $amount);
                BankMovement::create([
                    'bank_account_id' => $origin->id,
                    'user_id' => $request->user()->id,
                    'movement_date' => $data['movement_date'],
                    'type' => 'withdrawal',
                    'amount' => $amount,
                    'description' => $description,
                    'reference' => $reference,
                ]);
                return;
            }

            $toId = (int) ($data['to_bank_account_id'] ?? 0);
            if ($toId <= 0 || $toId === $origin->id) {
                throw new \RuntimeException('Selecciona una cuenta destino válida para transferir.');
            }

            /** @var BankAccount|null $target */
            $target = BankAccount::query()->lockForUpdate()->find($toId);
            if (! $target || ! $target->is_active) {
                throw new \RuntimeException('La cuenta destino no está disponible.');
            }

            if ((float) $origin->current_balance < $amount) {
                throw new \RuntimeException('Saldo insuficiente en la cuenta origen.');
            }

            $origin->decrement('current_balance', $amount);
            $target->increment('current_balance', $amount);

            $group = (string) Str::uuid();
            BankMovement::create([
                'bank_account_id' => $origin->id,
                'related_account_id' => $target->id,
                'user_id' => $request->user()->id,
                'movement_date' => $data['movement_date'],
                'type' => 'transfer_out',
                'amount' => $amount,
                'description' => $description,
                'reference' => $reference,
                'transfer_group' => $group,
            ]);
            BankMovement::create([
                'bank_account_id' => $target->id,
                'related_account_id' => $origin->id,
                'user_id' => $request->user()->id,
                'movement_date' => $data['movement_date'],
                'type' => 'transfer_in',
                'amount' => $amount,
                'description' => $description,
                'reference' => $reference,
                'transfer_group' => $group,
            ]);
        });

        return back()->with('success', [
            'title' => 'Movimiento bancario registrado',
            'description' => 'La operación fue registrada correctamente.',
        ]);
    }

    public function storePosTerminal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:120'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BankPosTerminal::create([
            'bank_account_id' => (int) $data['bank_account_id'],
            'name' => trim((string) $data['name']),
            'commission_percent' => round((float) $data['commission_percent'], 2),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', [
            'title' => 'POS bancario creado',
            'description' => 'El POS quedó asignado a la cuenta bancaria.',
        ]);
    }

    public function updatePosTerminal(Request $request, BankPosTerminal $terminal): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:120'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $terminal->update([
            'bank_account_id' => (int) $data['bank_account_id'],
            'name' => trim((string) $data['name']),
            'commission_percent' => round((float) $data['commission_percent'], 2),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', [
            'title' => 'POS bancario actualizado',
            'description' => 'Los cambios fueron guardados.',
        ]);
    }

    public function destroyMovement(BankMovement $movement): RedirectResponse
    {
        DB::transaction(function () use ($movement): void {
            $movement->refresh();

            if (in_array($movement->type, ['transfer_out', 'transfer_in'], true) && $movement->transfer_group) {
                $pair = BankMovement::query()
                    ->where('transfer_group', $movement->transfer_group)
                    ->where('id', '!=', $movement->id)
                    ->first();

                $originId = $movement->type === 'transfer_out' ? $movement->bank_account_id : $movement->related_account_id;
                $targetId = $movement->type === 'transfer_out' ? $movement->related_account_id : $movement->bank_account_id;
                $amount = (float) $movement->amount;

                $origin = $originId ? BankAccount::query()->lockForUpdate()->find($originId) : null;
                $target = $targetId ? BankAccount::query()->lockForUpdate()->find($targetId) : null;

                if ($origin && $target && (float) $target->current_balance >= $amount) {
                    $origin->increment('current_balance', $amount);
                    $target->decrement('current_balance', $amount);
                }

                if ($pair) {
                    $pair->delete();
                }

                $movement->delete();
                return;
            }

            $account = BankAccount::query()->lockForUpdate()->find($movement->bank_account_id);
            if ($account) {
                $amount = (float) $movement->amount;
                if ($movement->type === 'deposit') {
                    if ((float) $account->current_balance >= $amount) {
                        $account->decrement('current_balance', $amount);
                    }
                }
                if ($movement->type === 'withdrawal') {
                    $account->increment('current_balance', $amount);
                }
            }

            $movement->delete();
        });

        return back()->with('success', [
            'title' => 'Movimiento eliminado',
            'description' => 'El movimiento fue revertido correctamente.',
        ]);
    }

    private function accountLabel(?BankAccount $account): ?string
    {
        if (! $account) {
            return null;
        }

        return trim($account->bank_name . ' · ' . $account->account_name);
    }
}
