<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $q        = trim((string) $request->query('q', ''));
        $today    = now()->toDateString();
        $dateFrom = $request->query('date_from', $today);
        $dateTo   = $request->query('date_to', $today);
        $sellerId = $request->query('seller_id');

        $baseQuery = function () use ($q, $dateFrom, $dateTo, $sellerId) {
            return Sale::query()
                ->when($q !== '', function ($builder) use ($q) {
                    $builder->where(function ($inner) use ($q) {
                        $inner->where('sale_code', 'like', "%{$q}%")
                            ->orWhere('customer_name', 'like', "%{$q}%")
                            ->orWhereHas('items', fn ($items) => $items->whereHas(
                                'product',
                                fn ($p) => $p->where('name', 'like', "%{$q}%")
                                             ->orWhere('sku', 'like', "%{$q}%")
                            ));
                    });
                })
                ->when($dateFrom, fn ($b) => $b->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo,   fn ($b) => $b->whereDate('created_at', '<=', $dateTo))
                ->when($sellerId, fn ($b) => $b->where('user_id', $sellerId));
        };

        $sales = $baseQuery()
            ->with(['items.product', 'seller', 'payments'])
            ->latest()
            ->paginate(15)
            ->appends($request->query())
            ->through(fn (Sale $sale) => [
                'id'              => $sale->id,
                'sale_code'       => $sale->sale_code,
                'customer_name'   => $sale->customer_name ?: 'CF',
                'total'           => (float) $sale->total,
                'items_count'     => $sale->items_count,
                'seller'          => $sale->seller?->name,
                'created_at'      => $sale->created_at->toDateTimeString(),
                'payment_methods' => $sale->payments->pluck('method')->unique()->values(),
                'discount'        => (float) $sale->items->sum('discount_amount'),
                'profit'          => (float) $sale->items->sum(
                    fn ($i) => ($i->unit_price - $i->unit_cost) * $i->quantity
                ),
                'items' => $sale->items->map(fn ($item) => [
                    'id'               => $item->id,
                    'product'          => $item->product?->name,
                    'sku'              => $item->product?->sku,
                    'quantity'         => $item->quantity,
                    'unit_price'       => (float) $item->unit_price,
                    'original_price'   => (float) $item->original_price,
                    'discount_amount'  => (float) $item->discount_amount,
                    'presentation_name'=> $item->presentation_name,
                ])->values()->all(),
            ]);

        // Summary totals — separate query for accuracy across all pages
        $summaryTotals = $baseQuery()
            ->selectRaw('count(*) as sales_count, sum(total) as revenue')
            ->first();

        $itemTotals = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('sales.sale_code', 'like', "%{$q}%")
                        ->orWhere('sales.customer_name', 'like', "%{$q}%")
                        ->orWhereHas('product', fn ($p) =>
                            $p->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")
                        );
                });
            })
            ->when($dateFrom, fn ($b) => $b->whereDate('sales.created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($b) => $b->whereDate('sales.created_at', '<=', $dateTo))
            ->when($sellerId, fn ($b) => $b->where('sales.user_id', $sellerId))
            ->selectRaw('sum(sale_items.discount_amount) as discounts')
            ->selectRaw('sum(sale_items.quantity * (sale_items.unit_price - sale_items.unit_cost)) as profit')
            ->first();

        $sellers = User::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('sales.view'))
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->values();

        return Inertia::render('Sales/Index', [
            'sales'   => $sales,
            'filters' => [
                'q'         => $q,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'seller_id' => $sellerId,
            ],
            'summary' => [
                'revenue'     => (float) ($summaryTotals->revenue   ?? 0),
                'discounts'   => (float) ($itemTotals->discounts     ?? 0),
                'profit'      => (float) ($itemTotals->profit        ?? 0),
                'sales_count' => (int)   ($summaryTotals->sales_count ?? 0),
            ],
            'sellers' => $sellers,
        ]);
    }
}
