<template>
    <AppLayout title="Productos">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-md">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 5.64 5.64a7.5 7.5 0 0 0 10.01 10.01Z" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombre o código"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 py-2.5 pl-9 pr-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <a
                        href="/admin/products/create"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-semibold text-black shadow hover:bg-amber-300"
                    >
                        Crear producto
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-gray-900/80 ring-1 ring-black/30">
                <div class="hidden grid-cols-12 gap-2 bg-gray-900 px-4 py-3 text-sm font-semibold text-gray-300 md:grid">
                    <div class="col-span-4">Producto</div>
                    <div class="col-span-2">SKU</div>
                    <div class="col-span-2 text-right">Precio</div>
                    <div class="col-span-2 text-right">Stock</div>
                    <div class="col-span-2 text-right">Acciones</div>
                </div>

                <div v-if="products.data.length" class="divide-y divide-gray-800">
                    <article
                        v-for="product in products.data"
                        :key="product.id"
                        class="grid grid-cols-1 gap-3 px-4 py-3 md:grid-cols-12 md:items-center"
                    >
                        <div class="flex items-center gap-3 md:col-span-4">
                            <div class="h-12 w-12 overflow-hidden rounded-xl bg-gray-850 ring-1 ring-black/20">
                                <img
                                    v-if="product.photo_url"
                                    :src="product.photo_url"
                                    alt="Foto"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-gray-600 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 6 6m0 0 4-4 8 8M13 13h6v6M3 5h6v6" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-50">{{ product.name }}</p>
                                <p class="text-xs text-gray-500">Vence: {{ product.expires_at || '—' }}</p>
                                <p class="text-xs text-gray-500">Activo: {{ product.is_active ? 'Sí' : 'No' }}</p>
                            </div>
                        </div>
                        <div class="md:col-span-2 md:text-left">
                            <p class="text-sm text-gray-200">{{ product.sku }}</p>
                        </div>
                        <div class="md:col-span-2 md:text-right">
                            <p class="text-sm font-semibold text-gray-100">Q{{ formatPrice(product.price) }}</p>
                        </div>
                        <div class="md:col-span-2 md:text-right">
                            <p class="text-sm text-gray-200">{{ product.stock }}</p>
                        </div>
                        <div class="flex gap-2 md:col-span-2 md:justify-end">
                            <a
                                :href="`/admin/products/${product.id}/edit`"
                                class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800"
                            >
                                Editar
                            </a>
                        </div>
                    </article>
                </div>

                <div v-else class="px-4 py-6 text-center text-sm text-gray-400">
                    No hay productos. Crea uno nuevo.
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-500">
                <div>Mostrando {{ products.data.length }} de {{ products.total }} resultados</div>
                <div class="flex gap-2">
                    <button
                        class="rounded-lg border border-gray-700 px-3 py-1 text-xs"
                        :disabled="!products.prev_page_url"
                        @click="goTo(products.prev_page_url)"
                    >
                        Anterior
                    </button>
                    <button
                        class="rounded-lg border border-gray-700 px-3 py-1 text-xs"
                        :disabled="!products.next_page_url"
                        @click="goTo(products.next_page_url)"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const search = ref(props.filters.q || '');

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const debouncedSearch = debounce((value) => {
    router.get('/admin/products', { q: value }, { preserveState: true, replace: true, preserveScroll: true });
});

watch(search, (val) => debouncedSearch(val));

const formatPrice = (val) => Number(val ?? 0).toFixed(2);

const goTo = (link) => {
    if (link) {
        router.visit(link);
    }
};
</script>

<style scoped>
.bg-gray-850 {
    background-color: #1f2937;
}
</style>
