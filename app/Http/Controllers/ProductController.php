<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\ProductRecipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->select(['id', 'category_id', 'name', 'unit_label', 'description', 'sku', 'price', 'cost_price', 'stock', 'stock_alert', 'expires_at', 'is_active', 'photo'])
            ->where('is_active', true)
            ->with('category:id,name')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$q}%"));
                });
                $builder->orderByRaw(
                    "CASE WHEN name LIKE ? THEN 0 WHEN sku LIKE ? THEN 1 WHEN description LIKE ? THEN 2 ELSE 3 END",
                    ["%{$q}%", "%{$q}%", "%{$q}%"]
                );
            })
            ->orderBy('name')
            ->paginate(10)
            ->through(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'unit_label' => $product->unit_label,
                'description' => $product->description,
                'sku' => $product->sku,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'stock' => $product->stock,
                'stock_alert' => $product->stock_alert,
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
            'categories' => $this->categoryOptions(),
            'ingredient_products' => $this->ingredientOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_alert' => ['required', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image'],
            'presentations' => ['array'],
            'presentations.*.name' => ['required', 'string', 'max:255'],
            'presentations.*.factor' => ['required', 'integer', 'min:1'],
            'presentations.*.price' => ['required', 'numeric', 'min:0'],
            'presentations.*.is_active' => ['required', 'boolean'],
            'recipe_enabled' => ['nullable', 'boolean'],
            'recipe_items' => ['array'],
            'recipe_items.*.ingredient_product_id' => ['required_with:recipe_items', 'integer', 'exists:products,id'],
            'recipe_items.*.quantity' => ['required_with:recipe_items', 'integer', 'min:1'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('', 'public_products');
            $data['photo'] = 'products/' . ltrim($path, '/');
        }

        $this->ensureSku($data);
        $presentations = $data['presentations'] ?? [];
        $recipeEnabled = (bool) ($data['recipe_enabled'] ?? false);
        $recipeItems = $data['recipe_items'] ?? [];
        unset($data['presentations']);
        unset($data['recipe_enabled'], $data['recipe_items']);

        /** @var Product $product */
        $product = DB::transaction(function () use ($data, $presentations, $recipeEnabled, $recipeItems) {
            /** @var Product $product */
            $product = Product::create($data);
            $this->syncPresentations($product, $presentations);
            $this->syncRecipe($product, $recipeEnabled, $recipeItems);

            return $product;
        });

        return redirect()
            ->route('products.index')
            ->with('success', [
                'title' => 'Producto creado',
                'description' => "{$product->name} guardado. Stock: {$product->stock} {$product->unit_label}.",
            ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'product' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'unit_label' => $product->unit_label,
                'description' => $product->description,
                'sku' => $product->sku,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'stock' => $product->stock,
                'stock_alert' => $product->stock_alert,
                'expires_at' => optional($product->expires_at)->toDateString(),
                'is_active' => $product->is_active,
                'photo' => $product->photo,
                'photo_url' => $this->photoUrl($product->photo),
                'presentations' => $product->presentations()->orderBy('factor')->get([
                    'id',
                    'name',
                    'factor',
                    'price',
                    'is_active',
                ]),
                'recipe_enabled' => (bool) $product->recipe?->is_active,
                'recipe_items' => $product->recipe?->items()
                    ->with('ingredient:id,name,sku')
                    ->get(['id', 'product_recipe_id', 'ingredient_product_id', 'quantity'])
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'ingredient_product_id' => $item->ingredient_product_id,
                        'ingredient_name' => $item->ingredient?->name,
                        'ingredient_sku' => $item->ingredient?->sku,
                        'quantity' => $item->quantity,
                    ])
                    ->values() ?? [],
            ],
            'categories' => $this->categoryOptions(),
            'ingredient_products' => $this->ingredientOptions($product->id),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_alert' => ['required', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image'],
            'presentations' => ['array'],
            'presentations.*.id' => ['nullable', 'integer', 'exists:product_presentations,id'],
            'presentations.*.name' => ['required', 'string', 'max:255'],
            'presentations.*.factor' => ['required', 'integer', 'min:1'],
            'presentations.*.price' => ['required', 'numeric', 'min:0'],
            'presentations.*.is_active' => ['required', 'boolean'],
            'recipe_enabled' => ['nullable', 'boolean'],
            'recipe_items' => ['array'],
            'recipe_items.*.id' => ['nullable', 'integer', 'exists:product_recipe_items,id'],
            'recipe_items.*.ingredient_product_id' => ['required_with:recipe_items', 'integer', 'exists:products,id'],
            'recipe_items.*.quantity' => ['required_with:recipe_items', 'integer', 'min:1'],
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('', 'public_products');
            $data['photo'] = 'products/' . ltrim($path, '/');
        } else {
            // Mantener la foto actual si no se envía una nueva
            unset($data['photo']);
        }

        $this->ensureSku($data, $product->id);
        $presentations = $data['presentations'] ?? [];
        $recipeEnabled = (bool) ($data['recipe_enabled'] ?? false);
        $recipeItems = $data['recipe_items'] ?? [];
        unset($data['presentations']);
        unset($data['recipe_enabled'], $data['recipe_items']);

        DB::transaction(function () use ($product, $data, $presentations, $recipeEnabled, $recipeItems): void {
            $product->update($data);
            $this->syncPresentations($product, $presentations);
            $this->syncRecipe($product, $recipeEnabled, $recipeItems);
        });

        return redirect()
            ->route('products.index')
            ->with('success', [
                'title' => 'Producto actualizado',
                'description' => "{$product->name} guardado. Stock: {$product->stock} {$product->unit_label}.",
            ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', [
            'title' => 'Producto eliminado',
            'description' => 'El producto fue eliminado correctamente.',
        ]);
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

    private function syncPresentations(Product $product, array $presentations): void
    {
        $ids = collect($presentations)->pluck('id')->filter()->all();
        if (! empty($ids)) {
            ProductPresentation::where('product_id', $product->id)
                ->whereNotIn('id', $ids)
                ->delete();
        } else {
            $product->presentations()->delete();
        }

        foreach ($presentations as $presentation) {
            $product->presentations()->updateOrCreate(
                [
                    'id' => $presentation['id'] ?? null,
                ],
                [
                    'name' => $presentation['name'],
                    'factor' => (int) $presentation['factor'],
                    'price' => $presentation['price'],
                    'is_active' => (bool) $presentation['is_active'],
                ]
            );
        }
    }

    private function ensureSku(array &$data, ?int $ignoreId = null): void
    {
        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku !== '') {
            $data['sku'] = $sku;
            return;
        }

        $base = Str::slug((string) ($data['name'] ?? ''), '-');
        if ($base === '') {
            $base = 'sku';
        }

        $base = strtoupper($base);
        $candidate = $base;
        $suffix = 1;

        $exists = function (string $value) use ($ignoreId): bool {
            return Product::where('sku', $value)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
        };

        while ($exists($candidate)) {
            $suffix++;
            $candidate = $base . '-' . $suffix;
        }

        $data['sku'] = $candidate;
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,sku:?string,stock:int}>
     */
    private function ingredientOptions(?int $excludeProductId = null): array
    {
        return Product::query()
            ->where('is_active', true)
            ->when($excludeProductId, fn ($query) => $query->where('id', '<>', $excludeProductId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock'])
            ->map(fn (Product $product) => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'sku' => $product->sku,
                'stock' => (int) $product->stock,
            ])
            ->values()
            ->all();
    }

    private function syncRecipe(Product $product, bool $recipeEnabled, array $recipeItems): void
    {
        $normalized = collect($recipeItems)
            ->map(fn (array $item) => [
                'ingredient_product_id' => (int) ($item['ingredient_product_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
            ])
            ->filter(fn (array $item) => $item['ingredient_product_id'] > 0 && $item['quantity'] > 0)
            ->values();

        if (! $recipeEnabled || $normalized->isEmpty()) {
            ProductRecipe::query()->where('product_id', $product->id)->delete();
            return;
        }

        if ($normalized->contains(fn (array $item) => $item['ingredient_product_id'] === (int) $product->id)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recipe_items' => 'Un producto no puede ser insumo de su propia receta.',
            ]);
        }

        $duplicate = $normalized
            ->pluck('ingredient_product_id')
            ->duplicates()
            ->isNotEmpty();

        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recipe_items' => 'No repitas el mismo insumo en la receta.',
            ]);
        }

        $recipe = ProductRecipe::query()->updateOrCreate(
            ['product_id' => $product->id],
            ['is_active' => true],
        );

        $ingredientsIds = $normalized->pluck('ingredient_product_id')->values();
        $recipe->items()->whereNotIn('ingredient_product_id', $ingredientsIds)->delete();

        foreach ($normalized as $item) {
            $recipe->items()->updateOrCreate(
                ['ingredient_product_id' => $item['ingredient_product_id']],
                ['quantity' => $item['quantity']],
            );
        }
    }
}
