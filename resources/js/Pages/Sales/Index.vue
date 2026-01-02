<template>
    <AppLayout title="Ventas">
        <div class="rounded-2xl bg-gray-900/80 ring-1 ring-black/30">
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
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
});

const formatPrice = (val) => Number(val ?? 0).toFixed(2);

const goTo = (link) => {
    if (link) {
        router.visit(link);
    }
};
</script>
