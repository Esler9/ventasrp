<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $items = SaleItem::query()
            ->with(['product', 'sale.seller'])
            ->latest()
            ->paginate(20)
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

        return Inertia::render('Sales/Index', [
            'items' => $items,
        ]);
    }
}
