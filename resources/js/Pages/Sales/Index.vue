<template>
    <AppLayout title="Ventas">
        <div class="rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30 space-y-4">
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3 hover-lift fade-in-soft">
                    <p class="text-xs text-gray-400">Ingresos</p>
                    <p class="text-xl font-semibold text-amber-300">Q{{ formatPrice(summary.revenue || 0) }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3 hover-lift fade-in-soft">
                    <p class="text-xs text-gray-400">Descuentos</p>
                    <p class="text-lg font-semibold text-amber-200">Q{{ formatPrice(summary.discounts || 0) }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3 hover-lift fade-in-soft">
                    <p class="text-xs text-gray-400">Unidades vendidas</p>
                    <p class="text-xl font-semibold text-gray-50">{{ summary.units || 0 }}</p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-3 hover-lift fade-in-soft">
                    <p class="text-xs text-gray-400">Ítems</p>
                    <p class="text-xl font-semibold text-gray-50">{{ summary.lines || 0 }}</p>
                </div>
            </div>

            <form class="grid gap-3 md:grid-cols-5" @submit.prevent="applyFilters">
                <div class="md:col-span-2 space-y-1">
                    <label class="text-xs text-gray-400">Buscar</label>
                    <input
                        v-model="filtersForm.q"
                        type="text"
                        placeholder="Producto o SKU"
                        class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                    />
                </div>
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
                <div class="flex items-end gap-2 md:col-span-5">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                    >
                        Aplicar filtros
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
        </div>

        <div class="mt-4 rounded-2xl bg-gray-900/80 ring-1 ring-black/30">
            <div class="hidden grid-cols-12 gap-2 bg-gray-900 px-4 py-3 text-sm font-semibold text-gray-300 md:grid">
                <div class="col-span-3">Producto</div>
                <div class="col-span-2">SKU</div>
                <div class="col-span-2 text-right">Cantidad</div>
                <div class="col-span-2 text-right">Precio</div>
                <div class="col-span-2 text-right">Descuento</div>
                <div class="col-span-1 text-right">Vendedor</div>
            </div>

            <div v-if="items.data.length" class="divide-y divide-gray-800">
                <article v-for="item in items.data" :key="item.id" class="grid grid-cols-1 gap-2 px-4 py-3 md:grid-cols-12 md:items-center">
                    <div class="md:col-span-3">
                        <p class="font-semibold text-gray-50">{{ item.product }}</p>
                        <p class="text-xs text-gray-500">{{ item.created_at }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-200">{{ item.sku }}</p>
                    </div>
                    <div class="md:col-span-2 md:text-right">
                        <p class="text-sm text-gray-200">{{ item.quantity }}</p>
                    </div>
                    <div class="md:col-span-2 md:text-right">
                        <p class="text-sm font-semibold text-gray-100">Q{{ formatPrice(item.unit_price) }}</p>
                    </div>
                    <div class="md:col-span-2 md:text-right">
                        <p class="text-sm text-amber-300">Q{{ formatPrice(item.discount_amount) }}</p>
                    </div>
                    <div class="md:col-span-1 md:text-right">
                        <p class="text-sm text-gray-200">{{ item.seller || '—' }}</p>
                    </div>
                </article>
            </div>

            <div v-else class="px-4 py-6 text-center text-sm text-gray-400">Aún no hay ventas registradas.</div>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
            <div>Mostrando {{ items.data.length }} de {{ items.total }} resultados</div>
            <div class="flex gap-2">
                <button
                    class="rounded-lg border border-gray-700 px-3 py-1 text-xs"
                    :disabled="!items.prev_page_url"
                    @click="goTo(items.prev_page_url)"
                >
                    Anterior
                </button>
                <button
                    class="rounded-lg border border-gray-700 px-3 py-1 text-xs"
                    :disabled="!items.next_page_url"
                    @click="goTo(items.next_page_url)"
                >
                    Siguiente
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    sellers: {
        type: Array,
        default: () => [],
    },
});

const filtersForm = reactive({
    q: props.filters.q || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    seller_id: props.filters.seller_id || '',
});

const formatPrice = (val) => Number(val ?? 0).toFixed(2);

const goTo = (link) => {
    if (link) {
        router.visit(link, { preserveState: true, preserveScroll: true });
    }
};

const applyFilters = () => {
    router.get('/admin/sales', filtersForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.q = '';
    filtersForm.date_from = '';
    filtersForm.date_to = '';
    filtersForm.seller_id = '';
    applyFilters();
};
</script>
