<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <GlobalLoader />
        <div class="px-4 pt-3">
            <FlashBanner />
        </div>
        <div class="max-w-5xl mx-auto px-4 pt-6 space-y-6 fade-in-soft" :style="contentSafeArea">
            <header class="space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-semibold text-gray-50">Punto de venta</h1>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-800/80 bg-gray-900/70 text-gray-200 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-400/80 hover:bg-amber-500/10 hover:text-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-400/50 active:scale-95"
                            aria-label="Cambiar tema"
                            title="Cambiar tema"
                            @click="toggleTheme"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun text-lg' : 'fa-solid fa-moon text-lg'"></i>
                        </button>
                    <button
                        type="button"
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-800/80 bg-gray-900/70 text-gray-200 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-red-400/80 hover:bg-red-500/15 hover:text-red-200 focus:outline-none focus:ring-2 focus:ring-red-400/50 active:scale-95"
                        aria-label="Cerrar sesión"
                        title="Cerrar sesión"
                        @click="logout"
                    >
                        <i class="fa-solid fa-power-off text-xl"></i>
                    </button>
                    </div>
                </div>
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
                            class="hidden sm:flex items-center gap-2 rounded-2xl bg-amber-400 px-5 py-3.5 text-base font-semibold text-black shadow-lg shadow-amber-500/30 transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 active:scale-95"
                            @click="startScanner"
                        >
                            <i class="fa-solid fa-qrcode text-base"></i>
                            Escanear
                        </button>
                    </div>
                    <button
                        type="button"
                        class="sm:hidden flex items-center justify-center gap-2 rounded-2xl bg-amber-400 px-5 py-3.5 text-base font-semibold text-black shadow-lg shadow-amber-500/30 transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 active:scale-95"
                        @click="startScanner"
                    >
                        <i class="fa-solid fa-qrcode text-base"></i>
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
                                    <div class="text-xs text-gray-400">Unidad base: {{ product.unit_label || 'Unidad' }}</div>
                                    <div class="text-xs text-gray-400">
                                        {{ shortDescription(product.description) }}
                                    </div>
                                    <div class="text-xs text-gray-400">Stock: {{ product.stock }}</div>
                                    <div v-if="product.expires_at" class="text-xs text-gray-400">
                                        Vence: {{ product.expires_at }}
                                    </div>
                                </div>
                                <div class="flex items-center justify-between sm:flex-col sm:items-end gap-3">
                                    <div class="text-lg font-semibold text-gray-50">Q{{ formatPrice(product.price) }}</div>
                                    <button
                                        type="button"
                                        class="flex items-center gap-2 rounded-2xl bg-amber-400 px-5 py-3 text-base font-semibold text-black shadow-lg shadow-amber-500/30 transition-all duration-150 hover:-translate-y-0.5 hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 active:scale-95"
                                        @click="openSale(product)"
                                    >
                                        <i class="fa-solid fa-cart-plus text-base"></i>
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

        <BottomNav />

        <div
            v-if="showToast"
            class="fixed left-1/2 top-4 z-50 -translate-x-1/2 rounded-xl border px-4 py-2 text-sm font-semibold shadow-lg"
            :class="toastTone === 'error' ? 'border-red-300/60 bg-red-100/90 text-red-900' : 'border-amber-300/60 bg-amber-100/90 text-amber-900'"
        >
            {{ toastMessage }}
        </div>

        <div
            v-if="showScanner"
            class="fixed inset-0 z-50 bg-black/80 px-4 py-6 flex flex-col gap-3"
        >
            <div class="flex items-center justify-between text-gray-100">
                <h3 class="text-lg font-semibold">Escanear código</h3>
                <button type="button" class="text-sm text-gray-300 underline" @click="stopScanner(false)">Cerrar</button>
            </div>
            <div v-if="!fallbackManual" class="relative flex-1 rounded-2xl border border-gray-800 bg-black overflow-hidden">
                <video ref="videoRef" class="h-full w-full object-cover" autoplay playsinline muted></video>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="h-32 w-64 rounded-xl border-2 border-amber-400/80"></div>
                </div>
            </div>
            <div v-else class="space-y-3">
                <p class="text-sm text-gray-200">Ingresa el código manualmente</p>
                <input
                    v-model="search"
                    type="text"
                    inputmode="numeric"
                    autofocus
                    class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-lg text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                    placeholder="Ej. 7501234567890"
                />
                <button
                    type="button"
                    class="w-full rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                    @click="applyManualSearch"
                >
                    Buscar
                </button>
                <div class="space-y-1 text-xs text-gray-400">
                    <p>O sube una foto de un código QR (respaldo con jsQR):</p>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-gray-700 bg-gray-900 px-3 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                            @click="pickImage"
                        >
                            Subir foto
                        </button>
                        <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="onImageSelected" capture="environment" />
                    </div>
                </div>
            </div>
            <p v-if="scannerError" class="text-sm text-red-400">{{ scannerError }}</p>
            <p class="text-xs text-gray-400">Apunta al código de barras, se llenará la búsqueda automáticamente.</p>
            <div class="text-xs text-gray-500">
                Si no ves la cámara en Safari, revisa permisos (Ajustes &gt; Safari &gt; Cámara) o usa el ingreso manual.
            </div>
            <button
                type="button"
                class="w-full rounded-xl border border-gray-700 bg-gray-900 px-4 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                @click="stopScanner(false)"
            >
                Cerrar
            </button>
        </div>

        <div
            v-if="showModal && selectedProduct"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 overflow-y-auto"
        >
            <div class="w-full max-w-md rounded-2xl bg-gray-900 p-5 shadow-2xl ring-1 ring-black/30 text-gray-100">
                <div class="flex items-start gap-3">
                    <div class="h-14 w-14 rounded-xl bg-gray-850 overflow-hidden ring-1 ring-black/30 flex-shrink-0">
                    <img
                        v-if="selectedProduct.photo_url"
                        :src="selectedProduct.photo_url"
                        alt="Foto"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 6 6m0 0 4-4 8 8M13 13h6v6M3 5h6v6" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">Confirmar venta</h3>
                            <p class="text-sm text-gray-400">{{ selectedProduct.name }} · SKU: {{ selectedProduct.sku }}</p>
                            <p class="text-xs text-gray-500">Stock disponible: {{ selectedProduct.stock }} {{ selectedProduct.unit_label || 'unidades' }}</p>
                            <p v-if="selectedProduct.expires_at" class="text-xs text-amber-300">Vence: {{ selectedProduct.expires_at }}</p>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-white" @click="closeModal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitSale">
                <div class="space-y-1">
                    <label class="text-sm text-gray-300">Presentación</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            v-for="option in presentationOptions"
                            :key="option.key"
                            type="button"
                            class="rounded-lg border px-3 py-2 text-left text-sm transition"
                            :class="option.key === selectedPresentationKey ? 'border-amber-400 bg-amber-400/10 text-amber-100' : 'border-gray-800 bg-gray-950/60 text-gray-100 hover:border-amber-300/40'"
                            @click="selectPresentation(option)"
                        >
                            <div class="font-semibold">{{ option.name }}</div>
                            <div class="text-xs text-gray-400">Descuenta {{ option.factor }} {{ selectedProduct.unit_label || 'unidad(es)' }}</div>
                            <div class="text-xs text-gray-300">Q{{ formatPrice(option.price) }}</div>
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Cantidad</label>
                        <input
                            type="number"
                            min="1"
                            v-model.number="form.quantity"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        >
                        <p class="mt-1 text-xs text-gray-500">Equivalente a {{ form.quantity * form.presentation_factor }} {{ selectedProduct.unit_label || 'unidades' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Precio por presentación</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            v-model.number="form.price"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        >
                        <p class="mt-1 text-xs text-gray-500">Total: Q{{ formatPrice((form.price || 0) * (form.quantity || 0)) }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-200">Nota (opcional)</label>
                    <textarea
                        rows="2"
                        v-model="form.note"
                        class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                    ></textarea>
                </div>
                <div v-if="form.errors.sale" class="text-sm text-red-400">
                    {{ form.errors.sale }}
                </div>
                <div v-if="saleErrorActionUrl" class="text-xs text-amber-300">
                    Stock insuficiente. <a :href="saleErrorActionUrl" class="underline">Editar stock</a>
                </div>

                <div class="mt-2 flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-200 hover:bg-gray-800"
                        @click="closeModal"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        :disabled="form.processing"
                    >
                        Vender
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import BottomNav from '../../Components/BottomNav.vue';
import GlobalLoader from '../../Components/GlobalLoader.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import Quagga from '@ericblade/quagga2';
import jsQR from 'jsqr';
import { useTheme } from '../../composables/useTheme';

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
const selectedPresentation = ref(null);
const showToast = ref(false);
const toastMessage = ref('Venta registrada');
const toastTone = ref('success');
const saleErrorActionUrl = ref(null);
const showScanner = ref(false);
const scannerError = ref('');
const videoRef = ref(null);
const fallbackManual = ref(false);
const fileInputRef = ref(null);
const { theme, toggleTheme } = useTheme();
let mediaStream = null;
let detector = null;
let quaggaRunning = false;

const form = useForm({
    product_id: null,
    product_presentation_id: null,
    presentation_name: '',
    presentation_factor: 1,
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
const shortDescription = (text) => {
    if (!text) return 'Sin descripción';
    return text.length > 90 ? `${text.slice(0, 87)}...` : text;
};

const presentationOptions = computed(() => {
    if (!selectedProduct.value) return [];
    const baseOption = {
        key: 'base',
        id: null,
        name: selectedProduct.value.unit_label || 'Unidad',
        factor: 1,
        price: selectedProduct.value.price,
    };
    const extras =
        selectedProduct.value.presentations?.map((p) => ({
            key: `p-${p.id ?? p.name}`,
            id: p.id ?? null,
            name: p.name,
            factor: p.factor || 1,
            price: p.price,
        })) ?? [];

    return [baseOption, ...extras];
});

const selectedPresentationKey = computed(() => selectedPresentation.value?.key ?? null);

const selectPresentation = (option) => {
    selectedPresentation.value = option;
    form.product_presentation_id = option.id;
    form.presentation_name = option.name;
    form.presentation_factor = option.factor;
    form.price = option.price;
};

const startScanner = async () => {
    scannerError.value = '';
    showScanner.value = true;
    fallbackManual.value = false;
    if (!navigator.mediaDevices?.getUserMedia) {
        scannerError.value = 'Tu dispositivo no permite acceso a cámara.';
        return;
    }
    if ('BarcodeDetector' in window) {
        try {
            detector = new window.BarcodeDetector({ formats: ['code_128', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_39'] });
            mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
            await nextTick();
            if (videoRef.value) {
                videoRef.value.srcObject = mediaStream;
                videoRef.value.play().catch(() => {});
                requestAnimationFrame(scanFrame);
            }
            return;
        } catch (e) {
            // fallback to Quagga
        }
    }

    startQuaggaFallback();
};

const scanFrame = async () => {
    if (!videoRef.value || videoRef.value.readyState < 2 || !detector) {
        requestAnimationFrame(scanFrame);
        return;
    }
    try {
        const barcodes = await detector.detect(videoRef.value);
        if (barcodes.length) {
            const code = barcodes[0].rawValue;
            search.value = code;
            showScanner.value = false;
            stopScanner();
            debouncedSearch(code);
            toastMessage.value = `Código: ${code}`;
            showToast.value = true;
            setTimeout(() => (showToast.value = false), 1600);
            return;
        }
    } catch (e) {
        // swallow and retry
    }
    if (showScanner.value) {
        requestAnimationFrame(scanFrame);
    }
};

const stopScanner = (keepOverlay = false) => {
    showScanner.value = keepOverlay;
    if (mediaStream) {
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
    }
    detector = null;
    if (quaggaRunning) {
        Quagga.stop();
        quaggaRunning = false;
    }
};

const openSale = (product) => {
    selectedProduct.value = product;
    form.product_id = product.id;
    form.quantity = 1;
    form.note = '';
    saleErrorActionUrl.value = null;
    const options = presentationOptions.value;
    const firstOption = options[0] || { key: 'base', id: null, name: product.unit_label || 'Unidad', factor: 1, price: product.price };
    selectPresentation(firstOption);
    showModal.value = true;
};

const submitSale = () => {
    form.post('/pos/sales', {
        preserveScroll: true,
        onError: (errors) => {
            toastTone.value = 'error';
            toastMessage.value = errors.sale || 'No se pudo registrar la venta';
            if (errors.sale?.toLowerCase().includes('stock') && selectedProduct.value?.id) {
                saleErrorActionUrl.value = `/admin/products/${selectedProduct.value.id}/edit`;
            }
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 2200);
        },
        onSuccess: () => {
            showModal.value = false;
            showToast.value = false;
            const total = (form.price || 0) * (form.quantity || 0);
            toastTone.value = 'success';
            toastMessage.value = `Venta: ${form.quantity} x ${form.presentation_name || selectedProduct.value?.unit_label || 'Unidad'} · Q${formatPrice(total)}`;
            saleErrorActionUrl.value = null;
            requestAnimationFrame(() => {
                showToast.value = true;
                setTimeout(() => {
                    showToast.value = false;
                }, 1800);
            });
        },
    });
};

const closeModal = () => {
    showModal.value = false;
};

const contentSafeArea = computed(() => ({
    paddingBottom: 'calc(8rem + env(safe-area-inset-bottom, 0px))',
}));

const logoutForm = useForm({});
const logout = () => {
    logoutForm.post('/logout');
};

const applyManualSearch = () => {
    showScanner.value = false;
    if (search.value) {
        debouncedSearch(search.value);
        toastMessage.value = `Código: ${search.value}`;
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 1600);
    }
};

const pickImage = () => {
    fileInputRef.value?.click();
};

const onImageSelected = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const result = jsQR(imageData.data, imageData.width, imageData.height);
            if (result?.data) {
                search.value = result.data;
                applyManualSearch();
            } else {
                scannerError.value = 'No se detectó un QR en la imagen.';
            }
        };
        img.src = reader.result;
    };
    reader.readAsDataURL(file);
};

const startQuaggaFallback = async () => {
    try {
        await nextTick();
        const constraints = {
            facingMode: 'environment',
            width: { ideal: 640 },
            height: { ideal: 480 },
        };

        Quagga.init(
            {
                inputStream: {
                    name: 'Live',
                    type: 'LiveStream',
                    target: videoRef.value,
                    constraints,
                },
                decoder: {
                    readers: ['ean_reader', 'ean_8_reader', 'code_128_reader', 'upc_reader', 'upc_e_reader'],
                },
                locate: true,
            },
            (err) => {
                if (err) {
                    scannerError.value = 'No se pudo iniciar el escáner. Usa el ingreso manual.';
                    fallbackManual.value = true;
                    return;
                }
                Quagga.start();
                quaggaRunning = true;
                Quagga.onDetected((result) => {
                    const code = result?.codeResult?.code;
                    if (!code) return;
                    stopScanner(false);
                    search.value = code;
                    debouncedSearch(code);
                    toastMessage.value = `Código: ${code}`;
                    showToast.value = true;
                    setTimeout(() => (showToast.value = false), 1600);
                });
            },
        );
    } catch (e) {
        scannerError.value = 'Tu navegador no soporta escaneo. Usa el ingreso manual.';
        fallbackManual.value = true;
    }
};

onBeforeUnmount(() => {
    stopScanner();
});
</script>

<style scoped>
.bg-gray-850 {
    background-color: #1f2937;
}
</style>
