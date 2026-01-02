<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @routes
    @vite('resources/js/pos-app.js')
    @inertiaHead
</head>
<body class="antialiased bg-gray-950 text-gray-100">
    @inertia
</body>
</html>
