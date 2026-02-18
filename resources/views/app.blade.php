<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php($settings = \App\Models\AppSetting::current())
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="{{ $settings->primary_color ?? '#0f172a' }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('logos/iconofm.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logos/iconofm-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('logos/iconofm.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/iconofm.png') }}">
    @vite('resources/js/pos-app.js')
    @inertiaHead
</head>
<body class="antialiased bg-gray-950 text-gray-100">
    @inertia
</body>
</html>
