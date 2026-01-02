<template>
    <AppLayout title="Panel">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30">
                <p class="text-sm text-gray-400">Pos</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Vender rápido</p>
                <p class="mt-1 text-xs text-gray-500">Accede al punto de venta</p>
                <a href="/pos" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black">
                    Ir a POS
                </a>
            </div>
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30">
                <p class="text-sm text-gray-400">Inventario</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Productos</p>
                <p class="mt-1 text-xs text-gray-500">Gestiona precios, stock y fotos</p>
                <a href="/admin/products" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100">
                    Ver productos
                </a>
            </div>
            <div class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30">
                <p class="text-sm text-gray-400">Ventas</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">Historial</p>
                <p class="mt-1 text-xs text-gray-500">Consulta ventas y descuentos</p>
                <a href="/admin/sales" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100">
                    Ver ventas
                </a>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
</script>
