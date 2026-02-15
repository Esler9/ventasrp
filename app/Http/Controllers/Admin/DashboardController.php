<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $cards = [
            [
                'key' => 'pos',
                'title' => 'POS',
                'headline' => 'Vender rápido',
                'description' => 'Accede al punto de venta',
                'href' => '/pos',
                'permission' => 'pos.view',
                'button' => 'Ir a POS',
            ],
            [
                'key' => 'inventory',
                'title' => 'Inventario',
                'headline' => 'Productos',
                'description' => 'Gestiona precios, stock y fotos',
                'href' => '/admin/products',
                'permission' => 'products.view',
                'button' => 'Ver productos',
            ],
            [
                'key' => 'sales',
                'title' => 'Ventas',
                'headline' => 'Historial',
                'description' => 'Consulta ventas y descuentos',
                'href' => '/admin/sales',
                'permission' => 'sales.view',
                'button' => 'Ver ventas',
            ],
            [
                'key' => 'cash',
                'title' => 'Caja',
                'headline' => 'Apertura y cierre',
                'description' => 'Arqueo y movimientos de caja',
                'href' => '/admin/cash',
                'permission' => 'cash.view',
                'button' => 'Ir a caja',
            ],
            [
                'key' => 'users',
                'title' => 'Usuarios',
                'headline' => 'Equipo y accesos',
                'description' => 'Gestiona roles y permisos base',
                'href' => '/admin/users',
                'permission' => 'users.view',
                'button' => 'Gestionar usuarios',
            ],
        ];

        $cards = collect($cards)
            ->map(fn (array $card) => [
                ...$card,
                'enabled' => $user?->hasPermission($card['permission']) ?? false,
            ])
            ->filter(fn (array $card) => $card['enabled'])
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'cards' => $cards,
        ]);
    }
}
