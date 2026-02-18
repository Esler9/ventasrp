<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = AppSetting::current();

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'app_logo_url' => $settings->logoUrl(),
                'primary_color' => $settings->primary_color,
                'secondary_color' => $settings->secondary_color,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_color' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{6})$/'],
            'app_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $settings = AppSetting::current();

        if ($request->hasFile('app_logo')) {
            $extension = $request->file('app_logo')->getClientOriginalExtension();
            $name = 'brand-logo-' . Str::random(10) . '.' . strtolower($extension);
            $request->file('app_logo')->move(public_path('logos'), $name);
            $data['app_logo_path'] = 'logos/' . $name;
        }

        unset($data['app_logo']);

        $settings->update($data);

        return back()->with('success', [
            'title' => 'Configuración actualizada',
            'description' => 'Se guardaron logo y colores de la marca.',
        ]);
    }
}
