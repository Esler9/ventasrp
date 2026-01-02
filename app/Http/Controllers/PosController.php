<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($data, $request): void {
                /** @var Product|null $product */
                $product = Product::whereKey($data['product_id'])->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    throw new \RuntimeException('Producto no disponible.');
                }

                if ($product->stock < $data['quantity']) {
                    throw new \RuntimeException("Stock insuficiente para {$product->name} (disponible {$product->stock}).");
                }

                $product->decrement('stock', $data['quantity']);

                $sale = Sale::create([
                    'user_id' => $request->user()->id,
                    'items_count' => $data['quantity'],
                    'total' => $data['price'] * $data['quantity'],
                ]);

                $discount = max(0, ((float) $product->price - (float) $data['price']) * $data['quantity']);

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['price'],
                    'original_price' => $product->price,
                    'discount_amount' => $discount,
                    'note' => $data['note'] ?? null,
                ]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'sale',
                    'quantity' => -$data['quantity'],
                    'note' => $data['note'] ?? 'Venta rápida',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['sale' => $e->getMessage()]);
        }

        return back()->with('success', 'Venta registrada');
    }
}
