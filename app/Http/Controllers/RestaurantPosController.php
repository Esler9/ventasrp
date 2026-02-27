<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\RestaurantAccount;
use App\Models\RestaurantAccountItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'stock', 'unit_label']);

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
            'products' => $products->map(fn (Product $product) => [
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
                    $total = (float) $account->items->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity);

                    return [
                        'id' => $account->id,
                        'split_type' => $account->split_type,
                        'label' => $account->label ?: ('Cuenta #' . $account->id),
                        'opened_at' => optional($account->opened_at)->format('Y-m-d H:i:s'),
                        'items_count' => $itemsCount,
                        'total' => round($total, 2),
                        'draft_items' => $account->items->where('kitchen_status', 'draft')->count(),
                        'pending_items' => $account->items->where('kitchen_status', 'pending')->count(),
                        'preparing_items' => $account->items->where('kitchen_status', 'preparing')->count(),
                        'ready_items' => $account->items->where('kitchen_status', 'ready')->count(),
                        'orders_count' => $account->items->whereNotNull('restaurant_order_id')->pluck('restaurant_order_id')->unique()->count(),
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

        if ($statuses->isEmpty() || $statuses->every(fn (string $status) => $status === 'served')) {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return;
        }

        if ($statuses->contains('ready') && ! $statuses->contains('pending') && ! $statuses->contains('preparing')) {
            $order->update([
                'status' => 'ready',
                'completed_at' => null,
            ]);

            return;
        }

        if ($statuses->contains('preparing') || ($statuses->contains('ready') && $statuses->contains('pending'))) {
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
                    throw new \RuntimeException("Stock insuficiente de {$name} para entregar esta orden.");
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
            }

            return;
        }

        $product = Product::query()->lockForUpdate()->find((int) $item->product_id);
        if (! $product || (int) $product->stock < $servedQuantity) {
            throw new \RuntimeException('Stock insuficiente del producto para marcar como entregado.');
        }

        $product->decrement('stock', $servedQuantity);

        InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => 'restaurant_served',
            'quantity' => -$servedQuantity,
            'note' => 'Entrega restaurante · Orden #' . ($item->order?->id ?: $item->restaurant_order_id),
        ]);
    }
}
