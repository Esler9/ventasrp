<template>
    <AppLayout title="Cocina">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div>
                    <h1 class="text-lg font-semibold text-gray-100">Órdenes de cocina</h1>
                    <p class="text-xs text-gray-400">Gestiona el avance de cada ítem enviado desde mesas.</p>
                </div>
                <a href="/pos" class="rounded-xl border border-gray-700 px-3 py-2 text-sm text-gray-200">Volver al POS</a>
            </div>

            <div v-if="firstError" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200">
                {{ firstError }}
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-amber-200">Pendiente</h2>
                    <div class="mt-3 space-y-2">
                        <article v-for="item in sentItems" :key="item.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <p class="text-sm font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                            <p class="text-xs text-gray-400">{{ item.table_name }} · {{ item.account_label }}</p>
                            <p v-if="item.note" class="mt-1 text-xs text-gray-300">Obs: {{ item.note }}</p>
                            <button type="button" class="mt-2 w-full rounded-lg bg-sky-500 px-2 py-2 text-xs font-semibold text-white" @click="setStatus(item.id, 'preparing')">
                                Marcar en preparación
                            </button>
                        </article>
                        <p v-if="!sentItems.length" class="text-xs text-gray-500">Sin ítems pendientes.</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-sky-200">En preparación</h2>
                    <div class="mt-3 space-y-2">
                        <article v-for="item in preparingItems" :key="item.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <p class="text-sm font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                            <p class="text-xs text-gray-400">{{ item.table_name }} · {{ item.account_label }}</p>
                            <p v-if="item.note" class="mt-1 text-xs text-gray-300">Obs: {{ item.note }}</p>
                            <button type="button" class="mt-2 w-full rounded-lg bg-emerald-500 px-2 py-2 text-xs font-semibold text-white" @click="setStatus(item.id, 'ready')">
                                Marcar listo
                            </button>
                        </article>
                        <p v-if="!preparingItems.length" class="text-xs text-gray-500">Sin ítems en preparación.</p>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-emerald-200">Listo</h2>
                    <div class="mt-3 space-y-2">
                        <article v-for="item in readyItems" :key="item.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <p class="text-sm font-semibold text-gray-100">{{ item.quantity }}x {{ item.product_name }}</p>
                            <p class="text-xs text-gray-400">{{ item.table_name }} · {{ item.account_label }}</p>
                            <p v-if="item.note" class="mt-1 text-xs text-gray-300">Obs: {{ item.note }}</p>
                            <button type="button" class="mt-2 w-full rounded-lg bg-amber-400 px-2 py-2 text-xs font-semibold text-black" @click="setStatus(item.id, 'served')">
                                Marcar entregado
                            </button>
                        </article>
                        <p v-if="!readyItems.length" class="text-xs text-gray-500">Sin ítems listos.</p>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const page = usePage();

const firstError = computed(() => {
    const errors = page.props.errors || {};
    return Object.values(errors)[0] || null;
});

const sentItems = computed(() => props.items.filter((item) => item.kitchen_status === 'sent'));
const preparingItems = computed(() => props.items.filter((item) => item.kitchen_status === 'preparing'));
const readyItems = computed(() => props.items.filter((item) => item.kitchen_status === 'ready'));

const setStatus = (itemId, status) => {
    router.post(`/pos/restaurant/items/${itemId}/status`, {
        kitchen_status: status,
    }, {
        preserveScroll: true,
    });
};
</script>
