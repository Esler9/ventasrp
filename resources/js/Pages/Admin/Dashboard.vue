<template>
    <AppLayout title="Panel">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft">
                <p class="text-sm text-gray-400">Pos</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Vender rápido</p>
                <p class="mt-1 text-xs text-gray-500">Accede al punto de venta</p>
                <Link href="/pos" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black">
                    Ir a POS
                </Link>
            </div>
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft">
                <p class="text-sm text-gray-400">Inventario</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Productos</p>
                <p class="mt-1 text-xs text-gray-500">Gestiona precios, stock y fotos</p>
                <Link href="/admin/products" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100">
                    Ver productos
                </Link>
            </div>
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft">
                <p class="text-sm text-gray-400">Ventas</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Historial</p>
                <p class="mt-1 text-xs text-gray-500">Consulta ventas y descuentos</p>
                <Link href="/admin/sales" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100">
                    Ver ventas
                </Link>
            </div>
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft">
                <p class="text-sm text-gray-400">Usuarios</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Equipo y accesos</p>
                <p class="mt-1 text-xs text-gray-500">Gestiona quién puede entrar y sus PIN</p>
                <Link href="/admin/users" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100">
                    Gestionar usuarios
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
