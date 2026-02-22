<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\BankAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $selectedCategoryId = (int) $request->query('category_id', 0);

        $topCategories = Category::query()
            ->select(['categories.id', 'categories.name'])
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->join('sale_items', 'sale_items.product_id', '=', 'products.id')
            ->where('categories.is_active', true)
            ->where('products.is_active', true)
            ->groupBy('categories.id', 'categories.name')
            ->orderByRaw('SUM(sale_items.quantity) DESC')
            ->limit(5)
            ->get();

        if ($topCategories->count() < 5) {
            $excluded = $topCategories->pluck('id');
            $fallback = Category::query()
                ->select(['categories.id', 'categories.name'])
                ->join('products', 'products.category_id', '=', 'categories.id')
                ->where('categories.is_active', true)
                ->where('products.is_active', true)
                ->when($excluded->isNotEmpty(), fn ($query) => $query->whereNotIn('categories.id', $excluded))
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('categories.name')
                ->limit(5 - $topCategories->count())
                ->get();

            $topCategories = $topCategories->concat($fallback);
        }

        $selectedCategoryId = $selectedCategoryId > 0 && $topCategories->pluck('id')->contains($selectedCategoryId)
            ? $selectedCategoryId
            : 0;

        $products = Product::query()
            ->select(['id', 'category_id', 'name', 'unit_label', 'description', 'sku', 'stock', 'price', 'expires_at', 'photo'])
            ->where('is_active', true)
            ->with(['presentations' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('factor')
                    ->select(['id', 'product_id', 'name', 'factor', 'price', 'is_active']);
            }])
            ->when($selectedCategoryId > 0, fn ($query) => $query->where('category_id', $selectedCategoryId))
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(function ($product) {
                $path = ltrim((string) $product->photo, '/');
                $product->photo_url = $path === '' ? null : asset(Str::startsWith($path, 'products/') ? $path : 'products/' . $path);
                $product->unit_label = $product->unit_label ?? 'Unidad';

                return $product;
            });

        $openCashSession = CashSession::query()
            ->with('register:id,name,branch_name')
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $clients = Client::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'phone', 'tax_id']);

        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get(['id', 'bank_name', 'account_name', 'currency']);

        return Inertia::render('Pos/Index', [
            'products' => $products,
            'filters' => [
                'q' => $q,
            ],
            'top_categories' => $topCategories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
            'selected_category_id' => $selectedCategoryId,
            'clients' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'tax_id' => $client->tax_id,
            ])->values(),
            'bank_accounts' => $bankAccounts->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'label' => trim($account->bank_name . ' · ' . $account->account_name),
                'currency' => $account->currency,
            ])->values(),
            'defaults' => [
                'customer_id' => null,
                'customer_name' => 'CF',
                'sale_code' => $this->generateSaleCode(),
            ],
            'open_cash_session' => $openCashSession ? [
                'id' => $openCashSession->id,
                'register' => $openCashSession->register?->name,
                'branch' => $openCashSession->register?->branch_name,
                'opened_at' => optional($openCashSession->opened_at)->format('Y-m-d H:i:s'),
                'opening_amount' => (float) $openCashSession->opening_amount,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sale_code' => ['nullable', 'string', 'max:50', Rule::unique('sales', 'sale_code')],
            'customer_id' => ['nullable', 'integer', 'exists:clients,id'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_presentation_id' => ['nullable', 'integer', 'exists:product_presentations,id'],
            'items.*.presentation_name' => ['nullable', 'string', 'max:255'],
            'items.*.presentation_factor' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', Rule::in(['cash', 'card', 'transfer'])],
            'payments.*.bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();

        $session = CashSession::query()
            ->with('register:id,name,branch_name')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $session) {
            return back()->withErrors([
                'sale' => 'Debes abrir caja antes de registrar ventas.',
            ]);
        }

        $saleTicket = null;

        try {
            DB::transaction(function () use ($data, $user, $session, &$saleTicket): void {
                $items = collect($data['items']);
                $productIds = $items->pluck('product_id')->unique()->values();

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $total = 0.0;
                $totalUnits = 0;
                $normalizedItems = [];

                foreach ($items as $item) {
                    $product = $products->get((int) $item['product_id']);

                    if (! $product || ! $product->is_active) {
                        throw new \RuntimeException('Uno de los productos no está disponible.');
                    }

                    $factor = max(1, (int) $item['presentation_factor']);
                    $presentationQuantity = max(1, (int) $item['quantity']);
                    $unitsToDecrement = $factor * $presentationQuantity;
                    $lineTotal = round(((float) $item['price']) * $presentationQuantity, 2);

                    if ((int) $product->stock < $unitsToDecrement) {
                        throw new \RuntimeException("Stock insuficiente para {$product->name}.");
                    }

                    $total += $lineTotal;
                    $totalUnits += $unitsToDecrement;

                    $unitPrice = $factor > 0 ? ((float) $item['price']) / $factor : (float) $item['price'];
                    $discount = max(0, ((float) $product->price - $unitPrice) * $unitsToDecrement);

                    $normalizedItems[] = [
                        'product' => $product,
                        'product_presentation_id' => $item['product_presentation_id'] ?? null,
                        'presentation_name' => $item['presentation_name'] ?? $product->unit_label ?? 'Unidad',
                        'presentation_factor' => $factor,
                        'presentation_price' => (float) $item['price'],
                        'presentation_quantity' => $presentationQuantity,
                        'quantity' => $unitsToDecrement,
                        'unit_price' => $unitPrice,
                        'original_price' => (float) $product->price,
                        'discount_amount' => $discount,
                        'note' => $item['note'] ?? null,
                    ];
                }

                $payments = collect($data['payments'] ?? []);
                if ($payments->isEmpty()) {
                    $payments = collect([[
                        'method' => 'cash',
                        'bank_account_id' => null,
                        'amount' => $total,
                    ]]);
                }

                foreach ($payments as $payment) {
                    if (($payment['method'] ?? null) === 'transfer' && empty($payment['bank_account_id'])) {
                        throw new \RuntimeException('Selecciona la cuenta bancaria para cada pago por transferencia.');
                    }
                }

                $transferAccountIds = $payments
                    ->filter(fn (array $payment) => ($payment['method'] ?? null) === 'transfer')
                    ->pluck('bank_account_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($transferAccountIds->isNotEmpty()) {
                    $activeTransferAccounts = BankAccount::query()
                        ->whereIn('id', $transferAccountIds)
                        ->where('is_active', true)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    foreach ($transferAccountIds as $accountId) {
                        if (! in_array($accountId, $activeTransferAccounts, true)) {
                            throw new \RuntimeException('Una de las cuentas bancarias seleccionadas no está activa.');
                        }
                    }
                }

                $paidTotal = round((float) $payments->sum(fn (array $payment) => (float) $payment['amount']), 2);
                if (abs($paidTotal - round($total, 2)) > 0.01) {
                    throw new \RuntimeException('La suma de pagos debe ser igual al total de la venta.');
                }

                $clientId = isset($data['customer_id']) ? (int) $data['customer_id'] : null;
                $client = $clientId ? Client::query()->find($clientId) : null;
                if ($client && ! $client->is_active) {
                    throw new \RuntimeException('El cliente seleccionado está inactivo.');
                }

                $resolvedCustomerName = $client?->name
                    ?: (trim((string) ($data['customer_name'] ?? '')) !== '' ? trim((string) $data['customer_name']) : 'CF');

                $sale = Sale::create([
                    'sale_code' => trim((string) ($data['sale_code'] ?? '')) !== '' ? trim((string) $data['sale_code']) : $this->generateSaleCode(),
                    'user_id' => $user->id,
                    'customer_id' => $client?->id,
                    'customer_name' => $resolvedCustomerName,
                    'cash_session_id' => $session->id,
                    'items_count' => $totalUnits,
                    'total' => round($total, 2),
                ]);

                foreach ($normalizedItems as $item) {
                    /** @var Product $product */
                    $product = $item['product'];

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'product_presentation_id' => $item['product_presentation_id'],
                        'presentation_name' => $item['presentation_name'],
                        'presentation_factor' => $item['presentation_factor'],
                        'presentation_price' => $item['presentation_price'],
                        'presentation_quantity' => $item['presentation_quantity'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'original_price' => $item['original_price'],
                        'discount_amount' => $item['discount_amount'],
                        'note' => $item['note'],
                    ]);

                    $product->decrement('stock', $item['quantity']);

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'note' => 'Venta ' . $sale->sale_code,
                    ]);
                }

                foreach ($payments as $payment) {
                    $method = (string) $payment['method'];
                    $amount = round((float) $payment['amount'], 2);

                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'cash_session_id' => $session->id,
                        'user_id' => $user->id,
                        'method' => $method,
                        'bank_account_id' => isset($payment['bank_account_id']) ? (int) $payment['bank_account_id'] : null,
                        'amount' => $amount,
                    ]);

                    if ($method === 'cash') {
                        CashMovement::create([
                            'cash_session_id' => $session->id,
                            'user_id' => $user->id,
                            'type' => 'sale',
                            'method' => 'cash',
                            'amount' => $amount,
                            'note' => 'Venta ' . $sale->sale_code,
                            'meta' => ['sale_id' => $sale->id],
                        ]);
                    }
                }

                session()->flash('success', [
                    'title' => 'Venta registrada',
                    'description' => "Código {$sale->sale_code} · Cliente {$sale->customer_name} · Total Q" . number_format((float) $sale->total, 2),
                ]);

                $saleTicket = [
                    'sale_code' => $sale->sale_code,
                    'created_at' => optional($sale->created_at)->format('Y-m-d H:i:s'),
                    'customer_name' => $sale->customer_name ?: 'CF',
                    'cashier_name' => $user->name,
                    'branch_name' => $session->register?->branch_name ?? 'Sucursal',
                    'register_name' => $session->register?->name ?? 'Caja',
                    'items_count' => (int) $sale->items_count,
                    'total' => round((float) $sale->total, 2),
                    'items' => collect($normalizedItems)->map(fn (array $item) => [
                        'name' => $item['product']->name,
                        'presentation_name' => $item['presentation_name'],
                        'quantity' => (int) $item['presentation_quantity'],
                        'price' => round((float) $item['presentation_price'], 2),
                        'line_total' => round((float) $item['presentation_price'] * (int) $item['presentation_quantity'], 2),
                    ])->values(),
                    'payments' => $payments->map(fn (array $payment) => [
                        'method' => $this->paymentMethodLabel((string) $payment['method']),
                        'amount' => round((float) $payment['amount'], 2),
                    ])->values(),
                ];
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        if (is_array($saleTicket)) {
            session()->flash('sale_ticket', $saleTicket);
        }

        return back();
    }

    private function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            default => ucfirst($method),
        };
    }

    private function generateSaleCode(): string
    {
        $prefix = 'V' . now()->format('Ymd');
        $last = Sale::query()
            ->where('sale_code', 'like', $prefix . '-%')
            ->orderByDesc('sale_code')
            ->value('sale_code');

        $next = 1;
        if (is_string($last) && preg_match('/-(\d{4})$/', $last, $match) === 1) {
            $next = ((int) $match[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }
}
