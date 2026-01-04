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
            'product_presentation_id' => ['nullable', 'integer', 'exists:product_presentations,id'],
            'presentation_name' => ['nullable', 'string', 'max:255'],
            'presentation_factor' => ['required', 'integer', 'min:1'],
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

                $presentationFactor = max(1, (int) $data['presentation_factor']);
                $presentationQuantity = max(1, (int) $data['quantity']);
                $unitsToDecrement = $presentationFactor * $presentationQuantity;

                if ($product->stock < $unitsToDecrement) {
                    throw new \RuntimeException("Stock insuficiente para {$product->name} (disponible {$product->stock}).");
                }

                $product->decrement('stock', $unitsToDecrement);

                $sale = Sale::create([
                    'user_id' => $request->user()->id,
                    'items_count' => $unitsToDecrement,
                    'total' => $data['price'] * $presentationQuantity,
                ]);

                $unitPrice = $presentationFactor > 0 ? (float) $data['price'] / $presentationFactor : (float) $data['price'];
                $discount = max(0, ((float) $product->price - $unitPrice) * $unitsToDecrement);
                $presentationName = $data['presentation_name'] ?? $product->unit_label ?? 'Unidad';

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_presentation_id' => $data['product_presentation_id'] ?? null,
                    'presentation_name' => $presentationName,
                    'presentation_factor' => $presentationFactor,
                    'presentation_price' => $data['price'],
                    'presentation_quantity' => $presentationQuantity,
                    'quantity' => $unitsToDecrement,
                    'unit_price' => $unitPrice,
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
