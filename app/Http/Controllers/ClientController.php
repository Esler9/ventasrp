<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('tax_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->through(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'tax_id' => $client->tax_id,
                'address' => $client->address,
                'is_active' => $client->is_active,
            ]);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $client = Client::create([
            'name' => trim((string) $data['name']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'tax_id' => trim((string) ($data['tax_id'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'tax_id' => $client->tax_id,
                'address' => $client->address,
                'is_active' => $client->is_active,
            ], 201);
        }

        return back()->with('success', [
            'title' => 'Cliente creado',
            'description' => 'El cliente fue guardado correctamente.',
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $client->update([
            'name' => trim((string) $data['name']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'tax_id' => trim((string) ($data['tax_id'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', [
            'title' => 'Cliente actualizado',
            'description' => 'Los cambios se guardaron correctamente.',
        ]);
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->sales()->exists()) {
            return back()->with('error', [
                'title' => 'No se puede eliminar',
                'description' => 'Este cliente ya tiene ventas asociadas.',
            ]);
        }

        $client->delete();

        return back()->with('success', [
            'title' => 'Cliente eliminado',
            'description' => 'El cliente fue eliminado correctamente.',
        ]);
    }
}
