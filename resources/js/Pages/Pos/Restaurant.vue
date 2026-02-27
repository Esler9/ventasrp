<template>
    <AppLayout title="POS Restaurante">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div>
                    <h1 class="text-lg font-semibold text-gray-100">POS Restaurante</h1>
                    <p class="text-xs text-gray-400">Mesas, cuentas y envío de órdenes a cocina.</p>
                </div>
                <a href="/pos/kitchen" class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm font-semibold text-amber-300">
                    Ver cocina
                </a>
            </div>

            <div v-if="firstError" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200">
                {{ firstError }}
            </div>

            <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-200">Mesas</p>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-700 px-2 py-1 text-xs text-gray-300"
                            :disabled="!selectedTable"
                            @click="openAccountModal"
                        >
                            + Cuenta
                        </button>
                    </div>

                    <div class="space-y-2">
                        <button
                            v-for="table in tables"
                            :key="table.id"
                            type="button"
                            class="w-full rounded-xl border px-3 py-2 text-left"
                            :class="selectedTableId === table.id ? 'border-amber-400 bg-amber-500/10' : 'border-gray-800 bg-gray-950/40'"
                            @click="selectTable(table)"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-100">{{ table.name }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="table.status === 'occupied' ? 'bg-rose-500/20 text-rose-200' : 'bg-emerald-500/20 text-emerald-200'"
                                >
                                    {{ table.status === 'occupied' ? 'Ocupada' : 'Libre' }}
                                </span>
                            </div>
                            <p class="mt-1 text-[11px] text-gray-400">{{ table.is_takeaway ? 'Mesa virtual' : 'Mesa salón' }}</p>

                            <div v-if="table.accounts.length" class="mt-2 flex flex-wrap gap-1">
                                <button
                                    v-for="account in table.accounts"
                                    :key="account.id"
                                    type="button"
                                    class="rounded-md border px-2 py-1 text-[11px]"
                                    :class="selectedAccountId === account.id ? 'border-amber-400 bg-amber-500/15 text-amber-100' : 'border-gray-700 bg-gray-900 text-gray-200'"
                                    @click.stop="selectAccount(table, account)"
                                >
                                    {{ account.label }} · {{ account.items_count }} item(s)
                                </button>
                            </div>
                        </button>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <div class="mb-3 flex flex-wrap items-end gap-2">
                        <div class="flex-1 min-w-[220px]">
                            <label class="text-xs text-gray-400">Buscar</label>
                            <input v-model="search" type="text" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100" placeholder="Producto o SKU" />
                        </div>
                        <div class="w-full sm:w-56">
                            <label class="text-xs text-gray-400">Categoría</label>
                            <select v-model.number="selectedCategoryId" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100">
                                <option :value="0">Todas</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="selectedAccount" class="mb-3 rounded-xl border border-gray-800 bg-gray-950/40 p-3 text-sm text-gray-200">
                        <p class="font-semibold">Cuenta activa: {{ selectedAccount.label }}</p>
                        <p class="text-xs text-gray-400">
                            Mesa: {{ selectedTable?.name }} · Borrador: {{ selectedAccount.draft_items }} · Pendientes: {{ selectedAccount.pending_items }} · En preparación: {{ selectedAccount.preparing_items }} · Listos: {{ selectedAccount.ready_items }} · Órdenes: {{ selectedAccount.orders_count }}
                        </p>
                        <button
                            type="button"
                            class="mt-2 rounded-lg bg-amber-400 px-3 py-1.5 text-xs font-semibold text-black"
                            @click="sendCurrentAccountToKitchen"
                        >
                            Crear orden y enviar a cocina
                        </button>
                        <button
                            type="button"
                            class="mt-2 ml-2 rounded-lg border border-emerald-700/60 bg-emerald-900/20 px-3 py-1.5 text-xs font-semibold text-emerald-200"
                            @click="closeCurrentAccount"
                        >
                            Cerrar cuenta
                        </button>
                    </div>
                    <div v-else class="mb-3 rounded-xl border border-dashed border-gray-700 px-3 py-3 text-sm text-gray-400">
                        Selecciona una mesa y una cuenta para agregar productos.
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <article v-for="product in filteredProducts" :key="product.id" class="rounded-xl border border-gray-800 bg-gray-950/40 p-3">
                            <p class="text-sm font-semibold text-gray-100">{{ product.name }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-400">{{ product.sku || 'SIN-SKU' }} · Stock {{ product.stock }}</p>
                            <p class="mt-2 text-sm text-amber-300">Q{{ money(product.price) }}</p>
                            <button
                                type="button"
                                class="mt-2 w-full rounded-lg bg-sky-500 px-2 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!selectedAccount"
                                @click="openAddItemModal(product)"
                            >
                                Agregar a cuenta
                            </button>
                        </article>
                    </div>
                </section>
            </div>
        </div>

        <div v-if="accountModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4" @click.self="accountModalOpen = false">
            <div class="w-full max-w-md rounded-2xl border border-gray-800 bg-gray-900 p-4">
                <h2 class="text-base font-semibold text-gray-100">Abrir cuenta en {{ selectedTable?.name }}</h2>
                <p class="mt-1 text-xs text-gray-400">Define al inicio si la mesa será cuenta única o cuentas separadas.</p>

                <div class="mt-3 space-y-2">
                    <label class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-950/60 p-2 text-sm">
                        <input v-model="newAccountForm.split_type" type="radio" value="unique" />
                        <span>Cuenta única</span>
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-950/60 p-2 text-sm">
                        <input v-model="newAccountForm.split_type" type="radio" value="split" />
                        <span>Cuentas separadas</span>
                    </label>
                </div>

                <div class="mt-3">
                    <label class="text-xs text-gray-400">Etiqueta (opcional)</label>
                    <input v-model="newAccountForm.label" type="text" maxlength="120" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" placeholder="Ej. Familia López" />
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-sm" @click="accountModalOpen = false">Cancelar</button>
                    <button type="button" class="rounded-lg bg-amber-400 px-3 py-2 text-sm font-semibold text-black" @click="createAccount">Crear cuenta</button>
                </div>
            </div>
        </div>

        <div v-if="addItemModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4" @click.self="addItemModalOpen = false">
            <div class="w-full max-w-md rounded-2xl border border-gray-800 bg-gray-900 p-4">
                <h2 class="text-base font-semibold text-gray-100">Agregar: {{ selectedProductToAdd?.name }}</h2>
                <p class="mt-1 text-xs text-gray-400">Cuenta: {{ selectedAccount?.label }}</p>

                <div class="mt-3 grid gap-3">
                    <div>
                        <label class="text-xs text-gray-400">Cantidad</label>
                        <input v-model.number="addItemForm.quantity" type="number" min="1" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Observaciones para cocina</label>
                        <textarea v-model="addItemForm.note" rows="3" maxlength="1000" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" placeholder="Sin cebolla, bien cocido, etc."></textarea>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-sm" @click="addItemModalOpen = false">Cancelar</button>
                    <button type="button" class="rounded-lg bg-sky-500 px-3 py-2 text-sm font-semibold text-white" @click="addItemToAccount">Agregar</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    tables: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '', category_id: null }) },
});

const page = usePage();

const search = ref(props.filters.q || '');
const selectedCategoryId = ref(Number(props.filters.category_id || 0));

const selectedTableId = ref(props.tables[0]?.id || null);
const selectedAccountId = ref(props.tables[0]?.accounts?.[0]?.id || null);

const accountModalOpen = ref(false);
const addItemModalOpen = ref(false);
const selectedProductToAdd = ref(null);

const newAccountForm = reactive({
    split_type: 'unique',
    label: '',
});

const addItemForm = reactive({
    quantity: 1,
    note: '',
});

const firstError = computed(() => {
    const errors = page.props.errors || {};
    return Object.values(errors)[0] || null;
});

const selectedTable = computed(() => props.tables.find((table) => Number(table.id) === Number(selectedTableId.value)) || null);
const selectedAccount = computed(() => selectedTable.value?.accounts?.find((account) => Number(account.id) === Number(selectedAccountId.value)) || null);

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.products.filter((product) => {
        if (selectedCategoryId.value > 0 && Number(product.category_id) !== Number(selectedCategoryId.value)) {
            return false;
        }

        if (!q) return true;

        return String(product.name || '').toLowerCase().includes(q)
            || String(product.sku || '').toLowerCase().includes(q);
    });
});

const selectTable = (table) => {
    selectedTableId.value = table.id;
    selectedAccountId.value = table.accounts[0]?.id || null;
};

const selectAccount = (table, account) => {
    selectedTableId.value = table.id;
    selectedAccountId.value = account.id;
};

const openAccountModal = () => {
    if (!selectedTable.value) return;
    newAccountForm.split_type = selectedTable.value.accounts.length ? 'split' : 'unique';
    newAccountForm.label = '';
    accountModalOpen.value = true;
};

const createAccount = () => {
    if (!selectedTable.value) return;

    router.post('/pos/restaurant/accounts', {
        table_id: selectedTable.value.id,
        split_type: newAccountForm.split_type,
        label: newAccountForm.label || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            accountModalOpen.value = false;
        },
    });
};

const openAddItemModal = (product) => {
    if (!selectedAccount.value) return;

    selectedProductToAdd.value = product;
    addItemForm.quantity = 1;
    addItemForm.note = '';
    addItemModalOpen.value = true;
};

const addItemToAccount = () => {
    if (!selectedAccount.value || !selectedProductToAdd.value) return;

    router.post(`/pos/restaurant/accounts/${selectedAccount.value.id}/items`, {
        product_id: selectedProductToAdd.value.id,
        quantity: Number(addItemForm.quantity || 1),
        note: addItemForm.note || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            addItemModalOpen.value = false;
        },
    });
};

const sendCurrentAccountToKitchen = () => {
    if (!selectedAccount.value) return;

    router.post(`/pos/restaurant/accounts/${selectedAccount.value.id}/send-kitchen`, {}, {
        preserveScroll: true,
    });
};

const closeCurrentAccount = () => {
    if (!selectedAccount.value) return;

    const confirmed = window.confirm(`¿Cerrar la cuenta ${selectedAccount.value.label}?`);
    if (!confirmed) return;

    router.post(`/pos/restaurant/accounts/${selectedAccount.value.id}/close`, {}, {
        preserveScroll: true,
    });
};

const money = (value) => Number(value || 0).toFixed(2);
</script>
