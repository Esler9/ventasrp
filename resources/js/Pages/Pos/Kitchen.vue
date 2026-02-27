<template>
    <AppLayout title="Cocina">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div>
                    <h1 class="text-lg font-semibold text-gray-100">Órdenes de cocina</h1>
                    <p class="text-xs text-gray-400">Flujo por comanda: pendiente -> en preparación -> listo -> entregado.</p>
                </div>
                <a href="/pos" class="rounded-xl border border-gray-700 px-3 py-2 text-sm text-gray-200">Volver al POS</a>
            </div>

            <div v-if="firstError" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200">
                {{ firstError }}
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-amber-200">Pendiente</h2>
                    <div class="mt-3 space-y-3">
                        <article v-for="order in pendingOrders" :key="order.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <header class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-100">Orden #{{ order.id }}</p>
                                <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-200">{{ order.items_count }} item(s)</span>
                            </header>
                            <p class="text-xs text-gray-400">{{ order.table_name }} · {{ order.account_label }}</p>
                            <div class="mt-2 space-y-2">
                                <div v-for="item in order.items" :key="item.id" class="rounded-lg border border-gray-800 bg-gray-900/70 p-2">
                                    <p class="text-xs font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                                    <p v-if="item.note" class="mt-1 text-[11px] text-gray-300">Obs: {{ item.note }}</p>
                                    <button
                                        type="button"
                                        class="mt-2 w-full rounded-md bg-sky-500 px-2 py-1.5 text-[11px] font-semibold text-white"
                                        @click="setStatus(item.id, 'preparing')"
                                    >
                                        Iniciar preparación
                                    </button>
                                    <button
                                        type="button"
                                        class="mt-1 w-full rounded-md border border-red-700/60 px-2 py-1.5 text-[11px] font-semibold text-red-300"
                                        @click="cancelItem(item)"
                                    >
                                        Anular
                                    </button>
                                </div>
                            </div>
                        </article>
                        <p v-if="!pendingOrders.length" class="text-xs text-gray-500">Sin órdenes pendientes.</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-sky-200">En preparación</h2>
                    <div class="mt-3 space-y-3">
                        <article v-for="order in preparingOrders" :key="order.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <header class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-100">Orden #{{ order.id }}</p>
                                <span class="rounded-full bg-sky-500/20 px-2 py-0.5 text-[10px] font-semibold text-sky-200">{{ order.items_count }} item(s)</span>
                            </header>
                            <p class="text-xs text-gray-400">{{ order.table_name }} · {{ order.account_label }}</p>
                            <div class="mt-2 space-y-2">
                                <div v-for="item in order.items" :key="item.id" class="rounded-lg border border-gray-800 bg-gray-900/70 p-2">
                                    <p class="text-xs font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                                    <p v-if="item.note" class="mt-1 text-[11px] text-gray-300">Obs: {{ item.note }}</p>
                                    <button
                                        v-if="item.kitchen_status === 'preparing'"
                                        type="button"
                                        class="mt-2 w-full rounded-md bg-emerald-500 px-2 py-1.5 text-[11px] font-semibold text-white"
                                        @click="setStatus(item.id, 'ready')"
                                    >
                                        Marcar listo
                                    </button>
                                    <button
                                        type="button"
                                        class="mt-1 w-full rounded-md border border-red-700/60 px-2 py-1.5 text-[11px] font-semibold text-red-300"
                                        @click="cancelItem(item)"
                                    >
                                        Anular
                                    </button>
                                </div>
                            </div>
                        </article>
                        <p v-if="!preparingOrders.length" class="text-xs text-gray-500">Sin órdenes en preparación.</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-emerald-200">Listo</h2>
                    <div class="mt-3 space-y-3">
                        <article v-for="order in readyOrders" :key="order.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <header class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-100">Orden #{{ order.id }}</p>
                                <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-200">{{ order.items_count }} item(s)</span>
                            </header>
                            <p class="text-xs text-gray-400">{{ order.table_name }} · {{ order.account_label }}</p>
                            <div class="mt-2 space-y-2">
                                <div v-for="item in order.items" :key="item.id" class="rounded-lg border border-gray-800 bg-gray-900/70 p-2">
                                    <p class="text-xs font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                                    <p v-if="item.note" class="mt-1 text-[11px] text-gray-300">Obs: {{ item.note }}</p>
                                    <button
                                        v-if="item.kitchen_status === 'ready'"
                                        type="button"
                                        class="mt-2 w-full rounded-md bg-amber-400 px-2 py-1.5 text-[11px] font-semibold text-black"
                                        @click="setStatus(item.id, 'served')"
                                    >
                                        Marcar entregado
                                    </button>
                                    <button
                                        type="button"
                                        class="mt-1 w-full rounded-md border border-red-700/60 px-2 py-1.5 text-[11px] font-semibold text-red-300"
                                        @click="cancelItem(item)"
                                    >
                                        Anular
                                    </button>
                                </div>
                            </div>
                        </article>
                        <p v-if="!readyOrders.length" class="text-xs text-gray-500">Sin órdenes listas.</p>
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                <h2 class="text-sm font-semibold text-gray-100">Entregados recientes (anulables)</h2>
                <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="item in servedRecent" :key="`served-${item.id}`" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                        <p class="text-sm font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                        <p class="text-xs text-gray-400">{{ item.table_name }} · {{ item.account_label }}</p>
                        <p v-if="item.note" class="mt-1 text-xs text-gray-300">Obs: {{ item.note }}</p>
                        <p class="mt-1 text-[11px] text-gray-500">{{ item.served_at }}</p>
                        <button type="button" class="mt-2 w-full rounded-md border border-red-700/60 px-2 py-1.5 text-[11px] font-semibold text-red-300" @click="cancelItem(item)">
                            Anular y revertir inventario
                        </button>
                    </article>
                    <p v-if="!servedRecent.length" class="text-xs text-gray-500">Sin entregados recientes.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
    served_recent: { type: Array, default: () => [] },
});

const page = usePage();

const firstError = computed(() => {
    const errors = page.props.errors || {};
    return Object.values(errors)[0] || null;
});

const pendingOrders = computed(() => props.orders.filter((order) => order.status === 'pending'));
const preparingOrders = computed(() => props.orders.filter((order) => order.status === 'preparing'));
const readyOrders = computed(() => props.orders.filter((order) => order.status === 'ready'));
const servedRecent = computed(() => props.served_recent || []);

const setStatus = (itemId, status) => {
    router.post(`/pos/restaurant/items/${itemId}/status`, {
        kitchen_status: status,
    }, {
        preserveScroll: true,
    });
};

const cancelItem = (item) => {
    const reason = window.prompt('Motivo de anulación:');
    if (!reason || !reason.trim()) return;

    router.post(`/pos/restaurant/items/${item.id}/cancel`, {
        reason: reason.trim(),
    }, {
        preserveScroll: true,
    });
};
</script>
