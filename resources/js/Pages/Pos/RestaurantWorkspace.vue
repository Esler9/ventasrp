<template>
    <AppLayout title="Gestión de Mesa">
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">New Order</p>
                        <h1 class="text-xl font-semibold text-gray-100">
                            {{ selectedTable ? selectedTable.name : 'Mesa' }}
                        </h1>
                        <p class="text-xs text-gray-400">
                            {{ selectedAccount ? `Cuenta: ${selectedAccount.label}` : 'Selecciona o crea una cuenta' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="/pos" class="rounded-xl border border-gray-700 bg-gray-950/60 px-3 py-2 text-sm text-gray-200">Volver a mesas</a>
                        <a href="/pos/kitchen" class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm font-semibold text-amber-300">Cocina</a>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        v-for="account in selectedTable?.accounts || []"
                        :key="account.id"
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs"
                        :class="selectedAccountId === account.id ? 'border-sky-400 bg-sky-500/20 text-sky-100' : 'border-gray-700 bg-gray-950/60 text-gray-300'"
                        @click="selectAccount(selectedTable, account)"
                    >
                        {{ account.label }} · {{ account.items_count }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-gray-700 bg-gray-950/60 px-3 py-1.5 text-xs text-gray-300"
                        :disabled="!selectedTable"
                        @click="openAccountModal"
                    >
                        + Cuenta
                    </button>
                </div>
            </div>

            <div v-if="!openCashSession" class="rounded-xl border border-rose-700/60 bg-rose-900/20 p-3 text-sm text-rose-200">
                Debes abrir caja para cobrar y cerrar cuentas.
                <a href="/admin/cash" class="font-semibold underline">Ir a Caja</a>
            </div>

            <div v-if="firstError" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200">
                {{ firstError }}
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <div class="mb-3 flex flex-wrap items-end gap-2">
                        <div class="min-w-[220px] flex-1">
                            <label class="text-xs text-gray-400">Buscar</label>
                            <input v-model="search" type="text" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100" placeholder="Buscar menú o receta..." />
                        </div>
                        <div class="w-full sm:w-56">
                            <label class="text-xs text-gray-400">Categoría</label>
                            <select v-model.number="selectedCategoryId" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100">
                                <option :value="0">Todas</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article v-for="product in filteredProducts" :key="product.id" class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950/50">
                            <div class="h-28 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.35),_transparent_65%),linear-gradient(135deg,_#111827,_#0f172a)]"></div>
                            <div class="p-3">
                                <p class="line-clamp-2 min-h-10 text-sm font-semibold text-gray-100">{{ product.name }}</p>
                                <p class="mt-1 text-[11px] text-gray-400">{{ product.sku || 'SIN-SKU' }} · Stock {{ product.stock }}</p>
                                <div class="mt-3 flex items-center justify-between">
                                    <p class="text-xl font-semibold text-sky-300">Q{{ money(product.price) }}</p>
                                    <button
                                        type="button"
                                        class="h-9 w-9 rounded-lg bg-sky-500 text-lg font-bold text-white disabled:opacity-40"
                                        :disabled="!selectedAccount"
                                        @click="openAddItemModal(product)"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <aside class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-100">Order Summary</h2>
                        <span class="rounded-full bg-sky-500/20 px-2 py-1 text-[11px] font-semibold text-sky-200">
                            {{ selectedTable?.code || selectedTable?.name || 'Mesa' }}
                        </span>
                    </div>

                    <div v-if="selectedAccount" class="space-y-2">
                        <article v-for="item in groupedAccountItems" :key="item.key" class="rounded-xl border border-gray-800 bg-gray-950/50 p-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-100">{{ item.product_name }}</p>
                                    <p v-if="item.note" class="text-[11px] text-gray-400">{{ item.note }}</p>
                                    <div class="mt-1 flex items-center gap-2 text-[11px]">
                                        <span class="rounded-full px-2 py-0.5" :class="statusPill(item.kitchen_status)">{{ statusLabel(item.kitchen_status) }}</span>
                                        <span class="text-gray-400">x{{ item.quantity }}</span>
                                    </div>
                                </div>
                                <p class="text-sm font-semibold text-gray-100">Q{{ money(item.line_total) }}</p>
                            </div>
                        </article>

                        <div v-if="latestAccountNote" class="rounded-xl border border-dashed border-gray-700 bg-gray-950/30 p-3 text-xs text-gray-300">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Custom Request</p>
                            <p>{{ latestAccountNote }}</p>
                        </div>
                    </div>

                    <p v-else class="rounded-xl border border-dashed border-gray-700 px-3 py-4 text-sm text-gray-400">
                        Selecciona una cuenta para comenzar a agregar productos.
                    </p>

                    <div class="mt-4 space-y-1 border-t border-gray-800 pt-3 text-sm">
                        <div class="flex items-center justify-between text-gray-300">
                            <span>Subtotal</span>
                            <span>Q{{ money(selectedAccountSubtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-300">
                            <span>Servido</span>
                            <span>Q{{ money(selectedAccountServedTotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-base font-semibold text-gray-100">
                            <span>Total</span>
                            <span>Q{{ money(selectedAccountTotal) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2">
                        <button
                            type="button"
                            class="rounded-xl bg-sky-500 px-3 py-2 text-sm font-semibold text-white disabled:opacity-40"
                            :disabled="!selectedAccount"
                            @click="sendCurrentAccountToKitchen"
                        >
                            Enviar orden a cocina
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-emerald-500 px-3 py-2 text-sm font-semibold text-white disabled:opacity-40"
                            :disabled="!selectedAccount?.can_settle || !openCashSession"
                            @click="openSettleModal"
                        >
                            Cobrar y cerrar
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-gray-700 px-3 py-2 text-sm text-gray-200 disabled:opacity-40"
                            :disabled="!selectedAccount"
                            @click="closeCurrentAccount"
                        >
                            Cerrar cuenta manual
                        </button>
                    </div>
                </aside>
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

        <div v-if="settleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4" @click.self="settleModalOpen = false">
            <div class="w-full max-w-2xl rounded-2xl border border-gray-800 bg-gray-900 p-4">
                <h2 class="text-base font-semibold text-gray-100">Cobrar y cerrar cuenta</h2>
                <p class="mt-1 text-xs text-gray-400">
                    Cuenta: {{ selectedAccount?.label }} · Total: Q{{ money(selectedAccountTotal) }}
                </p>

                <div class="mt-3 space-y-2">
                    <div
                        v-for="(payment, index) in settleForm.payments"
                        :key="index"
                        class="grid gap-2 rounded-lg border border-gray-800 bg-gray-950/50 p-3 md:grid-cols-12"
                    >
                        <div class="md:col-span-3">
                            <label class="text-[11px] text-gray-400">Método</label>
                            <select v-model="payment.method" class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="transfer">Transferencia</option>
                            </select>
                        </div>

                        <div v-if="payment.method === 'card'" class="md:col-span-4">
                            <label class="text-[11px] text-gray-400">POS</label>
                            <select v-model="payment.card_pos_terminal_id" class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm">
                                <option :value="null">Selecciona POS</option>
                                <option v-for="terminal in cardPosTerminals" :key="terminal.id" :value="terminal.id">{{ terminal.name }}</option>
                            </select>
                        </div>
                        <div v-if="payment.method === 'transfer'" class="md:col-span-4">
                            <label class="text-[11px] text-gray-400">Cuenta bancaria</label>
                            <select v-model="payment.bank_account_id" class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm">
                                <option :value="null">Selecciona cuenta</option>
                                <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">{{ bank.label }}</option>
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="text-[11px] text-gray-400">Monto</label>
                            <input v-model.number="payment.amount" type="number" min="0.01" step="0.01" class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm" />
                            <button
                                type="button"
                                class="mt-1 text-[11px] text-sky-300 underline"
                                @click="fillRemaining(index)"
                            >
                                Completar restante
                            </button>
                        </div>

                        <div v-if="payment.method !== 'cash'" class="md:col-span-2">
                            <label class="text-[11px] text-gray-400">Referencia</label>
                            <input v-model.trim="payment.reference" type="text" maxlength="100" class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-900 px-2 py-2 text-sm" />
                        </div>

                        <div class="flex items-end md:col-span-2">
                            <button
                                type="button"
                                class="w-full rounded-lg border border-red-700/60 px-2 py-2 text-xs text-red-300 disabled:opacity-50"
                                :disabled="settleForm.payments.length <= 1"
                                @click="removePayment(index)"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between">
                    <button type="button" class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs text-gray-200" @click="addPayment">+ Agregar pago</button>
                    <p class="text-xs text-gray-300">
                        Pagos: Q{{ money(paymentsTotal) }} / Total: Q{{ money(selectedAccountTotal) }} / Diferencia: Q{{ money(paymentDifference) }}
                    </p>
                </div>
                <p v-if="settlementError" class="mt-2 text-xs text-rose-300">{{ settlementError }}</p>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-sm" @click="settleModalOpen = false">Cancelar</button>
                    <button
                        type="button"
                        class="rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canSubmitSettlement"
                        @click="submitSettlement"
                    >
                        Cobrar y cerrar
                    </button>
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
    bank_accounts: { type: Array, default: () => [] },
    card_pos_terminals: { type: Array, default: () => [] },
    open_cash_session: { type: Object, default: null },
    active_table_id: { type: Number, default: null },
});

const page = usePage();

const search = ref(props.filters.q || '');
const selectedCategoryId = ref(Number(props.filters.category_id || 0));

const initialTable = computed(() => {
    if (props.active_table_id) {
        const found = props.tables.find((table) => Number(table.id) === Number(props.active_table_id));
        if (found) return found;
    }

    return props.tables[0] || null;
});

const selectedTableId = ref(initialTable.value?.id || null);
const selectedAccountId = ref(initialTable.value?.accounts?.[0]?.id || null);

const accountModalOpen = ref(false);
const addItemModalOpen = ref(false);
const selectedProductToAdd = ref(null);
const settleModalOpen = ref(false);

const newAccountForm = reactive({
    split_type: 'unique',
    label: '',
});

const addItemForm = reactive({
    quantity: 1,
    note: '',
});

const settleForm = reactive({
    payments: [{
        method: 'cash',
        bank_account_id: null,
        card_pos_terminal_id: null,
        reference: '',
        amount: 0,
    }],
});

const firstError = computed(() => {
    const errors = page.props.errors || {};
    return Object.values(errors)[0] || null;
});

const selectedTable = computed(() => props.tables.find((table) => Number(table.id) === Number(selectedTableId.value)) || null);
const selectedAccount = computed(() => selectedTable.value?.accounts?.find((account) => Number(account.id) === Number(selectedAccountId.value)) || null);
const selectedAccountTotal = computed(() => Number(selectedAccount.value?.total ?? 0));
const selectedAccountServedTotal = computed(() => Number(selectedAccount.value?.served_total ?? 0));
const selectedAccountSubtotal = computed(() => Number((selectedAccountTotal.value - selectedAccountServedTotal.value).toFixed(2)));
const openCashSession = computed(() => props.open_cash_session || null);
const bankAccounts = computed(() => props.bank_accounts || []);
const cardPosTerminals = computed(() => props.card_pos_terminals || []);
const paymentsTotal = computed(() => settleForm.payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0));
const paymentDifference = computed(() => Number((selectedAccountTotal.value - paymentsTotal.value).toFixed(2)));

const groupedAccountItems = computed(() => {
    const items = selectedAccount.value?.items || [];
    const grouped = new Map();

    items.forEach((item) => {
        const key = `${item.product_name}::${item.note || ''}::${item.kitchen_status}`;
        const current = grouped.get(key) || {
            key,
            product_name: item.product_name,
            note: item.note,
            kitchen_status: item.kitchen_status,
            quantity: 0,
            line_total: 0,
        };

        current.quantity += Number(item.quantity || 0);
        current.line_total += Number(item.line_total || 0);
        grouped.set(key, current);
    });

    return Array.from(grouped.values());
});

const latestAccountNote = computed(() => {
    const withNotes = (selectedAccount.value?.items || []).filter((item) => String(item.note || '').trim() !== '');
    if (!withNotes.length) return '';
    return withNotes[withNotes.length - 1].note;
});

const settlementError = computed(() => {
    if (!selectedAccount.value) return 'Selecciona una cuenta.';
    if (!openCashSession.value) return 'Debes abrir caja antes de cobrar.';
    if (!settleForm.payments.length) return 'Agrega al menos un pago.';
    if (Math.abs(paymentDifference.value) > 0.01) return 'La suma de pagos debe ser igual al total.';

    const missingTransferAccount = settleForm.payments.some((payment) => payment.method === 'transfer' && !payment.bank_account_id);
    if (missingTransferAccount) return 'Selecciona la cuenta bancaria para cada transferencia.';

    const missingCardPos = settleForm.payments.some((payment) => payment.method === 'card' && !payment.card_pos_terminal_id);
    if (missingCardPos) return 'Selecciona POS para cada pago con tarjeta.';

    const missingCardReference = settleForm.payments.some((payment) => payment.method === 'card' && !String(payment.reference || '').trim());
    if (missingCardReference) return 'Ingresa referencia para cada pago con tarjeta.';

    const invalidAmount = settleForm.payments.some((payment) => Number(payment.amount || 0) <= 0);
    if (invalidAmount) return 'Todos los pagos deben ser mayores a cero.';

    return '';
});

const canSubmitSettlement = computed(() => !settlementError.value);

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

const openSettleModal = () => {
    if (!selectedAccount.value || !selectedAccount.value.can_settle || !openCashSession.value) return;
    settleForm.payments = [{
        method: 'cash',
        bank_account_id: null,
        card_pos_terminal_id: null,
        reference: '',
        amount: Number(selectedAccount.value.total ?? 0),
    }];
    settleModalOpen.value = true;
};

const addPayment = () => {
    settleForm.payments.push({
        method: 'cash',
        bank_account_id: null,
        card_pos_terminal_id: null,
        reference: '',
        amount: 0,
    });
};

const removePayment = (index) => {
    if (settleForm.payments.length <= 1) return;
    settleForm.payments.splice(index, 1);
};

const fillRemaining = (index) => {
    const current = settleForm.payments[index];
    if (!current) return;

    const othersTotal = settleForm.payments
        .filter((_, i) => i !== index)
        .reduce((sum, payment) => sum + Number(payment.amount || 0), 0);

    const remaining = Number((selectedAccountTotal.value - othersTotal).toFixed(2));
    current.amount = remaining > 0 ? remaining : 0;
};

const submitSettlement = () => {
    if (!selectedAccount.value || !canSubmitSettlement.value) return;

    router.post(`/pos/restaurant/accounts/${selectedAccount.value.id}/settle`, {
        payments: settleForm.payments.map((payment) => ({
            method: payment.method,
            bank_account_id: payment.method === 'transfer' ? payment.bank_account_id : null,
            card_pos_terminal_id: payment.method === 'card' ? payment.card_pos_terminal_id : null,
            reference: payment.method === 'cash' ? null : (payment.reference || null),
            amount: Number(payment.amount || 0),
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            settleModalOpen.value = false;
        },
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

const statusLabel = (status) => ({
    draft: 'Borrador',
    pending: 'Pendiente',
    preparing: 'Preparando',
    ready: 'Listo',
    served: 'Servido',
}[status] || status);

const statusPill = (status) => ({
    draft: 'bg-gray-700/30 text-gray-300',
    pending: 'bg-amber-500/20 text-amber-200',
    preparing: 'bg-sky-500/20 text-sky-200',
    ready: 'bg-emerald-500/20 text-emerald-200',
    served: 'bg-violet-500/20 text-violet-200',
}[status] || 'bg-gray-700/30 text-gray-300');

const money = (value) => Number(value || 0).toFixed(2);
</script>
