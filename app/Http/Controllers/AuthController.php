<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $method = $request->input('method', 'password');

        if ($method === 'pin') {
            $data = $request->validate([
                'username' => ['required', 'string'],
                'pin' => ['required', 'string'],
            ]);

            /** @var User|null $user */
            $user = User::query()
                ->where('username', $data['username'])
                ->orWhere('email', $data['username'])
                ->first();

            if (! $user || $user->pin === null || ! Hash::check($data['pin'], (string) $user->pin)) {
                return back()->withErrors([
                    'username' => 'Credenciales inválidas.',
                ])->onlyInput('username');
            }

            Auth::login($user, false);
        } else {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $remember = (bool) $request->boolean('remember');

            if (! Auth::attempt($credentials, $remember)) {
                return back()->withErrors([
                    'email' => 'Credenciales inválidas.',
                ])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        return redirect()->intended($request->user()->defaultHomeRoute());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
