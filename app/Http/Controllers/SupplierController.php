<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->when($q !== '', fn ($b) => $b->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('tax_id', 'like', "%{$q}%");
            }))
            ->withCount('purchases')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn ($s) => [
                'id'             => $s->id,
                'name'           => $s->name,
                'contact_name'   => $s->contact_name,
                'phone'          => $s->phone,
                'email'          => $s->email,
                'address'        => $s->address,
                'tax_id'         => $s->tax_id,
                'notes'          => $s->notes,
                'is_active'      => $s->is_active,
                'purchases_count'=> $s->purchases_count,
            ]);

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => ['q' => $q],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'address'      => ['nullable', 'string', 'max:2000'],
            'tax_id'       => ['nullable', 'string', 'max:30'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', [
            'title'       => 'Proveedor creado',
            'description' => $data['name'],
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'address'      => ['nullable', 'string', 'max:2000'],
            'tax_id'       => ['nullable', 'string', 'max:30'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : $supplier->is_active;

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', [
            'title'       => 'Proveedor actualizado',
            'description' => $supplier->name,
        ]);
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->withErrors(['supplier' => 'No se puede eliminar: tiene compras asociadas. Desactívalo en su lugar.']);
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', [
            'title'       => 'Proveedor eliminado',
            'description' => $supplier->name,
        ]);
    }
}
