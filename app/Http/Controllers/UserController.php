<?php

namespace App\Http\Controllers;

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
            ->select(['id', 'name', 'username', 'email', 'role'])
            ->selectRaw('pin IS NOT NULL as has_pin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->roleKey(),
                'role_label' => $user->roleLabel(),
                'has_pin' => (bool) $user->has_pin,
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => collect(User::availableRoles())->map(fn (string $role) => [
                'key' => $role,
                'label' => (string) config("access.roles.{$role}.label", $role),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::availableRoles())],
            'password' => ['required', 'string', 'min:4'],
            'pin' => ['nullable', 'string', 'min:4', 'max:10'],
        ]);

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(User::availableRoles())],
            'password' => ['nullable', 'string', 'min:4'],
            'pin' => ['nullable', 'string', 'min:4', 'max:10'],
        ]);

        $update = collect($data)->filter(function ($value, $key) {
            if (in_array($key, ['password', 'pin'], true)) {
                return $value !== null && $value !== '';
            }

            return true;
        })->toArray();

        if (array_key_exists('pin', $update) && $update['pin'] === '') {
            $update['pin'] = null;
        }

        $user->update($update);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado.');
    }
}
