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
        $today = now()->toDateString();
        $dateFrom = $request->query('date_from', $today);
        $dateTo = $request->query('date_to', $today);
        $sellerId = $request->query('seller_id');

        $query = SaleItem::query()
            ->with(['product', 'sale.seller'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($query) use ($q) {
                    $query->whereHas('product', function ($product) use ($q) {
                        $product->where('name', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%");
                    })->orWhereHas('sale', function ($sale) use ($q) {
                        $sale->where('sale_code', 'like', "%{$q}%")
                            ->orWhere('customer_name', 'like', "%{$q}%");
                    });
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
                'sale_code' => $item->sale?->sale_code,
                'customer_name' => $item->sale?->customer_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'original_price' => $item->original_price,
                'discount_amount' => $item->discount_amount,
                'seller' => $item->sale?->seller?->name,
                'created_at' => optional($item->created_at)->toDateTimeString(),
            ]);

        $summaryQuery = (clone $query)->reorder();

        $totals = $summaryQuery
            ->selectRaw('sum(quantity * unit_price) as revenue')
            ->selectRaw('sum(discount_amount) as discounts')
            ->selectRaw('sum(quantity) as units')
            ->selectRaw('count(*) as line_count')
            ->first();

        $sellers = User::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('sales.view'))
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values();

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
                'lines' => (int) $totals->line_count,
            ],
            'sellers' => $sellers,
        ]);
    }
}
