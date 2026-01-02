<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PosController;
use App\Models\Product;
use Illuminate\Support\Str;

if (! function_exists('handlePosSale')) {
    function handlePosSale(Request $request) {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($data, $request): void {
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
            return redirect()->back()->withErrors(['sale' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Venta registrada');
    }
}

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if (method_exists($user, 'isSeller') && $user->isSeller()) {
            return redirect()->to('/pos');
        }

        return redirect()->to('/admin');
    }

    return redirect()->route('filament.admin.auth.login');
});

Route::middleware(['web', 'auth'])->get('/pos', function (\Illuminate\Http\Request $request) {
    $q = trim((string) $request->query('q', ''));

    $products = Product::query()
        ->select(['id', 'name', 'sku', 'stock', 'price', 'expires_at', 'photo'])
        ->where('is_active', true)
        ->when($q !== '', function ($builder) use ($q) {
            $builder->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        })
        ->orderBy('name')
        ->limit(20)
        ->get()
        ->map(function ($product) {
            $path = ltrim((string) $product->photo, '/');

            if ($path === '') {
                $product->photo_url = null;
            } elseif (Str::startsWith($path, 'products/')) {
                $product->photo_url = asset($path);
            } else {
                $product->photo_url = asset('products/' . $path);
            }

            return $product;
        });

    return Inertia::render('Pos/Index', [
        'products' => $products,
        'filters' => [
            'q' => $q,
        ],
        'user' => [
            'name' => $request->user()->name,
            'role' => $request->user()->role,
        ],
    ]);
})->name('pos');

Route::middleware(['web', 'auth'])->get('/admin/point-of-sale', function (\Illuminate\Http\Request $request) {
    $q = trim((string) $request->query('q', ''));

    $products = Product::query()
        ->select(['id', 'name', 'sku', 'stock', 'price', 'expires_at', 'photo'])
        ->where('is_active', true)
        ->when($q !== '', function ($builder) use ($q) {
            $builder->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        })
        ->orderBy('name')
        ->limit(20)
        ->get()
        ->map(function ($product) {
            $path = ltrim((string) $product->photo, '/');

            if ($path === '') {
                $product->photo_url = null;
            } elseif (Str::startsWith($path, 'products/')) {
                $product->photo_url = asset($path);
            } else {
                $product->photo_url = asset('products/' . $path);
            }

            return $product;
        });

    return Inertia::render('Pos/Index', [
        'products' => $products,
        'filters' => [
            'q' => $q,
        ],
        'user' => [
            'name' => $request->user()->name,
            'role' => $request->user()->role,
        ],
    ]);
})->name('filament.admin.pages.point-of-sale');

Route::middleware(['web', 'auth'])->post('/pos/sales', [PosController::class, 'store'])->name('pos.sales.store');

Route::middleware(['web', 'auth'])->post('/admin/point-of-sale/sales', [PosController::class, 'store'])->name('filament.admin.pages.point-of-sale.sales');
