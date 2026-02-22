<template>
    <AppLayout title="Bancos y transferencias">
        <div class="space-y-4">
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="grid gap-3 md:grid-cols-5">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Buscar movimiento</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Descripción o referencia"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100"
                        />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Cuenta</label>
                        <select v-model="accountFilter" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100">
                            <option :value="null">Todas</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.bank_name }} · {{ account.account_name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Desde</label>
                        <input v-model="dateFrom" type="date" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Hasta</label>
                        <input v-model="dateTo" type="date" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                </div>
            </section>

            <section class="grid gap-3 md:grid-cols-3">
                <article class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-gray-400">Saldo total bancos</p>
                    <p class="mt-1 text-2xl font-bold text-gray-100">Q{{ money(summary.total_balance) }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-800/50 bg-emerald-950/20 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-emerald-300">Entradas</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-200">Q{{ money(summary.deposits) }}</p>
                </article>
                <article class="rounded-2xl border border-red-800/50 bg-red-950/20 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-red-300">Salidas</p>
                    <p class="mt-1 text-2xl font-bold text-red-200">Q{{ money(summary.withdrawals) }}</p>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(20rem,24rem)_1fr]">
                <article class="space-y-4 rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <div>
                        <h2 class="text-base font-semibold text-gray-100">{{ accountForm.id ? 'Editar cuenta bancaria' : 'Crear cuenta bancaria' }}</h2>
                        <form class="mt-3 space-y-2" @submit.prevent="submitAccount">
                            <input v-model="accountForm.bank_name" type="text" placeholder="Banco" required class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                            <input v-model="accountForm.account_name" type="text" placeholder="Nombre de cuenta" required class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                            <input v-model="accountForm.account_number" type="text" placeholder="Número de cuenta" class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                            <div class="grid grid-cols-2 gap-2">
                                <input v-model="accountForm.currency" type="text" placeholder="Moneda (GTQ)" required class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                                <input v-model.number="accountForm.current_balance" type="number" min="0" step="0.01" placeholder="Saldo inicial" :disabled="!!accountForm.id" class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 disabled:opacity-60" />
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs text-gray-300">
                                <input v-model="accountForm.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400" />
                                Activa
                            </label>
                            <div class="flex justify-end gap-2">
                                <button v-if="accountForm.id" type="button" class="rounded-xl border border-gray-700 px-3 py-2 text-xs font-semibold text-gray-100 hover:bg-gray-800" @click="resetAccountForm">
                                    Cancelar
                                </button>
                                <button type="submit" class="rounded-xl bg-amber-400 px-3 py-2 text-xs font-semibold text-black hover:bg-amber-300" :disabled="accountForm.processing">
                                    {{ accountForm.id ? 'Guardar' : 'Crear cuenta' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-200">Cuentas</h3>
                        <div class="mt-2 space-y-2">
                            <article v-for="account in accounts" :key="account.id" class="rounded-xl border border-gray-800 bg-gray-950/60 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-100">{{ account.bank_name }}</p>
                                        <p class="text-xs text-gray-400">{{ account.account_name }} · {{ account.account_number || 'Sin número' }}</p>
                                        <p class="text-xs text-gray-500">Saldo: Q{{ money(account.current_balance) }} · {{ account.currency }}</p>
                                    </div>
                                    <button type="button" class="rounded-md border border-gray-700 px-2 py-1 text-[11px] text-gray-200 hover:bg-gray-800" @click="editAccount(account)">
                                        Editar
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </article>

                <article class="space-y-4 rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <div>
                        <h2 class="text-base font-semibold text-gray-100">Registrar movimiento</h2>
                        <form class="mt-3 grid gap-2 md:grid-cols-6" @submit.prevent="submitMovement">
                            <input v-model="movementForm.movement_date" type="date" required class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                            <select v-model="movementForm.type" required class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100">
                                <option value="deposit">Ingreso</option>
                                <option value="withdrawal">Egreso</option>
                                <option value="transfer">Transferencia</option>
                            </select>
                            <select v-model="movementForm.bank_account_id" required class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100">
                                <option :value="null" disabled>Cuenta origen</option>
                                <option v-for="account in activeAccounts" :key="`origin-${account.id}`" :value="account.id">
                                    {{ account.bank_name }} · {{ account.account_name }}
                                </option>
                            </select>
                            <select
                                v-if="movementForm.type === 'transfer'"
                                v-model="movementForm.to_bank_account_id"
                                required
                                class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100"
                            >
                                <option :value="null" disabled>Cuenta destino</option>
                                <option v-for="account in transferTargetAccounts" :key="`target-${account.id}`" :value="account.id">
                                    {{ account.bank_name }} · {{ account.account_name }}
                                </option>
                            </select>
                            <input v-model.number="movementForm.amount" type="number" min="0.01" step="0.01" required placeholder="Monto" class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                            <input v-model="movementForm.description" type="text" required placeholder="Descripción" class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 md:col-span-2" />
                            <input v-model="movementForm.reference" type="text" placeholder="Referencia" class="rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                            <button type="submit" class="rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-semibold text-black hover:bg-amber-300 md:col-span-2" :disabled="movementForm.processing">
                                Guardar movimiento
                            </button>
                        </form>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-800">
                        <div class="hidden grid-cols-12 gap-2 bg-gray-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400 md:grid">
                            <div class="col-span-2">Fecha</div>
                            <div class="col-span-2">Tipo</div>
                            <div class="col-span-3">Cuenta</div>
                            <div class="col-span-2 text-right">Monto</div>
                            <div class="col-span-3 text-right">Acciones</div>
                        </div>
                        <div v-if="movements.data.length" class="divide-y divide-gray-800">
                            <article v-for="movement in movements.data" :key="movement.id" class="grid grid-cols-1 gap-2 px-3 py-3 md:grid-cols-12 md:items-center">
                                <p class="text-xs text-gray-300 md:col-span-2">{{ movement.movement_date }}</p>
                                <p class="text-xs md:col-span-2" :class="movementTypeClass(movement.type)">{{ movementTypeLabel(movement.type) }}</p>
                                <div class="md:col-span-3">
                                    <p class="text-xs text-gray-200">{{ movement.account_label }}</p>
                                    <p v-if="movement.related_account_label" class="text-[11px] text-gray-500">Contra: {{ movement.related_account_label }}</p>
                                    <p class="mt-1 text-[11px] text-gray-400">{{ movement.description }}</p>
                                    <p v-if="movement.reference" class="text-[11px] text-gray-500">Ref: {{ movement.reference }}</p>
                                </div>
                                <p class="text-sm font-semibold md:col-span-2 md:text-right" :class="movementAmountClass(movement.type)">
                                    {{ movement.type.includes('out') || movement.type === 'withdrawal' ? '-' : '+' }}Q{{ money(movement.amount) }}
                                </p>
                                <div class="md:col-span-3 md:text-right">
                                    <button type="button" class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10" @click="removeMovement(movement)">
                                        Eliminar
                                    </button>
                                </div>
                            </article>
                        </div>
                        <div v-else class="px-3 py-6 text-center text-sm text-gray-400">
                            No hay movimientos bancarios.
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <div>Mostrando {{ movements.data.length }} de {{ movements.total }} resultados</div>
                        <div class="flex gap-2">
                            <button class="rounded-lg border border-gray-700 px-3 py-1" :disabled="!movements.prev_page_url" @click="goTo(movements.prev_page_url)">Anterior</button>
                            <button class="rounded-lg border border-gray-700 px-3 py-1" :disabled="!movements.next_page_url" @click="goTo(movements.next_page_url)">Siguiente</button>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    movements: { type: Object, required: true },
    summary: { type: Object, default: () => ({ total_balance: 0, deposits: 0, withdrawals: 0 }) },
    filters: { type: Object, default: () => ({ q: '', account_id: null, date_from: '', date_to: '' }) },
});

const search = ref(props.filters.q || '');
const accountFilter = ref(props.filters.account_id || null);
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const accountForm = useForm({
    id: null,
    bank_name: '',
    account_name: '',
    account_number: '',
    currency: 'GTQ',
    current_balance: 0,
    is_active: true,
});

const movementForm = useForm({
    movement_date: new Date().toISOString().slice(0, 10),
    type: 'deposit',
    bank_account_id: props.accounts[0]?.id ?? null,
    to_bank_account_id: null,
    amount: '',
    description: '',
    reference: '',
});

const activeAccounts = computed(() => props.accounts.filter((account) => account.is_active));
const transferTargetAccounts = computed(() =>
    activeAccounts.value.filter((account) => Number(account.id) !== Number(movementForm.bank_account_id)),
);

watch(
    () => movementForm.type,
    (value) => {
        if (value !== 'transfer') {
            movementForm.to_bank_account_id = null;
        }
    },
);

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const applyFilters = () => {
    router.get('/admin/banks', {
        q: search.value || undefined,
        account_id: accountFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedFilters = debounce(applyFilters);

watch(search, () => debouncedFilters());
watch(accountFilter, () => applyFilters());
watch(dateFrom, () => applyFilters());
watch(dateTo, () => applyFilters());

const resetAccountForm = () => {
    accountForm.reset();
    accountForm.clearErrors();
    accountForm.id = null;
    accountForm.currency = 'GTQ';
    accountForm.current_balance = 0;
    accountForm.is_active = true;
};

const editAccount = (account) => {
    accountForm.id = account.id;
    accountForm.bank_name = account.bank_name;
    accountForm.account_name = account.account_name;
    accountForm.account_number = account.account_number || '';
    accountForm.currency = account.currency || 'GTQ';
    accountForm.current_balance = account.current_balance;
    accountForm.is_active = !!account.is_active;
};

const submitAccount = () => {
    const payload = {
        bank_name: accountForm.bank_name,
        account_name: accountForm.account_name,
        account_number: accountForm.account_number || null,
        currency: accountForm.currency || 'GTQ',
        current_balance: accountForm.current_balance,
        is_active: !!accountForm.is_active,
    };

    if (accountForm.id) {
        accountForm.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/banks/accounts/${accountForm.id}`, {
                preserveScroll: true,
                onSuccess: () => resetAccountForm(),
            });
        return;
    }

    accountForm.transform(() => payload).post('/admin/banks/accounts', {
        preserveScroll: true,
        onSuccess: () => resetAccountForm(),
    });
};

const submitMovement = () => {
    const payload = {
        movement_date: movementForm.movement_date,
        type: movementForm.type,
        bank_account_id: movementForm.bank_account_id,
        to_bank_account_id: movementForm.type === 'transfer' ? movementForm.to_bank_account_id : null,
        amount: movementForm.amount,
        description: movementForm.description,
        reference: movementForm.reference || null,
    };

    movementForm.transform(() => payload).post('/admin/banks/movements', {
        preserveScroll: true,
        onSuccess: () => {
            movementForm.amount = '';
            movementForm.description = '';
            movementForm.reference = '';
            movementForm.to_bank_account_id = null;
        },
    });
};

const removeMovement = (movement) => {
    if (!confirm('¿Eliminar y revertir este movimiento bancario?')) return;
    router.delete(`/admin/banks/movements/${movement.id}`, {
        preserveScroll: true,
    });
};

const movementTypeLabel = (type) => {
    if (type === 'deposit') return 'Ingreso';
    if (type === 'withdrawal') return 'Egreso';
    if (type === 'transfer_out') return 'Transferencia salida';
    if (type === 'transfer_in') return 'Transferencia entrada';
    return type;
};

const movementTypeClass = (type) => {
    if (type === 'deposit' || type === 'transfer_in') return 'text-emerald-300';
    if (type === 'withdrawal' || type === 'transfer_out') return 'text-red-300';
    return 'text-gray-300';
};

const movementAmountClass = (type) => {
    if (type === 'deposit' || type === 'transfer_in') return 'text-emerald-300';
    if (type === 'withdrawal' || type === 'transfer_out') return 'text-red-300';
    return 'text-gray-100';
};

const money = (value) => Number(value || 0).toFixed(2);

const goTo = (url) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>
