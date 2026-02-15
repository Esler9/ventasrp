<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to(auth()->user()->defaultHomeRoute());
    }

    return redirect()->to('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/admin/login', fn () => redirect('/login'));

Route::middleware(['web', 'auth', 'permission:pos.view'])->get('/pos', function (Request $request) {
    $q = trim((string) $request->query('q', ''));

    $products = Product::query()
        ->select(['id', 'name', 'unit_label', 'description', 'sku', 'stock', 'price', 'expires_at', 'photo'])
        ->where('is_active', true)
        ->with(['presentations' => function ($query) {
            $query->where('is_active', true)
                ->orderBy('factor')
                ->select(['id', 'product_id', 'name', 'factor', 'price', 'is_active']);
        }])
        ->when($q !== '', function ($builder) use ($q) {
            $builder->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
            $builder->orderByRaw(
                'CASE WHEN name LIKE ? THEN 0 WHEN sku LIKE ? THEN 1 WHEN description LIKE ? THEN 2 ELSE 3 END',
                ["%{$q}%", "%{$q}%", "%{$q}%"]
            );
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

            $product->unit_label = $product->unit_label ?? 'Unidad';

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

Route::middleware(['web', 'auth', 'permission:pos.create_sale'])->post('/pos/sales', [PosController::class, 'store'])->name('pos.sales.store');

Route::prefix('admin')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('admin.dashboard');

    Route::get('/point-of-sale', fn () => redirect('/pos'))
        ->middleware('permission:pos.view')
        ->name('admin.point-of-sale');

    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:products.edit')->name('products.edit');
    Route::match(['put', 'patch', 'post'], '/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.edit')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');

    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('sales.index');

    Route::post('/point-of-sale/sales', [PosController::class, 'store'])->middleware('permission:pos.create_sale')->name('admin.pos.sales');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage')->name('admin.users.store');
    Route::match(['put', 'patch', 'post'], '/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage')->name('admin.users.update');
});
