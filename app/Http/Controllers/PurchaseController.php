<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $q          = trim((string) $request->query('q', ''));
        $status     = (string) $request->query('status', '');
        $supplierId = (int) $request->query('supplier_id', 0);
        $dateFrom   = (string) $request->query('date_from', '');
        $dateTo     = (string) $request->query('date_to', '');

        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'user:id,name'])
            ->withCount('items')
            ->when($q !== '', fn ($b) => $b->where(function ($sub) use ($q) {
                $sub->where('purchase_code', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->when($status !== '', fn ($b) => $b->where('status', $status))
            ->when($supplierId > 0, fn ($b) => $b->where('supplier_id', $supplierId))
            ->when($dateFrom !== '', fn ($b) => $b->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($b) => $b->whereDate('purchase_date', '<=', $dateTo))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn ($p) => [
                'id'              => $p->id,
                'purchase_code'   => $p->purchase_code,
                'supplier_id'     => $p->supplier_id,
                'supplier_name'   => $p->supplier?->name ?? '—',
                'user_name'       => $p->user?->name ?? '—',
                'status'          => $p->status,
                'purchase_date'   => $p->purchase_date?->toDateString(),
                'subtotal'        => (float) $p->subtotal,
                'discount'        => (float) $p->discount,
                'total'           => (float) $p->total,
                'payment_method'  => $p->payment_method,
                'items_count'     => $p->items_count,
            ]);

        $summary = Purchase::query()
            ->when($dateFrom !== '', fn ($b) => $b->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($b) => $b->whereDate('purchase_date', '<=', $dateTo))
            ->selectRaw("
                COUNT(*) as total_orders,
                COALESCE(SUM(CASE WHEN status = 'received' THEN total ELSE 0 END), 0) as total_received,
                COALESCE(SUM(CASE WHEN status = 'draft'    THEN total ELSE 0 END), 0) as total_draft
            ")
            ->first();

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->values();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'summary'   => [
                'total_orders'   => (int)   ($summary->total_orders   ?? 0),
                'total_received' => (float) ($summary->total_received ?? 0),
                'total_draft'    => (float) ($summary->total_draft    ?? 0),
            ],
            'filters' => [
                'q'           => $q,
                'status'      => $status,
                'supplier_id' => $supplierId ?: '',
                'date_from'   => $dateFrom,
                'date_to'     => $dateTo,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/Form', [
            'purchase'  => null,
            'suppliers' => $this->supplierOptions(),
            'products'  => $this->productOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePurchase($request);

        $receiveNow = (bool) ($data['receive_now'] ?? false);
        $purchase   = null;

        try {
            DB::transaction(function () use ($data, $receiveNow, $request, &$purchase): void {
                $purchase = $this->persistPurchase(null, $data, $receiveNow, $request->user()->id);
                if ($receiveNow) {
                    $this->applyToInventory($purchase, $request->user()->id);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['purchase' => $e->getMessage()]);
        }

        return redirect()->route('purchases.index')->with('success', [
            'title'       => $receiveNow ? 'Compra recibida' : 'Borrador guardado',
            'description' => "Código {$purchase->purchase_code} · Total Q" . number_format((float) $purchase->total, 2),
        ]);
    }

    public function edit(Purchase $purchase): Response
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.index')->withErrors([
                'purchase' => 'Solo se pueden editar compras en borrador.',
            ]);
        }

        return Inertia::render('Purchases/Form', [
            'purchase'  => $this->purchaseData($purchase),
            'suppliers' => $this->supplierOptions(),
            'products'  => $this->productOptions(),
        ]);
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Solo se pueden editar compras en borrador.']);
        }

        $data       = $this->validatePurchase($request);
        $receiveNow = (bool) ($data['receive_now'] ?? false);

        try {
            DB::transaction(function () use ($purchase, $data, $receiveNow, $request): void {
                $this->persistPurchase($purchase, $data, $receiveNow, $request->user()->id);
                if ($receiveNow) {
                    $this->applyToInventory($purchase, $request->user()->id);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['purchase' => $e->getMessage()]);
        }

        return redirect()->route('purchases.index')->with('success', [
            'title'       => $receiveNow ? 'Compra recibida' : 'Borrador actualizado',
            'description' => "Código {$purchase->purchase_code} · Total Q" . number_format((float) $purchase->total, 2),
        ]);
    }

    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Esta compra ya fue procesada.']);
        }

        try {
            DB::transaction(function () use ($purchase, $request): void {
                $this->applyToInventory($purchase, $request->user()->id);
                $purchase->update(['status' => 'received']);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['purchase' => $e->getMessage()]);
        }

        return redirect()->route('purchases.index')->with('success', [
            'title'       => 'Compra recibida',
            'description' => "Código {$purchase->purchase_code} · Stock y costos actualizados.",
        ]);
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Solo se pueden eliminar borradores.']);
        }

        $code = $purchase->purchase_code;
        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', [
            'title'       => 'Borrador eliminado',
            'description' => "Compra {$code} eliminada.",
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Aplica Costo Promedio Ponderado e incrementa stock al recibir una compra.
     *
     * CPP_nuevo = (stock_actual × costo_actual + qty × costo_compra)
     *              / (stock_actual + qty)
     */
    private function applyToInventory(Purchase $purchase, int $userId): void
    {
        $purchase->load('items');

        $productIds = $purchase->items->pluck('product_id')->unique()->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($purchase->items as $item) {
            /** @var Product|null $product */
            $product = $products->get($item->product_id);

            if (! $product) {
                throw new \RuntimeException("Producto ID {$item->product_id} no encontrado.");
            }

            $qty          = (int) $item->quantity;
            $purchaseCost = (float) $item->unit_cost;
            $stockBefore  = (int) $product->stock;

            // Calcula y asigna el nuevo CPP (sin guardar aún)
            $product->applyWeightedAverageCost($qty, $purchaseCost);
            $product->stock = $stockBefore + $qty;
            $product->save();

            InventoryMovement::create([
                'product_id'     => $product->id,
                'user_id'        => $userId,
                'type'           => 'purchase',
                'quantity'       => $qty,
                'unit_cost'      => $purchaseCost,
                'reference_type' => 'purchase',
                'reference_id'   => $purchase->id,
                'stock_before'   => $stockBefore,
                'stock_after'    => $product->stock,
                'note'           => 'Compra ' . $purchase->purchase_code,
            ]);
        }
    }

    /** Crea o actualiza un Purchase y sus items desde $data validado. */
    private function persistPurchase(?Purchase $existing, array $data, bool $receiveNow, int $userId): Purchase
    {
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $subtotal = collect($data['items'])->reduce(
            fn (float $carry, array $item) => $carry + round((float) $item['unit_cost'] * (int) $item['quantity'], 2),
            0.0
        );
        $total = max(0.0, round($subtotal - $discount, 2));

        $attrs = [
            'supplier_id'       => $data['supplier_id'] ?? null,
            'status'            => $receiveNow ? 'received' : 'draft',
            'purchase_date'     => $data['purchase_date'],
            'subtotal'          => $subtotal,
            'discount'          => $discount,
            'total'             => $total,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'notes'             => $data['notes'] ?? null,
        ];

        if ($existing) {
            $existing->update($attrs);
            $existing->items()->delete();
            $purchase = $existing;
        } else {
            $purchase = Purchase::create(array_merge($attrs, [
                'purchase_code' => $this->generateCode(),
                'user_id'       => $userId,
            ]));
        }

        foreach ($data['items'] as $item) {
            $qty      = (int) $item['quantity'];
            $unitCost = round((float) $item['unit_cost'], 4);
            $purchase->items()->create([
                'product_id' => (int) $item['product_id'],
                'quantity'   => $qty,
                'unit_cost'  => $unitCost,
                'subtotal'   => round($unitCost * $qty, 2),
                'note'       => $item['note'] ?? null,
            ]);
        }

        return $purchase;
    }

    private function validatePurchase(Request $request): array
    {
        return $request->validate([
            'supplier_id'       => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchase_date'     => ['required', 'date'],
            'payment_method'    => ['nullable', 'in:cash,card,transfer,credit'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'discount'          => ['nullable', 'numeric', 'min:0'],
            'receive_now'       => ['nullable', 'boolean'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.product_id'=> ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'  => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.note'      => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function generateCode(): string
    {
        $prefix = 'C' . now()->format('Ymd');
        $last   = Purchase::query()
            ->where('purchase_code', 'like', $prefix . '-%')
            ->orderByDesc('purchase_code')
            ->value('purchase_code');

        $next = 1;
        if (is_string($last) && preg_match('/-(\d{4})$/', $last, $m) === 1) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }

    private function purchaseData(Purchase $purchase): array
    {
        return [
            'id'                => $purchase->id,
            'purchase_code'     => $purchase->purchase_code,
            'supplier_id'       => $purchase->supplier_id,
            'purchase_date'     => $purchase->purchase_date?->toDateString(),
            'payment_method'    => $purchase->payment_method,
            'payment_reference' => $purchase->payment_reference,
            'notes'             => $purchase->notes,
            'discount'          => (float) $purchase->discount,
            'subtotal'          => (float) $purchase->subtotal,
            'total'             => (float) $purchase->total,
            'items'             => $purchase->items()
                ->with('product:id,name,sku,unit_label,cost_price,stock')
                ->get()
                ->map(fn ($item) => [
                    'id'                   => $item->id,
                    'product_id'           => $item->product_id,
                    'product_name'         => $item->product?->name,
                    'product_sku'          => $item->product?->sku,
                    'product_unit_label'   => $item->product?->unit_label ?? 'Unidad',
                    'product_current_cost' => (float) ($item->product?->cost_price ?? 0),
                    'product_stock'        => (int) ($item->product?->stock ?? 0),
                    'quantity'             => (int) $item->quantity,
                    'unit_cost'            => (float) $item->unit_cost,
                    'subtotal'             => (float) $item->subtotal,
                    'note'                 => $item->note,
                ])
                ->values(),
        ];
    }

    private function supplierOptions(): array
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'phone' => $s->phone])
            ->values()
            ->all();
    }

    private function productOptions(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit_label', 'cost_price', 'stock'])
            ->map(fn ($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'sku'        => $p->sku,
                'unit_label' => $p->unit_label ?? 'Unidad',
                'cost_price' => (float) $p->cost_price,
                'stock'      => (int) $p->stock,
            ])
            ->values()
            ->all();
    }
}
