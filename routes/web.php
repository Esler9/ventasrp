<?php

use App\Http\Controllers\Admin\CashController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['web', 'auth', 'permission:pos.view'])->get('/pos', [PosController::class, 'index'])->name('pos');

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

    Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
    Route::match(['put', 'patch', 'post'], '/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.edit')->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');

    Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:clients.view')->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:clients.create')->name('clients.store');
    Route::match(['put', 'patch', 'post'], '/clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.edit')->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('permission:clients.delete')->name('clients.destroy');

    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('sales.index');

    Route::post('/point-of-sale/sales', [PosController::class, 'store'])->middleware('permission:pos.create_sale')->name('admin.pos.sales');

    Route::get('/cash', [CashController::class, 'index'])->middleware('permission:cash.view')->name('admin.cash.index');
    Route::post('/cash/open', [CashController::class, 'open'])->middleware('permission:cash.open')->name('admin.cash.open');
    Route::post('/cash/movements', [CashController::class, 'storeMovement'])->middleware('permission:cash.movements')->name('admin.cash.movements.store');
    Route::post('/cash/close', [CashController::class, 'close'])->middleware('permission:cash.close')->name('admin.cash.close');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('admin.reports.index');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage')->name('admin.users.store');
    Route::match(['put', 'patch', 'post'], '/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage')->name('admin.users.update');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware('permission:settings.view')->name('admin.settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.update')->name('admin.settings.update');
});
