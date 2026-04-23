<?php

namespace App\Services\Restaurant;

use App\Models\BankAccount;
use App\Models\BankPosTerminal;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantAccount;
use App\Models\RestaurantAccountItem;
use App\Models\RestaurantDeliveryRider;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RestaurantPosViewService
{
    public function buildViewData(Request $request): array
    {
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
            ->with([
                'recipe' => function ($query) {
                    $query->with([
                        'items' => function ($itemsQuery) {
                            $itemsQuery->with('ingredient:id,stock');
                        },
                    ]);
                },
            ])
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'stock', 'unit_label', 'photo']);

        $availableProducts = $products
            ->filter(function (Product $product): bool {
                $recipe = $product->recipe;
                if ($recipe && $recipe->is_active && $recipe->items->isNotEmpty()) {
                    $possibleByRecipe = null;

                    foreach ($recipe->items as $recipeItem) {
                        $required = max(1, (int) $recipeItem->quantity);
                        $stock = max(0, (int) ($recipeItem->ingredient?->stock ?? 0));
                        $possible = intdiv($stock, $required);
                        $possibleByRecipe = $possibleByRecipe === null ? $possible : min($possibleByRecipe, $possible);

                        if ($possibleByRecipe < 1) {
                            return false;
                        }
                    }

                    return (int) ($possibleByRecipe ?? 0) > 0;
                }

                return (int) $product->stock > 0;
            })
            ->take(60)
            ->values();

        $openCashSession = CashSession::query()
            ->with('register:id,name,branch_name')
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get(['id', 'bank_name', 'account_name', 'currency']);

        $cardPosTerminals = BankPosTerminal::query()
            ->with('bankAccount:id,bank_name,account_name,currency,is_active')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (BankPosTerminal $terminal) => (bool) $terminal->bankAccount?->is_active)
            ->values();

        $deliveryRiders = RestaurantDeliveryRider::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'is_available']);

        $tables = RestaurantTable::query()
            ->with(['accounts' => function ($query) {
                $query->where('status', 'open')
                    ->with('deliveryRider:id,name')
                    ->with(['items' => function ($itemsQuery) {
                        $itemsQuery->with('product:id,name,photo')
                            ->select([
                                'id',
                                'restaurant_account_id',
                                'product_id',
                                'quantity',
                                'unit_price',
                                'note',
                                'kitchen_status',
                                'created_at',
                            ])
                            ->orderBy('id');
                    }])
                    ->orderBy('opened_at');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'filters' => [
                'q' => $q,
                'category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : null,
            ],
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
            'products' => $availableProducts->map(fn (Product $product) => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'stock' => (int) $product->stock,
                'unit_label' => $product->unit_label ?: 'Unidad',
                'photo_url' => $this->productPhotoUrl($product->photo),
            ])->values(),
            'tables' => $tables->map(function (RestaurantTable $table) {
                $accounts = $table->accounts->map(function (RestaurantAccount $account) {
                    $itemsCount = (int) $account->items->sum('quantity');
                    $activeItems = $account->items->where('kitchen_status', '!=', 'canceled');
                    $servedItems = $activeItems->where('kitchen_status', 'served');
                    $total = (float) $activeItems->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity);
                    $servedTotal = (float) $servedItems->sum(fn (RestaurantAccountItem $item) => (float) $item->unit_price * (int) $item->quantity);

                    return [
                        'id' => $account->id,
                        'split_type' => $account->split_type,
                        'label' => $account->label ?: ('Cuenta #' . $account->id),
                        'opened_at' => optional($account->opened_at)->format('Y-m-d H:i:s'),
                        'delivery_rider_id' => $account->delivery_rider_id ? (int) $account->delivery_rider_id : null,
                        'delivery_assigned_at' => optional($account->delivery_assigned_at)->format('Y-m-d H:i:s'),
                        'delivery_rider_name' => $account->deliveryRider?->name,
                        'items_count' => $itemsCount,
                        'total' => round($total, 2),
                        'served_total' => round($servedTotal, 2),
                        'draft_items' => $account->items->where('kitchen_status', 'draft')->count(),
                        'pending_items' => $account->items->where('kitchen_status', 'pending')->count(),
                        'preparing_items' => $account->items->where('kitchen_status', 'preparing')->count(),
                        'ready_items' => $account->items->where('kitchen_status', 'ready')->count(),
                        'orders_count' => $account->items->whereNotNull('restaurant_order_id')->pluck('restaurant_order_id')->unique()->count(),
                        'can_settle' => $account->items->whereIn('kitchen_status', ['draft', 'pending', 'preparing', 'ready'])->count() === 0
                            && $account->items->where('kitchen_status', 'served')->count() > 0,
                        'items' => $activeItems->map(fn (RestaurantAccountItem $item) => [
                            'id' => $item->id,
                            'quantity' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'note' => $item->note,
                            'kitchen_status' => $item->kitchen_status,
                            'product_name' => $item->product?->name ?: 'Producto',
                            'product_photo_url' => $this->productPhotoUrl($item->product?->photo),
                            'line_total' => round(((float) $item->unit_price) * (int) $item->quantity, 2),
                            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
                        ])->values(),
                    ];
                })->values();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'code' => $table->code,
                    'sort_order' => (int) $table->sort_order,
                    'is_takeaway' => (bool) $table->is_takeaway,
                    'takeaway_service_type' => $table->takeaway_service_type,
                    'is_active' => (bool) $table->is_active,
                    'status' => $accounts->isEmpty() ? 'free' : 'occupied',
                    'accounts' => $accounts,
                ];
            })->values(),
            'bank_accounts' => $bankAccounts->map(fn (BankAccount $account) => [
                'id' => $account->id,
                'label' => trim($account->bank_name . ' · ' . $account->account_name),
                'currency' => $account->currency,
            ])->values(),
            'card_pos_terminals' => $cardPosTerminals->map(fn (BankPosTerminal $terminal) => [
                'id' => $terminal->id,
                'name' => $terminal->name,
                'commission_percent' => (float) $terminal->commission_percent,
                'bank_account_id' => $terminal->bank_account_id,
                'bank_account_label' => trim(($terminal->bankAccount?->bank_name ?? '') . ' · ' . ($terminal->bankAccount?->account_name ?? '')),
                'currency' => $terminal->bankAccount?->currency ?? 'GTQ',
            ])->values(),
            'delivery_riders' => $deliveryRiders->map(fn (RestaurantDeliveryRider $rider) => [
                'id' => $rider->id,
                'name' => $rider->name,
                'phone' => $rider->phone,
                'is_available' => (bool) $rider->is_available,
            ])->values(),
            'open_cash_session' => $openCashSession ? [
                'id' => $openCashSession->id,
                'register' => $openCashSession->register?->name,
                'branch' => $openCashSession->register?->branch_name,
                'opened_at' => optional($openCashSession->opened_at)->format('Y-m-d H:i:s'),
                'opening_amount' => (float) $openCashSession->opening_amount,
            ] : null,
        ];
    }

    public function productPhotoUrl(?string $photo): ?string
    {
        $path = ltrim((string) $photo, '/');
        if ($path === '') {
            return null;
        }

        return asset(Str::startsWith($path, 'products/') ? $path : 'products/' . $path);
    }
}
