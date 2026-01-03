<?php

use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if (method_exists($user, 'isSeller') && $user->isSeller()) {
            return redirect()->to('/pos');
        }

        return redirect()->to('/admin');
    }

    return redirect()->to('/login');
});

// Login propio con Inertia
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Redirecciones de compatibilidad
Route::get('/admin/login', fn () => redirect('/login'));
Route::get('/filament/login', fn () => redirect('/login'));

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

Route::middleware(['web', 'auth'])->post('/pos/sales', [PosController::class, 'store'])->name('pos.sales.store');

Route::prefix('admin')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('admin.dashboard');

    Route::get('/point-of-sale', fn () => redirect('/pos'))->name('admin.point-of-sale');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::match(['put', 'patch', 'post'], '/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');

    Route::post('/point-of-sale/sales', [PosController::class, 'store'])->name('admin.pos.sales');

    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::match(['put', 'patch', 'post'], '/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
});
