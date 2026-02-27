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
        $currencies = [
            ['code' => 'GTQ', 'symbol' => 'Q', 'label' => 'Quetzales (GTQ)'],
            ['code' => 'USD', 'symbol' => '$', 'label' => 'Dólares (USD)'],
            ['code' => 'MXN', 'symbol' => '$', 'label' => 'Pesos Mexicanos (MXN)'],
            ['code' => 'EUR', 'symbol' => '€', 'label' => 'Euros (EUR)'],
        ];

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'app_logo_url' => $settings->logoUrl(),
                'primary_color' => $settings->primary_color,
                'secondary_color' => $settings->secondary_color,
                'currency_code' => $settings->currency_code ?: 'GTQ',
                'currency_symbol' => $settings->currency_symbol ?: 'Q',
                'surcharge_calculation_mode' => $settings->surcharge_calculation_mode ?: 'sum',
                'business_mode' => $settings->business_mode ?: 'minorista',
            ],
            'currencies' => $currencies,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_color' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['required', 'regex:/^#(?:[A-Fa-f0-9]{6})$/'],
            'currency_code' => ['required', 'in:GTQ,USD,MXN,EUR'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'surcharge_calculation_mode' => ['nullable', 'in:sum,division'],
            'business_mode' => ['nullable', 'in:minorista,restaurante'],
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
        if (! array_key_exists('surcharge_calculation_mode', $data) || ! $data['surcharge_calculation_mode']) {
            $data['surcharge_calculation_mode'] = $settings->surcharge_calculation_mode ?: 'sum';
        }
        if (! array_key_exists('business_mode', $data) || ! $data['business_mode']) {
            $data['business_mode'] = $settings->business_mode ?: 'minorista';
        }

        $settings->update($data);

        return back()->with('success', [
            'title' => 'Configuración actualizada',
            'description' => 'Se guardaron logo, colores, moneda, recargos y modo del sistema.',
        ]);
    }
}
