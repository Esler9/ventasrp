<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="icon" type="image/svg+xml" href="{{ asset('logos/iconofm.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('logos/iconofm.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logos/iconofm.png') }}">
    <link rel="shortcut icon" href="{{ asset('logos/iconofm.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-Piv4xVNRyMGpqkM0Myd7sQJ2DqJE50E+I4QJ9bAQQ4K5j0vvVuv5QW7HQwZ0p5Gy" crossorigin="anonymous">
    @vite('resources/js/pos-app.js')
    @inertiaHead
</head>
<body class="antialiased bg-gray-950 text-gray-100">
    @inertia
</body>
</html>
