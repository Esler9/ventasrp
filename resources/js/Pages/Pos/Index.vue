<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <div class="space-y-5 px-3 pb-24 pt-4 max-w-4xl mx-auto">
            <div class="rounded-3xl bg-gray-900 p-4 shadow-lg ring-1 ring-black/30">
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nombre o código"
                            class="w-full rounded-2xl border border-gray-800 bg-gray-850 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        @click="emitScan"
                    >
                        <span class="text-lg">📷</span>
                        Escanear
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                <template v-if="products.length">
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="rounded-3xl bg-gray-900 p-4 shadow-lg ring-1 ring-black/30"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div class="text-base font-semibold text-gray-50">{{ product.name }}</div>
                                <div class="text-xs text-gray-400">SKU: {{ product.sku }}</div>
                                <div class="text-xs text-gray-400">Stock: {{ product.stock }}</div>
                                <div v-if="product.expires_at" class="text-xs text-gray-400">
                                    Vence: {{ product.expires_at }}
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <div class="text-lg font-semibold text-gray-50">Q{{ formatPrice(product.price) }}</div>
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    @click="openSale(product)"
                                >
                                    🛒 Vender
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <div
                    v-else
                    class="rounded-3xl bg-gray-900 p-6 text-center text-sm text-gray-400 shadow-lg ring-1 ring-black/30"
                >
                    Sin productos. Busca otro término o crea uno nuevo.
                </div>
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-40 bg-gray-900/90 backdrop-blur">
            <div class="mx-auto flex max-w-4xl items-center justify-around rounded-t-3xl bg-gray-900 px-6 py-3 text-xs text-gray-300">
                <button class="flex flex-col items-center gap-1 text-amber-400">
                    <span class="text-lg">🛒</span>
                    <span>Ventas</span>
                </button>
                <button class="flex flex-col items-center gap-1">
                    <span class="text-lg">📦</span>
                    <span>Inventario</span>
                </button>
                <button class="flex flex-col items-center gap-1">
                    <span class="text-lg">🕓</span>
                    <span>Historial</span>
                </button>
                <button class="flex flex-col items-center gap-1">
                    <span class="text-lg">⚙️</span>
                    <span>Ajustes</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash.debounce';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const search = ref(props.filters.q || '');
const products = ref(props.products);

watch(
    () => props.products,
    (val) => {
        products.value = val;
    },
);

const debouncedSearch = debounce((value) => {
    router.get(
        '/pos',
        { q: value },
        {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        },
    );
}, 300);

watch(search, (val) => {
    debouncedSearch(val);
});

const formatPrice = (val) => Number(val ?? 0).toFixed(2);

const emitScan = () => {
    window.dispatchEvent(new Event('pos-scan'));
};

const openSale = (product) => {
    // Placeholder: aquí deberías abrir tu modal/flow de venta en Vue.
    alert(`Vender ${product.name}`);
};
</script>

<style scoped>
.bg-gray-850 {
    background-color: #1f2937;
}
</style>
