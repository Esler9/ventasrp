<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function update(Request $request, Role $role): RedirectResponse
    {
        $allPerms = config('access.permissions', []);

        $data = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['string', Rule::in($allPerms)],
        ]);

        // Never strip the wildcard from owner_manager — keep super-admin intact
        if ($role->hasWildcard()) {
            return redirect()->route('admin.users.index')->with('error', 'No se pueden modificar los permisos del rol dueño/gerente.');
        }

        $role->update([
            'permissions' => array_values(array_unique($data['permissions'] ?? [])),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Permisos del rol actualizados.');
    }
}
