<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantAccount;
use App\Models\RestaurantAccountItem;
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
                        'pending_items' => $account->items->where('kitchen_status', 'pending')->count(),
                        'sent_items' => $account->items->where('kitchen_status', 'sent')->count(),
                        'preparing_items' => $account->items->where('kitchen_status', 'preparing')->count(),
                        'ready_items' => $account->items->where('kitchen_status', 'ready')->count(),
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
            'product_id' => $product->id,
            'added_by_user_id' => $request->user()->id,
            'quantity' => (int) $data['quantity'],
            'unit_price' => (float) $product->price,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'kitchen_status' => 'pending',
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

        $updated = RestaurantAccountItem::query()
            ->where('restaurant_account_id', $account->id)
            ->where('kitchen_status', 'pending')
            ->update([
                'kitchen_status' => 'sent',
                'sent_at' => now(),
            ]);

        if ($updated === 0) {
            return back()->withErrors(['restaurant' => 'No hay ítems pendientes para enviar a cocina.']);
        }

        return back()->with('success', [
            'title' => 'Orden enviada',
            'description' => "Se enviaron {$updated} ítems a cocina.",
        ]);
    }

    public function kitchen(Request $request): Response
    {
        $this->ensureRestaurantMode();

        $items = RestaurantAccountItem::query()
            ->with([
                'product:id,name',
                'account:id,restaurant_table_id,label,status',
                'account.table:id,name',
            ])
            ->whereIn('kitchen_status', ['sent', 'preparing', 'ready'])
            ->orderByRaw("CASE kitchen_status WHEN 'sent' THEN 1 WHEN 'preparing' THEN 2 WHEN 'ready' THEN 3 ELSE 4 END")
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('Pos/Kitchen', [
            'items' => $items->map(function (RestaurantAccountItem $item) {
                return [
                    'id' => $item->id,
                    'quantity' => (int) $item->quantity,
                    'note' => $item->note,
                    'kitchen_status' => $item->kitchen_status,
                    'sent_at' => optional($item->sent_at)->format('Y-m-d H:i:s'),
                    'started_at' => optional($item->started_at)->format('Y-m-d H:i:s'),
                    'ready_at' => optional($item->ready_at)->format('Y-m-d H:i:s'),
                    'product_name' => $item->product?->name ?: 'Producto',
                    'account_label' => $item->account?->label ?: ('Cuenta #' . $item->restaurant_account_id),
                    'table_name' => $item->account?->table?->name ?: 'Mesa',
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

        DB::transaction(function () use ($item, $data): void {
            $status = (string) $data['kitchen_status'];
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

            $item->update($updates);
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
}
