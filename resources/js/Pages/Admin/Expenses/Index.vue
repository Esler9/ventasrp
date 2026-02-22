<template>
    <AppLayout title="Gastos">
        <div class="space-y-4">
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="grid gap-3 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Buscar gasto</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Descripción, categoría, referencia..."
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Método</label>
                        <select v-model="methodFilter" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100">
                            <option value="">Todos</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
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
                    <div class="flex items-end">
                        <button
                            type="button"
                            class="w-full rounded-xl border border-gray-600 px-4 py-3 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                            @click="resetForm"
                        >
                            Nuevo gasto
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 md:grid-cols-4">
                <article class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-gray-400">Total período</p>
                    <p class="mt-1 text-2xl font-bold text-gray-100">Q{{ money(summary.total) }}</p>
                </article>
                <article class="rounded-2xl border border-red-800/50 bg-red-950/20 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-red-300">Efectivo</p>
                    <p class="mt-1 text-2xl font-bold text-red-200">Q{{ money(summary.cash) }}</p>
                </article>
                <article class="rounded-2xl border border-sky-800/50 bg-sky-950/20 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-sky-300">Tarjeta</p>
                    <p class="mt-1 text-2xl font-bold text-sky-200">Q{{ money(summary.card) }}</p>
                </article>
                <article class="rounded-2xl border border-indigo-800/50 bg-indigo-950/20 p-4 ring-1 ring-black/30">
                    <p class="text-xs text-indigo-300">Transferencia</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-200">Q{{ money(summary.transfer) }}</p>
                </article>
            </section>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div v-if="openCashSession" class="mb-3 rounded-xl border border-emerald-700/50 bg-emerald-900/20 px-3 py-2 text-xs text-emerald-200">
                    Caja abierta: {{ openCashSession.register_name }} · {{ openCashSession.branch_name }}.
                    Los gastos en efectivo se descuentan automáticamente de caja.
                </div>
                <h2 class="text-base font-semibold text-gray-100">{{ form.id ? 'Editar gasto' : 'Registrar gasto' }}</h2>
                <form class="mt-3 grid gap-3 md:grid-cols-6" @submit.prevent="submit">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Descripción</label>
                        <input
                            v-model="form.description"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100"
                        />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Fecha</label>
                        <input v-model="form.expense_date" type="date" required class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Método</label>
                        <select v-model="form.method" required class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100">
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Monto (Q)</label>
                        <input v-model.number="form.amount" type="number" min="0.01" step="0.01" required class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Categoría</label>
                        <input v-model="form.category" type="text" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Referencia</label>
                        <input v-model="form.reference" type="text" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div class="md:col-span-4">
                        <label class="text-xs text-gray-400">Notas</label>
                        <input v-model="form.notes" type="text" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div class="md:col-span-6 flex gap-2 md:justify-end">
                        <button v-if="form.id" type="button" class="rounded-xl border border-gray-600 px-4 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800" @click="resetForm">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300" :disabled="form.processing">
                            {{ form.id ? 'Guardar cambios' : 'Guardar gasto' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/80 ring-1 ring-black/30">
                <div v-if="expenses.data.length" class="divide-y divide-gray-800">
                    <article v-for="expense in expenses.data" :key="expense.id" class="space-y-3 px-4 py-4 md:flex md:items-center md:justify-between md:space-y-0">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-100">{{ expense.description }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="methodBadgeClass(expense.method)">
                                    {{ methodLabel(expense.method) }}
                                </span>
                                <span v-if="expense.has_cash_link" class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-300">
                                    En caja
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">Fecha: {{ expense.expense_date }} · Categoría: {{ expense.category || 'General' }}</p>
                            <p class="text-xs text-gray-500">Ref: {{ expense.reference || '—' }} · Usuario: {{ expense.created_by || '—' }}</p>
                            <p v-if="expense.notes" class="text-xs text-gray-500">{{ expense.notes }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-red-300">-Q{{ money(expense.amount) }}</p>
                            <button type="button" class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800" @click="editExpense(expense)">
                                Editar
                            </button>
                            <button type="button" class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10" @click="removeExpense(expense)">
                                Eliminar
                            </button>
                        </div>
                    </article>
                </div>
                <div v-else class="px-4 py-6 text-center text-sm text-gray-400">
                    No hay gastos registrados en el período.
                </div>
            </section>

            <div class="flex items-center justify-between text-xs text-gray-500">
                <div>Mostrando {{ expenses.data.length }} de {{ expenses.total }} resultados</div>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-gray-700 px-3 py-1" :disabled="!expenses.prev_page_url" @click="goTo(expenses.prev_page_url)">
                        Anterior
                    </button>
                    <button class="rounded-lg border border-gray-700 px-3 py-1" :disabled="!expenses.next_page_url" @click="goTo(expenses.next_page_url)">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    expenses: { type: Object, required: true },
    filters: { type: Object, default: () => ({ q: '', method: '', date_from: '', date_to: '' }) },
    summary: { type: Object, default: () => ({ total: 0, cash: 0, card: 0, transfer: 0 }) },
    open_cash_session: { type: Object, default: null },
});

const openCashSession = props.open_cash_session;
const search = ref(props.filters.q || '');
const methodFilter = ref(props.filters.method || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const form = useForm({
    id: null,
    expense_date: new Date().toISOString().slice(0, 10),
    description: '',
    category: '',
    method: 'cash',
    amount: '',
    reference: '',
    notes: '',
});

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const runFilters = () => {
    router.get('/admin/expenses', {
        q: search.value || undefined,
        method: methodFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedFilters = debounce(runFilters);

watch(search, () => debouncedFilters());
watch(methodFilter, () => runFilters());
watch(dateFrom, () => runFilters());
watch(dateTo, () => runFilters());

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.expense_date = new Date().toISOString().slice(0, 10);
    form.method = 'cash';
};

const editExpense = (expense) => {
    form.id = expense.id;
    form.expense_date = expense.expense_date;
    form.description = expense.description;
    form.category = expense.category || '';
    form.method = expense.method;
    form.amount = expense.amount;
    form.reference = expense.reference || '';
    form.notes = expense.notes || '';
};

const submit = () => {
    const payload = {
        expense_date: form.expense_date,
        description: form.description,
        category: form.category || null,
        method: form.method,
        amount: form.amount,
        reference: form.reference || null,
        notes: form.notes || null,
    };

    if (form.id) {
        form.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/expenses/${form.id}`, {
                preserveScroll: true,
                onSuccess: () => resetForm(),
            });
        return;
    }

    form.transform(() => payload).post('/admin/expenses', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const removeExpense = (expense) => {
    if (!confirm(`¿Eliminar el gasto "${expense.description}"?`)) return;

    router.delete(`/admin/expenses/${expense.id}`, {
        preserveScroll: true,
    });
};

const methodLabel = (method) => {
    if (method === 'cash') return 'Efectivo';
    if (method === 'card') return 'Tarjeta';
    if (method === 'transfer') return 'Transferencia';
    return method;
};

const methodBadgeClass = (method) => {
    if (method === 'cash') return 'bg-red-500/20 text-red-300';
    if (method === 'card') return 'bg-sky-500/20 text-sky-300';
    if (method === 'transfer') return 'bg-indigo-500/20 text-indigo-300';
    return 'bg-gray-700 text-gray-300';
};

const money = (value) => Number(value || 0).toFixed(2);

const goTo = (url) => {
    if (!url) return;
    router.visit(url, { preserveState: true, preserveScroll: true });
};
</script>
