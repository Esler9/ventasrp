<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to('/admin/point-of-sale');
    }

    return redirect()->route('filament.admin.auth.login');
});
