<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankPosTerminal;
use App\Models\Category;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RestaurantAccount;
use App\Models\RestaurantAccountItem;
use App\Models\RestaurantItemConsumption;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantPosController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureRestaurantMode();

        $q = trim((string) $request->query('q', ''));
        $selectedCategoryId = (int) $request->query('category_id', 0);

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->where('is_active', true)
            ->when($selectedCategoryId > 0, fn ($query) => $query->where('category_id', $selectedCategoryId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->with([
                'recipe' => function ($query) {
                    $query->with([
                        'items' => function ($itemsQuery) {
                            $itemsQuery->with('ingredient:id,stock');
                        },
                    ]);
                },
            ])
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'stock', 'unit_label']);

        $availableProducts = $products
            ->filter(function (Product $product): bool {
                $recipe = $product->recipe;
                if ($recipe && $recipe->is_active && $recipe->items->isNotEmpty()) {
                    $possibleByRecipe = null;

                    foreach ($recipe->items as $recipeItem) {
                        $required = max(1, (int) $recipeItem->quantity);
                        $stock = max(0, (int) ($recipeItem->ingredient?->stock ?? 0));
                        $possible = intdiv($stock, $required);
                        $possibleByRecipe = $possibleByRecipe === null ? $possible : min($possibleByRecipe, $possible);

                        if ($possibleByRecipe < 1) {
                            return false;
                        }
                    }

                    return (int) ($possibleByRecipe ?? 0) > 0;
                }

                return (int) $product->stock > 0;
            })
            ->take(60)
            ->values();

        $openCashSession = CashSession::query()
            ->with('register:id,name,branch_name')
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get(['id', 'bank_name', 'account_name', 'currency']);

        $cardPosTerminals = BankPosTerminal::query()
            ->with('bankAccount:id,bank_name,account_name,currency,is_active')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (BankPosTerminal $terminal) => (bool) $terminal->bankAccount?->is_active)
            ->values();

        $tables = RestaurantTable::query()
            ->where('is_active', true)
            ->with(['accounts' => function ($query) {
                $query->where('status', 'open')
                    ->with(['items' => function ($itemsQuery) {
                        $itemsQuery->select([
                            'id',
                            'restaurant_account_id',
                            'quantity',
                            'unit_price',
                            'kitchen_status',
                        ]);
                    }])
                    ->orderBy('opened_at');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Pos/Restaurant', [
            'filters' => [
                'q' => $q,
                'category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : null,
            ],
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
            'products' => $availableProducts->map(fn (Product $product) => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'unit_label' => $product->unit_label ?: 'Unidad',
            ])->values(),
            'tables' => $tables->map(function (RestaurantTable $table) {
                $accounts = $table->accounts->map(function (RestaurantAccount $account) {
                    $itemsCount = (int) $account->items->sum('quantity');
                    $activeItems = $account->items->where('kitchen_status', '!=', 'canceled');
                    $servedItems = $activeItems->where('kitchen_status', 'served');
                    $total = (float) $activeItems->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity);
                    $servedTotal = (float) $servedItems->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity);

                    return [
                        'id' => $account->id,
                        'split_type' => $account->split_type,
                        'label' => $account->label ?: ('Cuenta #' . $account->id),
                        'opened_at' => optional($account->opened_at)->format('Y-m-d H:i:s'),
                        'items_count' => $itemsCount,
                        'total' => round($total, 2),
                        'served_total' => round($servedTotal, 2),
                        'draft_items' => $account->items->where('kitchen_status', 'draft')->count(),
                        'pending_items' => $account->items->where('kitchen_status', 'pending')->count(),
                        'preparing_items' => $account->items->where('kitchen_status', 'preparing')->count(),
                        'ready_items' => $account->items->where('kitchen_status', 'ready')->count(),
                        'orders_count' => $account->items->whereNotNull('restaurant_order_id')->pluck('restaurant_order_id')->unique()->count(),
                        'can_settle' => $account->items->whereIn('kitchen_status', ['draft', 'pending', 'preparing', 'ready'])->count() === 0
                            && $account->items->where('kitchen_status', 'served')->count() > 0,
                    ];
                })->values();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'code' => $table->code,
                    'is_takeaway' => (bool) $table->is_takeaway,
                    'status' => $accounts->isEmpty() ? 'free' : 'occupied',
                    'accounts' => $accounts,
                ];
            })->values(),
            'bank_accounts' => $bankAccounts->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'label' => trim($account->bank_name . ' · ' . $account->account_name),
                'currency' => $account->currency,
            ])->values(),
            'card_pos_terminals' => $cardPosTerminals->map(fn (BankPosTerminal $terminal) => [
                'id' => $terminal->id,
                'name' => $terminal->name,
                'commission_percent' => (float) $terminal->commission_percent,
                'bank_account_id' => $terminal->bank_account_id,
                'bank_account_label' => trim(($terminal->bankAccount?->bank_name ?? '') . ' · ' . ($terminal->bankAccount?->account_name ?? '')),
                'currency' => $terminal->bankAccount?->currency ?? 'GTQ',
            ])->values(),
            'open_cash_session' => $openCashSession ? [
                'id' => $openCashSession->id,
                'register' => $openCashSession->register?->name,
                'branch' => $openCashSession->register?->branch_name,
                'opened_at' => optional($openCashSession->opened_at)->format('Y-m-d H:i:s'),
                'opening_amount' => (float) $openCashSession->opening_amount,
            ] : null,
        ]);
    }

    public function createAccount(Request $request): RedirectResponse
    {
        $this->ensureRestaurantMode();

        $data = $request->validate([
            'table_id' => ['required', 'integer', 'exists:restaurant_tables,id'],
            'split_type' => ['required', Rule::in(['unique', 'split'])],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $table = RestaurantTable::query()
            ->where('is_active', true)
            ->findOrFail((int) $data['table_id']);

        if ($data['split_type'] === 'unique') {
            $alreadyOpen = RestaurantAccount::query()
                ->where('restaurant_table_id', $table->id)
                ->where('status', 'open')
                ->exists();

            if ($alreadyOpen) {
                return back()->withErrors([
                    'restaurant' => 'La mesa ya tiene cuentas abiertas. Usa "Cuentas separadas" para abrir otra.',
                ]);
            }
        }

        RestaurantAccount::create([
            'restaurant_table_id' => $table->id,
            'opened_by_user_id' => $request->user()->id,
            'status' => 'open',
            'split_type' => $data['split_type'],
            'label' => trim((string) ($data['label'] ?? '')) ?: null,
            'opened_at' => now(),
        ]);

        return back()->with('success', [
            'title' => 'Cuenta abierta',
            'description' => 'La cuenta fue creada correctamente.',
        ]);
    }

    public function addItem(Request $request, RestaurantAccount $account): RedirectResponse
    {
        $this->ensureRestaurantMode();

        if ($account->status !== 'open') {
            return back()->withErrors(['restaurant' => 'La cuenta ya está cerrada.']);
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::query()->findOrFail((int) $data['product_id']);
        if (! $product->is_active) {
            return back()->withErrors(['restaurant' => 'El producto no está activo.']);
        }

        RestaurantAccountItem::create([
            'restaurant_account_id' => $account->id,
            'restaurant_order_id' => null,
            'product_id' => $product->id,
            'added_by_user_id' => $request->user()->id,
            'quantity' => (int) $data['quantity'],
            'unit_price' => (float) $product->price,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'kitchen_status' => 'draft',
        ]);

        return back()->with('success', [
            'title' => 'Producto agregado',
            'description' => 'La cuenta fue actualizada.',
        ]);
    }

    public function sendToKitchen(Request $request, RestaurantAccount $account): RedirectResponse
    {
        $this->ensureRestaurantMode();

        if ($account->status !== 'open') {
            return back()->withErrors(['restaurant' => 'La cuenta ya está cerrada.']);
        }

        $result = DB::transaction(function () use ($account, $request): array {
            $draftItems = RestaurantAccountItem::query()
                ->where('restaurant_account_id', $account->id)
                ->where('kitchen_status', 'draft')
                ->lockForUpdate()
                ->get(['id']);

            if ($draftItems->isEmpty()) {
                return ['order_id' => null, 'updated' => 0];
            }

            $order = RestaurantOrder::create([
                'restaurant_account_id' => $account->id,
                'restaurant_table_id' => $account->restaurant_table_id,
                'created_by_user_id' => $request->user()->id,
                'status' => 'pending',
                'sent_at' => now(),
            ]);

            $updated = RestaurantAccountItem::query()
                ->whereIn('id', $draftItems->pluck('id'))
                ->update([
                    'restaurant_order_id' => $order->id,
                    'kitchen_status' => 'pending',
                    'sent_at' => now(),
                ]);

            return ['order_id' => $order->id, 'updated' => $updated];
        });

        if (($result['updated'] ?? 0) === 0) {
            return back()->withErrors(['restaurant' => 'No hay ítems en borrador para enviar a cocina.']);
        }

        return back()->with('success', [
            'title' => 'Orden enviada',
            'description' => "Orden #{$result['order_id']} enviada con {$result['updated']} ítems a cocina.",
        ]);
    }

    public function closeAccount(Request $request, RestaurantAccount $account): RedirectResponse
    {
        $this->ensureRestaurantMode();

        if ($account->status !== 'open') {
            return back()->withErrors(['restaurant' => 'La cuenta ya está cerrada.']);
        }

        $pendingWork = RestaurantAccountItem::query()
            ->where('restaurant_account_id', $account->id)
            ->whereIn('kitchen_status', ['draft', 'pending', 'preparing', 'ready'])
            ->exists();

        if ($pendingWork) {
            return back()->withErrors([
                'restaurant' => 'No puedes cerrar la cuenta: hay ítems en borrador o pendientes de cocina.',
            ]);
        }

        $account->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return back()->with('success', [
            'title' => 'Cuenta cerrada',
            'description' => 'La cuenta fue cerrada correctamente.',
        ]);
    }

    public function settleAccount(Request $request, RestaurantAccount $account): RedirectResponse
    {
        $this->ensureRestaurantMode();

        $data = $request->validate([
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', Rule::in(['cash', 'card', 'transfer'])],
            'payments.*.bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'payments.*.card_pos_terminal_id' => ['nullable', 'integer', 'exists:bank_pos_terminals,id'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
        ]);

        if ($account->status !== 'open') {
            return back()->withErrors(['restaurant' => 'La cuenta ya está cerrada.']);
        }
        if ($account->sale_id) {
            return back()->withErrors(['restaurant' => 'La cuenta ya fue liquidada.']);
        }

        $session = CashSession::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $session) {
            return back()->withErrors(['restaurant' => 'Debes abrir caja antes de cobrar una cuenta.']);
        }

        $pendingWork = RestaurantAccountItem::query()
            ->where('restaurant_account_id', $account->id)
            ->whereIn('kitchen_status', ['draft', 'pending', 'preparing', 'ready'])
            ->exists();
        if ($pendingWork) {
            return back()->withErrors(['restaurant' => 'No puedes cobrar: hay ítems pendientes de cocina.']);
        }

        $servedItems = RestaurantAccountItem::query()
            ->with('product:id,name,price,unit_label')
            ->where('restaurant_account_id', $account->id)
            ->where('kitchen_status', 'served')
            ->get();

        if ($servedItems->isEmpty()) {
            return back()->withErrors(['restaurant' => 'No hay ítems entregados para cobrar.']);
        }

        $total = round((float) $servedItems->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity), 2);
        $payments = collect($data['payments'] ?? []);
        if ($payments->isEmpty()) {
            $payments = collect([[
                'method' => 'cash',
                'bank_account_id' => null,
                'card_pos_terminal_id' => null,
                'reference' => null,
                'amount' => $total,
            ]]);
        }

        foreach ($payments as $payment) {
            if (($payment['method'] ?? null) === 'transfer' && empty($payment['bank_account_id'])) {
                return back()->withErrors(['restaurant' => 'Selecciona la cuenta bancaria para pagos por transferencia.']);
            }
            if (($payment['method'] ?? null) === 'card' && empty($payment['card_pos_terminal_id'])) {
                return back()->withErrors(['restaurant' => 'Selecciona POS para pagos con tarjeta.']);
            }
            if (($payment['method'] ?? null) === 'card' && trim((string) ($payment['reference'] ?? '')) === '') {
                return back()->withErrors(['restaurant' => 'Ingresa referencia en pagos con tarjeta.']);
            }
        }

        $paymentsTotal = round((float) $payments->sum(fn (array $payment) => (float) $payment['amount']), 2);
        if (abs($paymentsTotal - $total) > 0.01) {
            return back()->withErrors(['restaurant' => 'La suma de pagos debe ser igual al total de la cuenta.']);
        }

        DB::transaction(function () use ($account, $request, $payments, $session, $total): void {
            $user = $request->user();
            $lockedAccount = RestaurantAccount::query()
                ->lockForUpdate()
                ->find($account->id);

            if (! $lockedAccount || $lockedAccount->status !== 'open' || $lockedAccount->sale_id) {
                throw ValidationException::withMessages([
                    'restaurant' => 'La cuenta ya fue liquidada o cerrada por otro usuario.',
                ]);
            }

            $pendingWork = RestaurantAccountItem::query()
                ->where('restaurant_account_id', $lockedAccount->id)
                ->whereIn('kitchen_status', ['draft', 'pending', 'preparing', 'ready'])
                ->lockForUpdate()
                ->exists();
            if ($pendingWork) {
                throw ValidationException::withMessages([
                    'restaurant' => 'No puedes cobrar: hay ítems pendientes de cocina.',
                ]);
            }

            $servedItems = RestaurantAccountItem::query()
                ->with('product:id,name,price,unit_label')
                ->where('restaurant_account_id', $lockedAccount->id)
                ->where('kitchen_status', 'served')
                ->lockForUpdate()
                ->get();
            if ($servedItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'restaurant' => 'No hay ítems entregados para cobrar.',
                ]);
            }

            $servedTotal = round((float) $servedItems->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity), 2);
            if (abs($servedTotal - $total) > 0.01) {
                throw ValidationException::withMessages([
                    'restaurant' => 'El total cambió durante el cobro. Reintenta para continuar.',
                ]);
            }

            $cardPosById = BankPosTerminal::query()
                ->with('bankAccount:id,is_active')
                ->whereIn('id', $payments->where('method', 'card')->pluck('card_pos_terminal_id')->filter()->map(fn ($id) => (int) $id)->unique()->values())
                ->get()
                ->keyBy('id');

            $sale = Sale::create([
                'sale_code' => $this->generateRestaurantSaleCode(),
                'user_id' => $user->id,
                'customer_id' => null,
                'customer_name' => trim(($lockedAccount->table?->name ?? 'Mesa') . ' · ' . ($lockedAccount->label ?: ('Cuenta #' . $lockedAccount->id))),
                'cash_session_id' => $session->id,
                'items_count' => (int) $servedItems->sum('quantity'),
                'total' => $servedTotal,
            ]);

            foreach ($servedItems as $item) {
                $product = $item->product;
                $unitPrice = round((float) $item->unit_price, 2);
                $qty = (int) $item->quantity;

                $sale->items()->create([
                    'product_id' => $item->product_id,
                    'product_presentation_id' => null,
                    'presentation_name' => $product?->unit_label ?: 'Unidad',
                    'presentation_factor' => 1,
                    'presentation_price' => $unitPrice,
                    'presentation_quantity' => $qty,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'original_price' => (float) ($product?->price ?? $unitPrice),
                    'discount_amount' => 0,
                    'note' => $item->note,
                ]);
            }

            foreach ($payments as $payment) {
                $method = (string) $payment['method'];
                $amount = round((float) $payment['amount'], 2);
                $bankAccountId = isset($payment['bank_account_id']) ? (int) $payment['bank_account_id'] : null;
                $cardPosTerminalId = isset($payment['card_pos_terminal_id']) ? (int) $payment['card_pos_terminal_id'] : null;
                $reference = trim((string) ($payment['reference'] ?? '')) ?: null;
                $commissionPercent = null;
                $commissionAmount = null;
                $netAmount = null;

                if ($method === 'card') {
                    /** @var BankPosTerminal|null $terminal */
                    $terminal = $cardPosById->get($cardPosTerminalId);
                    if (! $terminal || ! $terminal->bankAccount || ! $terminal->bankAccount->is_active) {
                        throw ValidationException::withMessages([
                            'restaurant' => 'El POS seleccionado no está disponible.',
                        ]);
                    }
                    $bankAccountId = (int) $terminal->bank_account_id;
                    $commissionPercent = round((float) $terminal->commission_percent, 2);
                    $commissionAmount = round(($amount * $commissionPercent) / 100, 2);
                    $netAmount = round($amount - $commissionAmount, 2);
                    if ($netAmount < 0) {
                        $netAmount = 0.0;
                    }
                }

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'cash_session_id' => $session->id,
                    'user_id' => $user->id,
                    'method' => $method,
                    'bank_account_id' => $bankAccountId,
                    'card_pos_terminal_id' => $cardPosTerminalId,
                    'reference' => $reference,
                    'apply_surcharge' => false,
                    'surcharge_percent' => null,
                    'surcharge_amount' => null,
                    'base_amount' => $amount,
                    'commission_percent' => $commissionPercent,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $netAmount,
                    'amount' => $amount,
                ]);

                if ($method === 'cash') {
                    CashMovement::create([
                        'cash_session_id' => $session->id,
                        'user_id' => $user->id,
                        'type' => 'sale',
                            'method' => 'cash',
                            'amount' => $amount,
                            'note' => 'Venta restaurante ' . $sale->sale_code,
                            'meta' => ['sale_id' => $sale->id, 'restaurant_account_id' => $lockedAccount->id],
                        ]);
                    }

                if ($method === 'transfer' && $bankAccountId) {
                    $bankAccount = BankAccount::query()->lockForUpdate()->find($bankAccountId);
                    if (! $bankAccount || ! $bankAccount->is_active) {
                        throw ValidationException::withMessages([
                            'restaurant' => 'La cuenta bancaria de transferencia no está disponible.',
                        ]);
                    }
                    $bankAccount->increment('current_balance', $amount);
                    BankMovement::create([
                        'bank_account_id' => $bankAccount->id,
                        'user_id' => $user->id,
                        'movement_date' => now()->toDateString(),
                        'type' => 'deposit',
                        'amount' => $amount,
                        'description' => 'Cobro cuenta restaurante ' . $sale->sale_code,
                        'reference' => $reference ?: $sale->sale_code,
                    ]);
                }

                if ($method === 'card') {
                    /** @var BankPosTerminal|null $terminal */
                    $terminal = $cardPosById->get((int) $cardPosTerminalId);
                    if (! $terminal) {
                        throw ValidationException::withMessages([
                            'restaurant' => 'El POS seleccionado no está disponible.',
                        ]);
                    }
                    $bankAccount = BankAccount::query()->lockForUpdate()->find($terminal->bank_account_id);
                    if (! $bankAccount || ! $bankAccount->is_active) {
                        throw ValidationException::withMessages([
                            'restaurant' => 'La cuenta bancaria del POS no está disponible.',
                        ]);
                    }

                    $depositAmount = round((float) ($netAmount ?? $amount), 2);
                    $bankAccount->increment('current_balance', $depositAmount);
                    BankMovement::create([
                        'bank_account_id' => $bankAccount->id,
                        'user_id' => $user->id,
                        'movement_date' => now()->toDateString(),
                        'type' => 'deposit',
                        'amount' => $depositAmount,
                        'description' => sprintf(
                            'Cobro cuenta restaurante %s · POS %s · Comisión %.2f%% (Q%.2f)',
                            $sale->sale_code,
                            $terminal->name,
                            (float) ($commissionPercent ?? 0),
                            (float) ($commissionAmount ?? 0)
                        ),
                        'reference' => $reference ?: $sale->sale_code,
                    ]);
                }
            }

            $lockedAccount->update([
                'sale_id' => $sale->id,
                'status' => 'closed',
                'closed_at' => now(),
                'settled_at' => now(),
                'settled_by_user_id' => $user->id,
            ]);
        });

        return back()->with('success', [
            'title' => 'Cuenta cobrada',
            'description' => 'Se registró la venta y se cerró la cuenta.',
        ]);
    }

    public function kitchen(Request $request): Response
    {
        $this->ensureRestaurantMode();

        $orders = RestaurantOrder::query()
            ->with([
                'table:id,name',
                'account:id,label',
                'items' => function ($query) {
                    $query->with('product:id,name')
                        ->whereIn('kitchen_status', ['pending', 'preparing', 'ready'])
                        ->orderByRaw("CASE kitchen_status WHEN 'pending' THEN 1 WHEN 'preparing' THEN 2 WHEN 'ready' THEN 3 ELSE 4 END")
                        ->orderBy('id');
                },
            ])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'preparing' THEN 2 WHEN 'ready' THEN 3 ELSE 4 END")
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();

        $servedRecent = RestaurantAccountItem::query()
            ->with(['product:id,name', 'account:id,restaurant_table_id,sale_id,status,label', 'account.table:id,name'])
            ->where('kitchen_status', 'served')
            ->whereHas('account', fn ($query) => $query->where('status', 'open')->whereNull('sale_id'))
            ->whereDate('served_at', '>=', now()->subDay()->toDateString())
            ->latest('served_at')
            ->limit(40)
            ->get()
            ->map(fn (RestaurantAccountItem $item) => [
                'id' => $item->id,
                'quantity' => (int) $item->quantity,
                'note' => $item->note,
                'kitchen_status' => $item->kitchen_status,
                'product_name' => $item->product?->name ?: 'Producto',
                'account_label' => $item->account?->label ?: ('Cuenta #' . $item->restaurant_account_id),
                'table_name' => $item->account?->table?->name ?: 'Mesa',
                'served_at' => optional($item->served_at)->format('Y-m-d H:i:s'),
            ])
            ->values();

        return Inertia::render('Pos/Kitchen', [
            'orders' => $orders->map(function (RestaurantOrder $order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'sent_at' => optional($order->sent_at)->format('Y-m-d H:i:s'),
                    'table_name' => $order->table?->name ?: 'Mesa',
                    'account_label' => $order->account?->label ?: ('Cuenta #' . $order->restaurant_account_id),
                    'items_count' => $order->items->count(),
                    'items' => $order->items->map(function (RestaurantAccountItem $item) {
                        return [
                            'id' => $item->id,
                            'quantity' => (int) $item->quantity,
                            'note' => $item->note,
                            'kitchen_status' => $item->kitchen_status,
                            'product_name' => $item->product?->name ?: 'Producto',
                        ];
                    })->values(),
                ];
            })->values(),
            'served_recent' => $servedRecent,
        ]);
    }

    public function updateKitchenStatus(Request $request, RestaurantAccountItem $item): RedirectResponse
    {
        $this->ensureRestaurantMode();

        $data = $request->validate([
            'kitchen_status' => ['required', Rule::in(['preparing', 'ready', 'served'])],
        ]);

        DB::transaction(function () use ($item, $data, $request): void {
            $status = (string) $data['kitchen_status'];
            $current = (string) $item->kitchen_status;

            $allowed = match ($current) {
                'pending' => ['preparing'],
                'preparing' => ['ready'],
                'ready' => ['served'],
                default => [],
            };

            if (! in_array($status, $allowed, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'kitchen_status' => 'Transición de estado no válida para este ítem.',
                ]);
            }

            $updates = ['kitchen_status' => $status];

            if ($status === 'preparing' && ! $item->started_at) {
                $updates['started_at'] = now();
            }
            if ($status === 'ready' && ! $item->ready_at) {
                $updates['ready_at'] = now();
            }
            if ($status === 'served' && ! $item->served_at) {
                $updates['served_at'] = now();
            }

            if ($status === 'served') {
                $this->consumeInventoryForServedItem($item, (int) $request->user()->id);
            }

            $item->update($updates);
            $this->syncOrderStatus((int) $item->restaurant_order_id);
        });

        return back()->with('success', [
            'title' => 'Estado actualizado',
            'description' => 'Se actualizó el estado del ítem en cocina.',
        ]);
    }

    public function cancelItem(Request $request, RestaurantAccountItem $item): RedirectResponse
    {
        $this->ensureRestaurantMode();

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $item->loadMissing('account:id,status,sale_id');
        $account = $item->account;
        if (! $account || $account->status !== 'open' || $account->sale_id) {
            return back()->withErrors(['restaurant' => 'No puedes anular ítems de una cuenta ya liquidada/cerrada.']);
        }
        if ($item->kitchen_status === 'canceled') {
            return back()->withErrors(['restaurant' => 'El ítem ya fue anulado.']);
        }

        DB::transaction(function () use ($item, $request, $data): void {
            if ($item->kitchen_status === 'served') {
                $this->reverseConsumptionsForCanceledItem($item, (int) $request->user()->id);
            }

            $item->update([
                'kitchen_status' => 'canceled',
                'canceled_by_user_id' => $request->user()->id,
                'canceled_at' => now(),
                'cancel_reason' => trim((string) $data['reason']),
            ]);

            $this->syncOrderStatus((int) $item->restaurant_order_id);
        });

        return back()->with('success', [
            'title' => 'Ítem anulado',
            'description' => 'El ítem fue anulado correctamente.',
        ]);
    }

    private function ensureRestaurantMode(): void
    {
        $mode = AppSetting::current()->business_mode ?: 'minorista';
        abort_unless($mode === 'restaurante', 404);
    }

    private function syncOrderStatus(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $order = RestaurantOrder::query()
            ->with('items:id,restaurant_order_id,kitchen_status')
            ->find($orderId);

        if (! $order) {
            return;
        }

        $statuses = $order->items->pluck('kitchen_status')->values();
        $activeStatuses = $statuses
            ->filter(fn (string $status) => $status !== 'canceled')
            ->values();

        if ($activeStatuses->isEmpty() || $activeStatuses->every(fn (string $status) => $status === 'served')) {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return;
        }

        if ($activeStatuses->contains('ready') && ! $activeStatuses->contains('pending') && ! $activeStatuses->contains('preparing')) {
            $order->update([
                'status' => 'ready',
                'completed_at' => null,
            ]);

            return;
        }

        if ($activeStatuses->contains('preparing') || ($activeStatuses->contains('ready') && $activeStatuses->contains('pending'))) {
            $order->update([
                'status' => 'preparing',
                'completed_at' => null,
            ]);

            return;
        }

        $order->update([
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    private function consumeInventoryForServedItem(RestaurantAccountItem $item, int $userId): void
    {
        $item->loadMissing([
            'product:id,name,stock',
            'order:id',
            'product.recipe' => function ($query) {
                $query->with('items:id,product_recipe_id,ingredient_product_id,quantity');
            },
        ]);

        $activeConsumptions = RestaurantItemConsumption::query()
            ->where('restaurant_account_item_id', $item->id)
            ->whereNull('reversed_at')
            ->exists();
        if ($activeConsumptions) {
            return;
        }

        $servedQuantity = max(1, (int) $item->quantity);
        $recipe = $item->product?->recipe;
        $recipeItems = collect($recipe?->items ?? [])->filter(fn ($ingredient) => (int) $ingredient->quantity > 0);

        if ($recipe && $recipe->is_active && $recipeItems->isNotEmpty()) {
            $ingredientIds = $recipeItems->pluck('ingredient_product_id')->map(fn ($id) => (int) $id)->values();
            $ingredients = Product::query()
                ->whereIn('id', $ingredientIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($recipeItems as $recipeItem) {
                /** @var Product|null $ingredient */
                $ingredient = $ingredients->get((int) $recipeItem->ingredient_product_id);
                $required = (int) $recipeItem->quantity * $servedQuantity;

                if (! $ingredient || (int) $ingredient->stock < $required) {
                    $name = $ingredient?->name ?: 'insumo';
                    throw ValidationException::withMessages([
                        'restaurant' => "Stock insuficiente de {$name} para entregar esta orden.",
                    ]);
                }
            }

            foreach ($recipeItems as $recipeItem) {
                /** @var Product $ingredient */
                $ingredient = $ingredients->get((int) $recipeItem->ingredient_product_id);
                $required = (int) $recipeItem->quantity * $servedQuantity;

                $ingredient->decrement('stock', $required);

                InventoryMovement::create([
                    'product_id' => $ingredient->id,
                    'user_id' => $userId,
                    'type' => 'recipe_served',
                    'quantity' => -$required,
                    'note' => 'Consumo receta · Orden #' . ($item->order?->id ?: $item->restaurant_order_id) . ' · ' . ($item->product?->name ?: 'Producto'),
                ]);

                RestaurantItemConsumption::create([
                    'restaurant_account_item_id' => $item->id,
                    'product_id' => $ingredient->id,
                    'quantity' => $required,
                    'source_type' => 'recipe',
                ]);
            }

            return;
        }

        $product = Product::query()->lockForUpdate()->find((int) $item->product_id);
        if (! $product || (int) $product->stock < $servedQuantity) {
            throw ValidationException::withMessages([
                'restaurant' => 'Stock insuficiente del producto para marcar como entregado.',
            ]);
        }

        $product->decrement('stock', $servedQuantity);

        InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => 'restaurant_served',
            'quantity' => -$servedQuantity,
            'note' => 'Entrega restaurante · Orden #' . ($item->order?->id ?: $item->restaurant_order_id),
        ]);

        RestaurantItemConsumption::create([
            'restaurant_account_item_id' => $item->id,
            'product_id' => $product->id,
            'quantity' => $servedQuantity,
            'source_type' => 'product',
        ]);
    }

    private function reverseConsumptionsForCanceledItem(RestaurantAccountItem $item, int $userId): void
    {
        $consumptions = RestaurantItemConsumption::query()
            ->where('restaurant_account_item_id', $item->id)
            ->whereNull('reversed_at')
            ->lockForUpdate()
            ->get();

        if ($consumptions->isEmpty()) {
            return;
        }

        $productIds = $consumptions->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($consumptions as $consumption) {
            /** @var Product|null $product */
            $product = $products->get((int) $consumption->product_id);
            if (! $product) {
                continue;
            }

            $qty = max(1, (int) $consumption->quantity);
            $product->increment('stock', $qty);

            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => 'restaurant_cancel_restock',
                'quantity' => $qty,
                'note' => 'Reverso por anulación ítem restaurante #' . $item->id,
            ]);

            $consumption->update([
                'reversed_by_user_id' => $userId,
                'reversed_at' => now(),
            ]);
        }
    }

    private function generateRestaurantSaleCode(): string
    {
        $prefix = 'R' . now()->format('Ymd');
        $lastCode = Sale::query()
            ->where('sale_code', 'like', $prefix . '-%')
            ->orderByDesc('sale_code')
            ->value('sale_code');

        if (! $lastCode) {
            return $prefix . '-0001';
        }

        $currentNumber = (int) substr((string) $lastCode, strrpos((string) $lastCode, '-') + 1);
        $nextNumber = str_pad((string) ($currentNumber + 1), 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $nextNumber;
    }
}
