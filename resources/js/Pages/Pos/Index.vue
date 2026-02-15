<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <GlobalLoader />
        <div class="px-4 pt-3">
            <FlashBanner />
        </div>

        <div class="mx-auto max-w-6xl space-y-4 px-4 pb-36 pt-4" :style="contentSafeArea">
            <header class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold">POS</h1>
                        <p class="text-xs text-gray-400">Venta por código y cliente (CF por defecto)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="h-10 w-10 rounded-full border border-gray-700 bg-gray-950/60" @click="toggleTheme" title="Tema">
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                        </button>
                        <button type="button" class="h-10 w-10 rounded-full border border-gray-700 bg-gray-950/60" @click="logout" title="Salir">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </div>
                </div>

                <div v-if="!openCashSession" class="rounded-xl border border-red-700/60 bg-red-900/20 p-3 text-sm text-red-200">
                    Debes abrir caja antes de vender.
                    <a href="/admin/cash" class="underline font-semibold">Ir a Caja</a>
                </div>
                <div v-else class="rounded-xl border border-emerald-700/40 bg-emerald-900/10 p-3 text-xs text-emerald-200">
                    Caja activa: {{ openCashSession.register }} · {{ openCashSession.branch }}
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Código de venta</label>
                        <input v-model="saleForm.sale_code" type="text" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm" />
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs text-gray-400">Cliente</label>
                        <input v-model="saleForm.customer_name" type="text" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm" />
                    </div>
                </div>
            </header>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4 space-y-3">
                <label class="text-xs text-gray-400">Buscar producto</label>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Nombre, SKU o descripción"
                    class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm"
                />

                <div class="grid gap-3 lg:grid-cols-2">
                    <article
                        v-for="product in products"
                        :key="product.id"
                        class="rounded-xl border border-gray-800 bg-gray-950/60 p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ product.name }}</p>
                                <p class="text-xs text-gray-400">SKU: {{ product.sku || '—' }}</p>
                                <p class="text-xs text-gray-400">Stock: {{ product.stock }}</p>
                            </div>
                            <p class="text-sm font-semibold">Q{{ money(product.price) }}</p>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-xs" @click="addProduct(product)">
                                Agregar
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Carrito</h2>
                    <button type="button" class="text-xs text-gray-400 underline" @click="clearCart">Vaciar</button>
                </div>

                <div v-if="cart.length" class="space-y-3">
                    <article v-for="(item, index) in cart" :key="item.row_key" class="rounded-xl border border-gray-800 bg-gray-950/60 p-3 space-y-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-semibold">{{ item.name }}</p>
                                <p class="text-xs text-gray-400">{{ item.presentation_name }} · factor {{ item.presentation_factor }}</p>
                            </div>
                            <button type="button" class="text-xs text-red-300" @click="removeItem(index)">Eliminar</button>
                        </div>

                        <div class="grid gap-2 md:grid-cols-3">
                            <div>
                                <label class="text-xs text-gray-500">Cantidad</label>
                                <input v-model.number="item.quantity" type="number" min="1" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Precio</label>
                                <input v-model.number="item.price" :disabled="!canChangePrice" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm disabled:opacity-60" />
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Subtotal</p>
                                <p class="text-base font-semibold">Q{{ money(item.quantity * item.price) }}</p>
                            </div>
                        </div>
                    </article>

                    <div class="rounded-xl border border-gray-700 bg-gray-950/60 p-3 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Total</span>
                            <strong>Q{{ money(total) }}</strong>
                        </div>

                        <div class="space-y-2">
                            <p class="text-xs text-gray-400">Pagos</p>
                            <div v-for="(payment, idx) in saleForm.payments" :key="idx" class="grid gap-2 grid-cols-5">
                                <select v-model="payment.method" class="col-span-2 rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm">
                                    <option value="cash">Efectivo</option>
                                    <option value="card">Tarjeta</option>
                                    <option value="transfer">Transferencia</option>
                                </select>
                                <input v-model.number="payment.amount" type="number" min="0.01" step="0.01" class="col-span-2 rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm" />
                                <button type="button" class="rounded-lg border border-gray-700 text-xs" @click="removePayment(idx)">X</button>
                            </div>
                            <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-xs" @click="addPayment">Agregar método</button>
                            <p class="text-xs" :class="paymentsMatch ? 'text-emerald-300' : 'text-red-300'">
                                Pagado: Q{{ money(paymentsTotal) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-sm text-gray-400">Aún no has agregado productos.</div>

                <div v-if="saleForm.errors.sale" class="text-sm text-red-400">{{ saleForm.errors.sale }}</div>

                <button
                    type="button"
                    class="w-full rounded-xl bg-amber-400 px-4 py-3 text-base font-semibold text-black disabled:opacity-50"
                    :disabled="!canSubmit"
                    @click="submitSale"
                >
                    Registrar venta
                </button>
            </section>
        </div>

        <BottomNav />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import BottomNav from '../../Components/BottomNav.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import GlobalLoader from '../../Components/GlobalLoader.vue';
import { useTheme } from '../../composables/useTheme';

const props = defineProps({
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '' }) },
    defaults: { type: Object, default: () => ({ sale_code: '', customer_name: 'CF' }) },
    open_cash_session: { type: Object, default: null },
});

const { theme, toggleTheme } = useTheme();
const page = usePage();
const search = ref(props.filters.q || '');
const products = ref(props.products);
const cart = ref([]);

const saleForm = useForm({
    sale_code: props.defaults.sale_code || '',
    customer_name: props.defaults.customer_name || 'CF',
    items: [],
    payments: [{ method: 'cash', amount: 0 }],
});

const openCashSession = computed(() => props.open_cash_session);
const canChangePrice = computed(() => {
    const permissions = page.props.auth?.user?.permissions || [];
    return permissions.includes('*') || permissions.includes('pos.change_price');
});

watch(
    () => props.products,
    (value) => {
        products.value = value;
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
    router.get('/pos', { q: value }, { replace: true, preserveState: true, preserveScroll: true });
});

watch(search, (value) => debouncedSearch(value));

const addProduct = (product) => {
    const existing = cart.value.find((item) => item.product_id === product.id && item.presentation_name === (product.unit_label || 'Unidad'));
    if (existing) {
        existing.quantity += 1;
        return;
    }

    cart.value.push({
        row_key: `${product.id}-${Date.now()}`,
        product_id: product.id,
        product_presentation_id: null,
        name: product.name,
        presentation_name: product.unit_label || 'Unidad',
        presentation_factor: 1,
        quantity: 1,
        price: Number(product.price || 0),
        note: '',
    });
};

const removeItem = (index) => {
    cart.value.splice(index, 1);
};

const clearCart = () => {
    cart.value = [];
};

const total = computed(() => cart.value.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.price || 0), 0));
const paymentsTotal = computed(() => saleForm.payments.reduce((sum, p) => sum + Number(p.amount || 0), 0));
const paymentsMatch = computed(() => Math.abs(paymentsTotal.value - total.value) < 0.01);

watch(total, (value) => {
    if (saleForm.payments.length === 1 && saleForm.payments[0].method === 'cash') {
        saleForm.payments[0].amount = Number(value.toFixed(2));
    }
});

const addPayment = () => {
    saleForm.payments.push({ method: 'cash', amount: 0 });
};

const removePayment = (index) => {
    if (saleForm.payments.length === 1) return;
    saleForm.payments.splice(index, 1);
};

const canSubmit = computed(() => openCashSession.value && cart.value.length > 0 && paymentsMatch.value && !saleForm.processing);

const submitSale = () => {
    saleForm.items = cart.value.map((item) => ({
        product_id: item.product_id,
        product_presentation_id: item.product_presentation_id,
        presentation_name: item.presentation_name,
        presentation_factor: item.presentation_factor,
        quantity: Number(item.quantity || 0),
        price: Number(item.price || 0),
        note: item.note || null,
    }));

    saleForm.post('/pos/sales', {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            saleForm.payments = [{ method: 'cash', amount: 0 }];
            saleForm.sale_code = '';
            saleForm.customer_name = 'CF';
        },
    });
};

const contentSafeArea = computed(() => ({
    paddingBottom: 'calc(6rem + env(safe-area-inset-bottom, 0px))',
}));

const logoutForm = useForm({});
const logout = () => logoutForm.post('/logout');

const money = (value) => Number(value || 0).toFixed(2);
</script>
