<template>
    <AppLayout title="Ventas">

        <!-- Summary cards -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-4 hover-lift fade-in-soft">
                <p class="text-xs text-gray-400">Ingresos</p>
                <p class="text-2xl font-bold text-amber-300">{{ currency(summary.revenue) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ summary.sales_count }} ventas</p>
            </div>
            <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-4 hover-lift fade-in-soft">
                <p class="text-xs text-gray-400">Utilidad bruta</p>
                <p class="text-2xl font-bold" :class="summary.profit >= 0 ? 'text-emerald-400' : 'text-red-400'">
                    {{ currency(summary.profit) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ summary.revenue > 0 ? ((summary.profit / summary.revenue) * 100).toFixed(1) : 0 }}% margen
                </p>
            </div>
            <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-4 hover-lift fade-in-soft">
                <p class="text-xs text-gray-400">Descuentos</p>
                <p class="text-2xl font-bold text-orange-300">{{ currency(summary.discounts) }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ summary.revenue > 0 ? ((summary.discounts / summary.revenue) * 100).toFixed(1) : 0 }}% del total
                </p>
            </div>
            <div class="rounded-xl border border-gray-800 bg-gray-950/60 p-4 hover-lift fade-in-soft">
                <p class="text-xs text-gray-400">Ticket promedio</p>
                <p class="text-2xl font-bold text-gray-100">
                    {{ summary.sales_count > 0 ? currency(summary.revenue / summary.sales_count) : currency(0) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">por venta</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mt-4 rounded-2xl bg-gray-900/80 p-4 ring-1 ring-black/30 space-y-3">

            <!-- Quick date shortcuts -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="shortcut in dateShortcuts"
                    :key="shortcut.label"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="isActiveShortcut(shortcut)
                        ? 'bg-amber-400 text-black'
                        : 'border border-gray-700 text-gray-300 hover:border-amber-400 hover:text-amber-300'"
                    @click="applyShortcut(shortcut)"
                >
                    {{ shortcut.label }}
                </button>
            </div>

            <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="applyFilters">
                <div class="lg:col-span-2 space-y-1">
                    <label class="text-xs text-gray-400">Buscar</label>
                    <input
                        v-model="form.q"
                        type="text"
                        placeholder="Código, cliente, producto o SKU"
                        class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-gray-400">Desde</label>
                    <input
                        v-model="form.date_from"
                        type="date"
                        class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-gray-400">Hasta</label>
                    <input
                        v-model="form.date_to"
                        type="date"
                        class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-gray-400">Vendedor</label>
                    <select
                        v-model="form.seller_id"
                        class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                    >
                        <option value="">Todos</option>
                        <option v-for="s in sellers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 lg:col-span-5">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300"
                    >
                        Aplicar
                    </button>
                    <button type="button" class="text-sm text-gray-400 underline hover:text-gray-200" @click="resetFilters">
                        Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Sales table -->
        <div class="mt-4 rounded-2xl bg-gray-900/80 ring-1 ring-black/30 overflow-hidden">

            <!-- Header -->
            <div class="hidden lg:grid grid-cols-12 gap-2 bg-gray-900 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                <div class="col-span-2">Código</div>
                <div class="col-span-2">Fecha</div>
                <div class="col-span-2">Cliente</div>
                <div class="col-span-1 text-center">Ítems</div>
                <div class="col-span-1 text-right">Descuento</div>
                <div class="col-span-2 text-right">Total</div>
                <div class="col-span-1">Pago</div>
                <div class="col-span-1 text-right">Acción</div>
            </div>

            <div v-if="sales.data.length" class="divide-y divide-gray-800">
                <div v-for="sale in sales.data" :key="sale.id">

                    <!-- Sale row -->
                    <div
                        class="grid grid-cols-2 gap-2 px-4 py-3 lg:grid-cols-12 lg:items-center cursor-pointer hover:bg-gray-800/40 transition-colors"
                        @click="toggleExpand(sale.id)"
                    >
                        <!-- Código -->
                        <div class="lg:col-span-2">
                            <p class="font-mono text-sm font-semibold text-amber-300">{{ sale.sale_code }}</p>
                            <p class="text-xs text-gray-500 lg:hidden">{{ formatDate(sale.created_at) }}</p>
                        </div>

                        <!-- Fecha -->
                        <div class="hidden lg:block lg:col-span-2">
                            <p class="text-sm text-gray-300">{{ formatDate(sale.created_at) }}</p>
                            <p class="text-xs text-gray-500">{{ formatTime(sale.created_at) }}</p>
                        </div>

                        <!-- Cliente -->
                        <div class="lg:col-span-2">
                            <p class="text-sm text-gray-200 truncate">{{ sale.customer_name }}</p>
                            <p class="text-xs text-gray-500">{{ sale.seller || '—' }}</p>
                        </div>

                        <!-- Ítems -->
                        <div class="lg:col-span-1 lg:text-center">
                            <span class="inline-flex items-center justify-center rounded-full bg-gray-800 px-2 py-0.5 text-xs text-gray-300">
                                {{ sale.items_count }}
                            </span>
                        </div>

                        <!-- Descuento -->
                        <div class="lg:col-span-1 lg:text-right">
                            <p v-if="sale.discount > 0" class="text-sm text-orange-300">-{{ currency(sale.discount) }}</p>
                            <p v-else class="text-sm text-gray-600">—</p>
                        </div>

                        <!-- Total -->
                        <div class="lg:col-span-2 lg:text-right">
                            <p class="text-sm font-bold text-gray-50">{{ currency(sale.total) }}</p>
                            <p class="text-xs text-emerald-400">+{{ currency(sale.profit) }} utilidad</p>
                        </div>

                        <!-- Métodos de pago -->
                        <div class="lg:col-span-1 flex flex-wrap gap-1">
                            <span
                                v-for="method in sale.payment_methods"
                                :key="method"
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="methodClass(method)"
                            >
                                {{ methodLabel(method) }}
                            </span>
                        </div>

                        <!-- Expand toggle -->
                        <div class="lg:col-span-1 lg:text-right">
                            <span class="text-gray-500 text-xs select-none">
                                {{ expanded.has(sale.id) ? '▲ cerrar' : '▼ detalle' }}
                            </span>
                        </div>
                    </div>

                    <!-- Expandable items -->
                    <div v-if="expanded.has(sale.id)" class="bg-gray-950/60 px-6 pb-4">
                        <table class="w-full text-xs mt-2">
                            <thead>
                                <tr class="text-gray-500 border-b border-gray-800">
                                    <th class="text-left py-1 font-medium">Producto</th>
                                    <th class="text-left py-1 font-medium">SKU</th>
                                    <th class="text-right py-1 font-medium">Cant.</th>
                                    <th class="text-right py-1 font-medium">Precio</th>
                                    <th class="text-right py-1 font-medium">Descuento</th>
                                    <th class="text-right py-1 font-medium">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                <tr v-for="item in sale.items" :key="item.id" class="text-gray-300">
                                    <td class="py-1.5">
                                        {{ item.product }}
                                        <span v-if="item.presentation_name" class="text-gray-500"> · {{ item.presentation_name }}</span>
                                    </td>
                                    <td class="py-1.5 text-gray-500">{{ item.sku }}</td>
                                    <td class="py-1.5 text-right">{{ item.quantity }}</td>
                                    <td class="py-1.5 text-right">
                                        <span v-if="item.discount_amount > 0" class="line-through text-gray-600 mr-1">
                                            {{ currency(item.original_price) }}
                                        </span>
                                        {{ currency(item.unit_price) }}
                                    </td>
                                    <td class="py-1.5 text-right text-orange-300">
                                        {{ item.discount_amount > 0 ? currency(item.discount_amount) : '—' }}
                                    </td>
                                    <td class="py-1.5 text-right font-semibold text-gray-100">
                                        {{ currency(item.unit_price * item.quantity) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div v-else class="px-4 py-10 text-center text-sm text-gray-500">
                No hay ventas en el período seleccionado.
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
            <div>{{ sales.data.length }} de {{ sales.total }} ventas</div>
            <div class="flex gap-2">
                <button
                    class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs disabled:opacity-30 hover:border-gray-500"
                    :disabled="!sales.prev_page_url"
                    @click="goTo(sales.prev_page_url)"
                >
                    ← Anterior
                </button>
                <span class="rounded-lg border border-gray-800 bg-gray-900 px-3 py-1.5 text-xs text-gray-400">
                    {{ sales.current_page }} / {{ sales.last_page }}
                </span>
                <button
                    class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs disabled:opacity-30 hover:border-gray-500"
                    :disabled="!sales.next_page_url"
                    @click="goTo(sales.next_page_url)"
                >
                    Siguiente →
                </button>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    sales:   { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    sellers: { type: Array,  default: () => [] },
});

const form = reactive({
    q:         props.filters.q         || '',
    date_from: props.filters.date_from || today(),
    date_to:   props.filters.date_to   || today(),
    seller_id: props.filters.seller_id || '',
});

const expanded = ref(new Set());

// ── Helpers ──────────────────────────────────────────────────────────────────

function today() {
    return new Date().toISOString().slice(0, 10);
}

function daysAgo(n) {
    const d = new Date();
    d.setDate(d.getDate() - n);
    return d.toISOString().slice(0, 10);
}

function startOfWeek() {
    const d = new Date();
    d.setDate(d.getDate() - d.getDay() + 1);
    return d.toISOString().slice(0, 10);
}

function startOfMonth() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
}

const currency = (val) => 'Q' + Number(val ?? 0).toFixed(2);

function formatDate(dt) {
    if (!dt) return '—';
    return new Date(dt).toLocaleDateString('es-GT', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTime(dt) {
    if (!dt) return '';
    return new Date(dt).toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
}

function methodLabel(m) {
    return { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transfer', credit: 'Crédito' }[m] ?? m;
}

function methodClass(m) {
    return {
        cash:     'bg-emerald-900/60 text-emerald-300',
        card:     'bg-blue-900/60 text-blue-300',
        transfer: 'bg-purple-900/60 text-purple-300',
        credit:   'bg-orange-900/60 text-orange-300',
    }[m] ?? 'bg-gray-800 text-gray-300';
}

// ── Date shortcuts ────────────────────────────────────────────────────────────

const dateShortcuts = [
    { label: 'Hoy',         from: today(),        to: today() },
    { label: 'Ayer',        from: daysAgo(1),      to: daysAgo(1) },
    { label: 'Esta semana', from: startOfWeek(),   to: today() },
    { label: 'Este mes',    from: startOfMonth(),  to: today() },
    { label: 'Últimos 7d',  from: daysAgo(6),      to: today() },
    { label: 'Últimos 30d', from: daysAgo(29),     to: today() },
];

function isActiveShortcut(s) {
    return form.date_from === s.from && form.date_to === s.to;
}

function applyShortcut(s) {
    form.date_from = s.from;
    form.date_to   = s.to;
    applyFilters();
}

// ── Actions ───────────────────────────────────────────────────────────────────

function toggleExpand(id) {
    if (expanded.value.has(id)) {
        expanded.value.delete(id);
    } else {
        expanded.value.add(id);
    }
    expanded.value = new Set(expanded.value);
}

function applyFilters() {
    router.get('/admin/sales', { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.q         = '';
    form.date_from = today();
    form.date_to   = today();
    form.seller_id = '';
    applyFilters();
}

function goTo(url) {
    if (url) router.visit(url, { preserveState: true, preserveScroll: true });
}
</script>
