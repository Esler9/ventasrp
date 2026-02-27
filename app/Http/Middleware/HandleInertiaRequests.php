<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_key' => $user->roleKey(),
                    'role_label' => $user->roleLabel(),
                    'permissions' => $user->permissions(),
                    'can_switch_branch' => $user->canSwitchBranch(),
                    'home' => $user->defaultHomeRoute(),
                ] : null,
            ],
            'app' => [
                'settings' => function () {
                    $settings = AppSetting::current();

                    return [
                        'primary_color' => $settings->primary_color,
                        'secondary_color' => $settings->secondary_color,
                        'logo_url' => $settings->logoUrl(),
                        'currency_code' => $settings->currency_code ?: 'GTQ',
                        'currency_symbol' => $settings->currency_symbol ?: 'Q',
                        'surcharge_calculation_mode' => $settings->surcharge_calculation_mode ?: 'sum',
                        'business_mode' => $settings->business_mode ?: 'minorista',
                    ];
                },
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'sale_ticket' => fn () => $request->session()->get('sale_ticket'),
            ],
        ];
    }
}
