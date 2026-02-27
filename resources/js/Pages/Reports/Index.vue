<template>
    <AppLayout title="Reportes">
        <div class="space-y-4">
            <section class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                <form class="grid gap-3 md:grid-cols-4" @submit.prevent="applyFilters">
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Desde</label>
                        <input
                            v-model="filtersForm.date_from"
                            type="date"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Hasta</label>
                        <input
                            v-model="filtersForm.date_to"
                            type="date"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Vendedor</label>
                        <select
                            v-model="filtersForm.seller_id"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        >
                            <option value="">Todos</option>
                            <option v-for="seller in sellers" :key="seller.id" :value="seller.id">{{ seller.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        >
                            Generar
                        </button>
                        <button
                            type="button"
                            class="text-sm text-gray-300 underline"
                            @click="resetFilters"
                        >
                            Limpiar
                        </button>
                    </div>
                </form>
            </section>

            <section class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Ventas</p>
                    <p class="text-2xl font-semibold text-gray-50">{{ summary.sales_count || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Ingresos</p>
                    <p class="text-2xl font-semibold text-emerald-300">Q{{ money(summary.revenue) }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Ticket promedio</p>
                    <p class="text-2xl font-semibold text-sky-300">Q{{ money(summary.avg_ticket) }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Unidades vendidas</p>
                    <p class="text-2xl font-semibold text-gray-50">{{ summary.units || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Descuentos</p>
                    <p class="text-2xl font-semibold text-amber-200">Q{{ money(summary.discounts) }}</p>
                </div>
            </section>

            <section v-if="is_restaurant_mode" class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Órdenes enviadas</p>
                    <p class="text-2xl font-semibold text-gray-50">{{ restaurant_summary.orders_sent || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Ítems entregados</p>
                    <p class="text-2xl font-semibold text-emerald-300">{{ restaurant_summary.served_items || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Prom. min por orden</p>
                    <p class="text-2xl font-semibold text-sky-300">{{ money(restaurant_summary.avg_order_minutes) }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Cuentas abiertas ahora</p>
                    <p class="text-2xl font-semibold text-amber-200">{{ restaurant_summary.open_accounts || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                    <p class="text-xs text-gray-400">Mesas activas ahora</p>
                    <p class="text-2xl font-semibold text-gray-50">{{ restaurant_summary.active_tables || 0 }}</p>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <h2 class="text-sm font-semibold text-gray-100">Ventas por día</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="pb-2 pr-3">Fecha</th>
                                    <th class="pb-2 pr-3 text-right">Ventas</th>
                                    <th class="pb-2 text-right">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody v-if="sales_by_day.length" class="divide-y divide-gray-800">
                                <tr v-for="row in sales_by_day" :key="row.day" class="text-gray-200">
                                    <td class="py-2 pr-3">{{ row.day }}</td>
                                    <td class="py-2 pr-3 text-right">{{ row.orders }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-100">Q{{ money(row.revenue) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-sm text-gray-400">Sin datos en el período.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <h2 class="text-sm font-semibold text-gray-100">Métodos de pago</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="pb-2 pr-3">Método</th>
                                    <th class="pb-2 pr-3 text-right">Transacciones</th>
                                    <th class="pb-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody v-if="payments_by_method.length" class="divide-y divide-gray-800">
                                <tr v-for="row in payments_by_method" :key="row.method" class="text-gray-200">
                                    <td class="py-2 pr-3">{{ methodLabel(row.method) }}</td>
                                    <td class="py-2 pr-3 text-right">{{ row.transactions }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-100">Q{{ money(row.total) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-sm text-gray-400">Sin pagos registrados en el período.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-3">
                <div class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <h2 class="text-sm font-semibold text-gray-100">Top productos</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="pb-2 pr-3">Producto</th>
                                    <th class="pb-2 pr-3 text-right">Unid.</th>
                                    <th class="pb-2 text-right">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody v-if="top_products.length" class="divide-y divide-gray-800">
                                <tr v-for="row in top_products" :key="`${row.id}-${row.sku}`" class="text-gray-200">
                                    <td class="py-2 pr-3">
                                        <p class="font-medium text-gray-100">{{ row.name }}</p>
                                        <p class="text-xs text-gray-500">{{ row.sku }}</p>
                                    </td>
                                    <td class="py-2 pr-3 text-right">{{ row.units }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-100">Q{{ money(row.revenue) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-sm text-gray-400">Sin productos para mostrar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <h2 class="text-sm font-semibold text-gray-100">Top vendedores</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="pb-2 pr-3">Vendedor</th>
                                    <th class="pb-2 pr-3 text-right">Ventas</th>
                                    <th class="pb-2 text-right">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody v-if="top_sellers.length" class="divide-y divide-gray-800">
                                <tr v-for="row in top_sellers" :key="`${row.id}-${row.seller_name}`" class="text-gray-200">
                                    <td class="py-2 pr-3">{{ row.seller_name }}</td>
                                    <td class="py-2 pr-3 text-right">{{ row.orders }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-100">Q{{ money(row.revenue) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-sm text-gray-400">Sin vendedores para mostrar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <h2 class="text-sm font-semibold text-gray-100">Top clientes</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="pb-2 pr-3">Cliente</th>
                                    <th class="pb-2 pr-3 text-right">Compras</th>
                                    <th class="pb-2 text-right">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody v-if="top_clients.length" class="divide-y divide-gray-800">
                                <tr v-for="row in top_clients" :key="row.customer_name" class="text-gray-200">
                                    <td class="py-2 pr-3">{{ row.customer_name }}</td>
                                    <td class="py-2 pr-3 text-right">{{ row.orders }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-100">Q{{ money(row.revenue) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="3" class="py-4 text-center text-sm text-gray-400">Sin clientes para mostrar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    sellers: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    sales_by_day: { type: Array, default: () => [] },
    top_products: { type: Array, default: () => [] },
    top_sellers: { type: Array, default: () => [] },
    top_clients: { type: Array, default: () => [] },
    payments_by_method: { type: Array, default: () => [] },
    is_restaurant_mode: { type: Boolean, default: false },
    restaurant_summary: { type: Object, default: () => ({}) },
});

const filtersForm = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    seller_id: props.filters.seller_id || '',
});

const money = (value) => Number(value || 0).toFixed(2);

const methodLabel = (method) => {
    const labels = {
        cash: 'Efectivo',
        card: 'Tarjeta',
        transfer: 'Transferencia',
    };

    return labels[method] || method;
};

const applyFilters = () => {
    router.get('/admin/reports', filtersForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.date_from = '';
    filtersForm.date_to = '';
    filtersForm.seller_id = '';
    applyFilters();
};
</script>
