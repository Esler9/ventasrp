<?php

namespace App\Services\Restaurant;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RestaurantAccountItem;
use App\Models\RestaurantItemConsumption;
use Illuminate\Validation\ValidationException;

class RestaurantInventoryService
{
    public function consumeForServedItem(RestaurantAccountItem $item, int $userId): void
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

    public function reverseConsumptionsForCanceledItem(RestaurantAccountItem $item, int $userId): void
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
}
