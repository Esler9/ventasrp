<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $categories = Category::query()
            ->withCount('products')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->through(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'products_count' => $category->products_count,
            ]);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['name'] = trim((string) $data['name']);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $category = Category::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active,
            ], 201);
        }

        return back()->with('success', [
            'title' => 'Categoría creada',
            'description' => 'La categoría fue guardada correctamente.',
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim((string) $data['name']);
        $category->update([
            'name' => $name,
            'slug' => $this->uniqueSlug($name, $category->id),
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', [
            'title' => 'Categoría actualizada',
            'description' => 'Los cambios se guardaron correctamente.',
        ]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', [
                'title' => 'No se puede eliminar',
                'description' => 'Esta categoría tiene productos asociados.',
            ]);
        }

        $category->delete();

        return back()->with('success', [
            'title' => 'Categoría eliminada',
            'description' => 'La categoría fue eliminada correctamente.',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'categoria';
        }

        $slug = $base;
        $suffix = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $suffix++;
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }
}
