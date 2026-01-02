<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'price', 'stock', 'expires_at', 'is_active', 'photo'])
            ->where('is_active', true)
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->through(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock' => $product->stock,
                'expires_at' => optional($product->expires_at)->toDateString(),
                'is_active' => $product->is_active,
                'photo' => $product->photo,
                'photo_url' => $this->photoUrl($product->photo),
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'product' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('', 'public_products');
            $data['photo'] = 'products/' . ltrim($path, '/');
        }

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Producto creado');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock' => $product->stock,
                'expires_at' => optional($product->expires_at)->toDateString(),
                'is_active' => $product->is_active,
                'photo' => $product->photo,
                'photo_url' => $this->photoUrl($product->photo),
            ],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('', 'public_products');
            $data['photo'] = 'products/' . ltrim($path, '/');
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Producto actualizado');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado');
    }

    private function photoUrl(?string $path): ?string
    {
        $path = ltrim((string) $path, '/');

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, 'products/')) {
            return asset($path);
        }

        return asset('products/' . $path);
    }
}
