<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $sellerId = $request->query('seller_id');

        $query = SaleItem::query()
            ->with(['product', 'sale.seller'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->whereHas('product', function ($product) use ($q) {
                    $product->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom, function ($builder) use ($dateFrom) {
                $builder->whereHas('sale', fn ($sale) => $sale->whereDate('created_at', '>=', $dateFrom));
            })
            ->when($dateTo, function ($builder) use ($dateTo) {
                $builder->whereHas('sale', fn ($sale) => $sale->whereDate('created_at', '<=', $dateTo));
            })
            ->when($sellerId, function ($builder) use ($sellerId) {
                $builder->whereHas('sale', fn ($sale) => $sale->where('user_id', $sellerId));
            })
            ->latest();

        $items = $query
            ->paginate(20)
            ->appends($request->query())
            ->through(fn ($item) => [
                'id' => $item->id,
                'product' => $item->product?->name,
                'sku' => $item->product?->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'original_price' => $item->original_price,
                'discount_amount' => $item->discount_amount,
                'seller' => $item->sale?->seller?->name,
                'created_at' => optional($item->created_at)->toDateTimeString(),
            ]);

        $totals = (clone $query)
            ->selectRaw('sum(quantity * unit_price) as revenue')
            ->selectRaw('sum(discount_amount) as discounts')
            ->selectRaw('sum(quantity) as units')
            ->selectRaw('count(*) as lines')
            ->first();

        $sellers = User::query()
            ->whereIn('role', ['admin', 'seller'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Sales/Index', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'seller_id' => $sellerId,
            ],
            'summary' => [
                'revenue' => (float) $totals->revenue,
                'discounts' => (float) $totals->discounts,
                'units' => (int) $totals->units,
                'lines' => (int) $totals->lines,
            ],
            'sellers' => $sellers,
        ]);
    }
}
