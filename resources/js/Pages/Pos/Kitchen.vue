<template>
    <AppLayout title="Cocina">
        <div class="space-y-5">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-100">Órdenes de cocina</h1>
                        <p class="text-xs text-gray-400">Flujo por comanda: pendiente -> en preparación -> listo -> entregado.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-400">Prom. Prep</span>
                        <span class="rounded-full bg-emerald-500/20 px-2 py-1 font-semibold text-emerald-200">{{ averagePrepTime }}</span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="station in stations"
                            :key="station.value"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                            :class="selectedStation === station.value
                                ? 'border-sky-400 bg-sky-500/20 text-sky-100'
                                : 'border-gray-700 bg-gray-950/60 text-gray-300 hover:text-gray-100'"
                            @click="selectedStation = station.value"
                        >
                            {{ station.label }}
                            <span class="ml-1 opacity-80">({{ stationCount(station.value) }})</span>
                        </button>
                    </div>
                    <a href="/pos" class="rounded-xl border border-gray-700 bg-gray-950/50 px-3 py-2 text-sm text-gray-200 hover:border-gray-600">
                        Volver al POS
                    </a>
                </div>
            </div>

            <div v-if="firstError" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200">
                {{ firstError }}
            </div>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                <div class="overflow-x-auto">
                    <div class="flex min-w-full gap-3 pb-1">
                        <article
                            v-for="order in filteredOrders"
                            :key="order.id"
                            class="relative flex min-h-[560px] w-[310px] shrink-0 flex-col overflow-hidden rounded-2xl border border-gray-800 bg-gray-950/60"
                            :class="orderCardAccent(order.status)"
                        >
                            <header class="border-b border-gray-800 bg-gray-900/85 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wide" :class="orderTagColor(order.status)">
                                            {{ orderLabel(order.status) }}
                                        </p>
                                        <p class="text-xl font-semibold text-gray-100">Orden #{{ order.id }}</p>
                                        <p class="text-xs text-gray-400">{{ order.table_name }} · {{ order.account_label }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-3xl font-semibold leading-none" :class="orderTimeColor(order.status)">
                                            {{ elapsedLabel(order.sent_at) }}
                                        </p>
                                        <p class="mt-1 text-[10px] uppercase text-gray-500">Transcurrido</p>
                                    </div>
                                </div>
                            </header>

                            <div class="flex-1 space-y-2 overflow-y-auto p-3">
                                <div
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="rounded-xl border border-gray-800 bg-gray-900/70 p-2"
                                >
                                    <div class="flex items-start gap-2">
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-gray-700 bg-gray-950/80 text-sky-500"
                                            :checked="isItemChecked(order.id, item.id)"
                                            @change="toggleItemCheck(order.id, item.id, $event.target.checked)"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold" :class="isItemChecked(order.id, item.id) ? 'text-gray-400 line-through' : 'text-gray-100'">
                                                {{ item.quantity }}x {{ item.product_name }}
                                            </p>
                                            <p v-if="item.note" class="mt-1 text-[11px] italic" :class="isItemChecked(order.id, item.id) ? 'text-gray-500 line-through' : 'text-amber-200'">
                                                • {{ item.note }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-2 grid gap-1">
                                        <button
                                            type="button"
                                            class="rounded-md border border-red-700/60 px-2 py-1.5 text-[11px] font-semibold text-red-300"
                                            @click="cancelItem(item)"
                                        >
                                            Anular
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <footer class="border-t border-gray-800 p-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-400">
                                        {{ checkedItemsCount(order) }}/{{ order.items_count }} item(s)
                                    </span>
                                    <span :class="orderTagColor(order.status)" class="font-semibold">{{ orderLabel(order.status) }}</span>
                                </div>
                                <button
                                    v-if="orderAction(order)"
                                    type="button"
                                    class="mt-2 w-full rounded-md px-3 py-2 text-sm font-semibold"
                                    :class="orderAction(order).className"
                                    :disabled="orderAction(order).disabled"
                                    @click="setOrderStatus(order, orderAction(order).action)"
                                >
                                    {{ orderAction(order).label }}
                                </button>
                            </footer>
                        </article>
                    </div>
                </div>
                <p v-if="!filteredOrders.length" class="px-2 py-6 text-center text-sm text-gray-400">No hay órdenes para esta estación.</p>
            </section>

            <div class="flex flex-wrap items-center gap-5 rounded-2xl border border-gray-800 bg-gray-900/70 px-4 py-3 text-xs">
                <div class="flex items-center gap-1.5 text-gray-300">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    Urgente (pendiente)
                </div>
                <div class="flex items-center gap-1.5 text-gray-300">
                    <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                    Activa (en preparación)
                </div>
                <div class="flex items-center gap-1.5 text-gray-300">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Lista
                </div>
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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
    served_recent: { type: Array, default: () => [] },
});

const page = usePage();
const selectedStation = ref('all');
const nowTs = ref(Date.now());
const checkedItems = ref({});
let timer = null;

const stations = [
    { value: 'all', label: 'Todas' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'preparing', label: 'En preparación' },
    { value: 'ready', label: 'Listo' },
];

onMounted(() => {
    timer = window.setInterval(() => {
        nowTs.value = Date.now();
    }, 30000);
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
});

const firstError = computed(() => {
    const errors = page.props.errors || {};
    return Object.values(errors)[0] || null;
});

const servedRecent = computed(() => props.served_recent || []);
const filteredOrders = computed(() => {
    if (selectedStation.value === 'all') return props.orders;
    return props.orders.filter((order) => order.status === selectedStation.value);
});

const averagePrepTime = computed(() => {
    if (!props.orders.length) return '0m';
    const diffMinutes = props.orders
        .map((order) => minutesElapsed(order.sent_at))
        .filter((value) => Number.isFinite(value));

    if (!diffMinutes.length) return '0m';
    const avg = Math.round(diffMinutes.reduce((sum, value) => sum + value, 0) / diffMinutes.length);
    return `${avg}m`;
});

const stationCount = (status) => {
    if (status === 'all') return props.orders.length;
    return props.orders.filter((order) => order.status === status).length;
};

const orderLabel = (status) => ({
    pending: 'Pendiente',
    preparing: 'En preparación',
    ready: 'Listo',
}[status] || 'Sin estado');

const orderCardAccent = (status) => ({
    'border-l-4 border-l-rose-500': status === 'pending',
    'border-l-4 border-l-sky-500': status === 'preparing',
    'border-l-4 border-l-emerald-500': status === 'ready',
});

const orderTagColor = (status) => ({
    'text-rose-300': status === 'pending',
    'text-sky-300': status === 'preparing',
    'text-emerald-300': status === 'ready',
});

const orderTimeColor = (status) => ({
    'text-rose-300': status === 'pending',
    'text-sky-200': status === 'preparing',
    'text-emerald-200': status === 'ready',
});

const orderAction = (order) => {
    if (order.status === 'pending') {
        return {
            action: 'start',
            label: 'Iniciar Orden',
            className: 'bg-sky-500 text-white hover:bg-sky-400',
            disabled: false,
        };
    }

    if (order.status === 'preparing' || order.status === 'ready') {
        const allChecked = allItemsChecked(order);
        return {
            action: 'complete',
            label: allChecked ? 'Completar Orden' : 'Marca todos para completar',
            className: allChecked
                ? 'bg-emerald-500 text-white hover:bg-emerald-400'
                : 'bg-gray-800 text-gray-400 cursor-not-allowed',
            disabled: !allChecked,
        };
    }

    return null;
};

const itemCheckKey = (orderId, itemId) => `${orderId}:${itemId}`;

const isItemChecked = (orderId, itemId) => Boolean(checkedItems.value[itemCheckKey(orderId, itemId)]);

const toggleItemCheck = (orderId, itemId, checked) => {
    const key = itemCheckKey(orderId, itemId);
    checkedItems.value = {
        ...checkedItems.value,
        [key]: checked,
    };
};

const checkedItemsCount = (order) => order.items.filter((item) => isItemChecked(order.id, item.id)).length;

const allItemsChecked = (order) => order.items.length > 0 && checkedItemsCount(order) === order.items.length;

const minutesElapsed = (value) => {
    if (!value) return 0;
    const ts = Date.parse(value.replace(' ', 'T'));
    if (Number.isNaN(ts)) return 0;
    return Math.max(0, Math.floor((nowTs.value - ts) / 60000));
};

const elapsedLabel = (value) => {
    const minutes = minutesElapsed(value);
    const hours = Math.floor(minutes / 60);
    const rem = minutes % 60;
    if (hours > 0) return `${hours}:${String(rem).padStart(2, '0')}h`;
    return `${minutes}m`;
};

const setOrderStatus = (order, action) => {
    if (action === 'complete' && !allItemsChecked(order)) return;

    router.post(`/pos/restaurant/orders/${order.id}/status`, {
        action,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const current = { ...checkedItems.value };
            order.items.forEach((item) => {
                delete current[itemCheckKey(order.id, item.id)];
            });
            checkedItems.value = current;
        },
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
