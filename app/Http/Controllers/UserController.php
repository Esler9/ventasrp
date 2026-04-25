<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'email', 'role', 'extra_permissions', 'created_at'])
            ->selectRaw('pin IS NOT NULL as has_pin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id'                => $user->id,
                'name'              => $user->name,
                'username'          => $user->username,
                'email'             => $user->email,
                'role'              => $user->roleKey(),
                'role_label'        => $user->roleLabel(),
                'extra_permissions' => (array) ($user->extra_permissions ?? []),
                'has_pin'           => (bool) $user->has_pin,
                'created_at'        => $user->created_at?->toDateString(),
            ]);

        $allPerms = config('access.permissions', []);

        $roles = Role::orderBy('id')->get()->map(fn (Role $role) => [
            'key'         => $role->key,
            'label'       => $role->label,
            'permissions' => (array) $role->permissions,
        ])->values();

        // Group permissions by module (prefix before the dot)
        $permissionGroups = collect($allPerms)
            ->groupBy(fn (string $p) => explode('.', $p)[0])
            ->map(fn ($perms, $module) => [
                'module' => $module,
                'label'  => self::moduleLabel($module),
                'perms'  => $perms->values()->all(),
            ])
            ->values();

        return Inertia::render('Admin/Users', [
            'users'            => $users,
            'roles'            => $roles,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allPerms = config('access.permissions', []);

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'username'            => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'                => ['required', Rule::in(User::availableRoles())],
            'password'            => ['required', 'string', 'min:4'],
            'pin'                 => ['nullable', 'string', 'min:4', 'max:10'],
            'extra_permissions'   => ['nullable', 'array'],
            'extra_permissions.*' => ['string', Rule::in($allPerms)],
        ]);

        $data['extra_permissions'] = array_values(array_unique($data['extra_permissions'] ?? []));

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $allPerms = config('access.permissions', []);

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'username'            => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email'               => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'                => ['required', Rule::in(User::availableRoles())],
            'password'            => ['nullable', 'string', 'min:4'],
            'pin'                 => ['nullable', 'string', 'min:4', 'max:10'],
            'extra_permissions'   => ['nullable', 'array'],
            'extra_permissions.*' => ['string', Rule::in($allPerms)],
        ]);

        $update = collect($data)->filter(function ($value, $key) {
            if (in_array($key, ['password', 'pin'], true)) {
                return $value !== null && $value !== '';
            }
            return true;
        })->toArray();

        if (array_key_exists('pin', $data) && ($data['pin'] === null || $data['pin'] === '')) {
            $update['pin'] = null;
        }

        $update['extra_permissions'] = array_values(array_unique($data['extra_permissions'] ?? []));

        $user->update($update);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }

    private static function moduleLabel(string $module): string
    {
        return [
            'dashboard'  => 'Dashboard',
            'pos'        => 'Punto de Venta',
            'products'   => 'Productos',
            'categories' => 'Categorías',
            'sales'      => 'Ventas',
            'clients'    => 'Clientes',
            'cash'       => 'Caja',
            'reports'    => 'Reportes',
            'expenses'   => 'Gastos',
            'banks'      => 'Bancos',
            'inventory'  => 'Inventario',
            'purchases'  => 'Compras',
            'suppliers'  => 'Proveedores',
            'users'      => 'Usuarios',
            'settings'   => 'Configuración',
        ][$module] ?? ucfirst($module);
    }
}
