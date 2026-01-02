<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to('/admin/point-of-sale');
    }

    return redirect()->route('filament.admin.auth.login');
});

Route::middleware(['web', 'auth'])->get('/pos', function (\Illuminate\Http\Request $request) {
    $q = trim((string) $request->query('q', ''));

    $products = Product::query()
        ->select(['id', 'name', 'sku', 'stock', 'price', 'expires_at'])
        ->where('is_active', true)
        ->when($q !== '', function ($builder) use ($q) {
            $builder->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        })
        ->orderBy('name')
        ->limit(20)
        ->get();

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
