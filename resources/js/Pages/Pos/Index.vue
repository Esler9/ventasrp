<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <div class="max-w-5xl mx-auto px-4 pb-28 pt-6 space-y-6">
            <header class="space-y-3">
                <h1 class="text-2xl font-semibold text-gray-50">Punto de venta</h1>
                <div class="rounded-3xl bg-gray-900 p-4 shadow-lg ring-1 ring-black/30 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 5.64 5.64a7.5 7.5 0 0 0 10.01 10.01Z" />
                                </svg>
                            </span>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por nombre o código"
                                class="w-full rounded-2xl border border-gray-800 bg-gray-850 pl-10 pr-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                            />
                        </div>
                        <button
                            type="button"
                            class="hidden sm:flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                            @click="emitScan"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h2l1-2h6l1 2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6v6H9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h.01M17 12h.01" />
                            </svg>
                            Escanear
                        </button>
                    </div>
                    <button
                        type="button"
                        class="sm:hidden flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        @click="emitScan"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h2l1-2h6l1 2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6v6H9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h.01M17 12h.01" />
                        </svg>
                        Escanear
                    </button>
                </div>
            </header>

            <main class="space-y-3">
                <template v-if="products.length">
                    <article
                        v-for="product in products"
                        :key="product.id"
                        class="rounded-3xl bg-gray-900 p-4 shadow-lg ring-1 ring-black/30"
                    >
                        <div class="flex gap-3 sm:gap-4">
                            <div class="h-16 w-16 rounded-2xl bg-gray-850 overflow-hidden flex-shrink-0 ring-1 ring-black/20">
                                <img
                                    v-if="product.photo_url"
                                    :src="product.photo_url"
                                    alt="Foto"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-gray-600 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 6 6m0 0 4-4 8 8M13 13h6v6M3 5h6v6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-1">
                                    <div class="text-base font-semibold text-gray-50">{{ product.name }}</div>
                                    <div class="text-xs text-gray-400">SKU: {{ product.sku }}</div>
                                    <div class="text-xs text-gray-400">Stock: {{ product.stock }}</div>
                                    <div v-if="product.expires_at" class="text-xs text-gray-400">
                                        Vence: {{ product.expires_at }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between sm:flex-col sm:items-end gap-3">
                                    <div class="text-lg font-semibold text-gray-50">Q{{ formatPrice(product.price) }}</div>
                                    <button
                                        type="button"
                                        class="flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        @click="openSale(product)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h11l1-5H6.4M7 13 5.4 5M7 13l-2 9h14M10 21a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm8 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
                                        </svg>
                                        Vender
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
                <div
                    v-else
                    class="rounded-3xl bg-gray-900 p-6 text-center text-sm text-gray-400 shadow-lg ring-1 ring-black/30"
                >
                    Sin productos. Busca otro término o crea uno nuevo.
                </div>
            </main>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-40 bg-gray-900/90 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-around rounded-t-3xl bg-gray-900 px-6 py-3 text-xs text-gray-300">
                <a href="/pos" class="flex flex-col items-center gap-1 text-amber-400">
                    <span class="text-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M5 7l1 12h12l1-12M9 11v2m6-2v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
                        </svg>
                    </span>
                    <span>POS</span>
                </a>
                <a href="/admin/products" class="flex flex-col items-center gap-1">
                    <span class="text-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8-4 8 4m-14 0v10l6 3m0-13 6-3v10l-6 3m0-13v13" />
                        </svg>
                    </span>
                    <span>Productos</span>
                </a>
                <a href="/admin/sales" class="flex flex-col items-center gap-1">
                    <span class="text-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v4H4V7Zm0 6h16v4H4v-4Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 9h.01M7 15h.01m4-6h6M11 15h6" />
                        </svg>
                    </span>
                    <span>Ventas</span>
                </a>
                <a href="/admin" class="flex flex-col items-center gap-1">
                    <span class="text-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m-8-8h16" />
                        </svg>
                    </span>
                    <span>Panel</span>
                </a>
            </div>
        </nav>

        <div
            v-if="showModal && selectedProduct"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-gray-900 p-5 shadow-2xl ring-1 ring-black/30 text-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Confirmar venta</h3>
                        <p class="text-sm text-gray-400">{{ selectedProduct.name }} · SKU: {{ selectedProduct.sku }}</p>
                        <p class="text-xs text-gray-500">Stock disponible: {{ selectedProduct.stock }}</p>
                        <p v-if="selectedProduct.expires_at" class="text-xs text-amber-300">Vence: {{ selectedProduct.expires_at }}</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-white" @click="closeModal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Cantidad</label>
                        <input
                            type="number"
                            min="1"
                            v-model.number="form.quantity"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        >
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Precio unitario</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            v-model.number="form.price"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        >
                        <p class="mt-1 text-xs text-gray-500">Puedes ajustar el precio. La diferencia queda registrada como descuento.</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Nota (opcional)</label>
                        <textarea
                            rows="2"
                            v-model="form.note"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        ></textarea>
                    </div>
                    <div class="flex justify-between rounded-lg bg-gray-800 p-3 text-sm font-semibold text-gray-100">
                        <span>Total</span>
                        <span>Q{{ formatPrice((form.price || 0) * (form.quantity || 0)) }}</span>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-200 hover:bg-gray-800"
                        @click="closeModal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        @click="submitSale"
                        :disabled="form.processing"
                    >
                        Vender
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

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
const showModal = ref(false);
const selectedProduct = ref(null);

const form = useForm({
    product_id: null,
    quantity: 1,
    price: '',
    note: '',
});

watch(
    () => props.products,
    (val) => {
        products.value = val;
    },
);

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

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
});

watch(search, (val) => {
    debouncedSearch(val);
});

const formatPrice = (val) => Number(val ?? 0).toFixed(2);

const emitScan = () => {
    window.dispatchEvent(new Event('pos-scan'));
};

const openSale = (product) => {
    selectedProduct.value = product;
    form.product_id = product.id;
    form.quantity = 1;
    form.price = product.price;
    form.note = '';
    showModal.value = true;
};

const submitSale = () => {
    form.post('/pos/sales', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const closeModal = () => {
    showModal.value = false;
};
</script>

<style scoped>
.bg-gray-850 {
    background-color: #1f2937;
}
</style>
