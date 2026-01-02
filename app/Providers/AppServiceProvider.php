<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'body.end',
            function () {
                // Solo en panel Filament y en pantallas pequeñas.
                if (! Route::is('filament.*')) {
                    return '';
                }

                return view('filament.partials.mobile-nav');
            }
        );
    }
}
