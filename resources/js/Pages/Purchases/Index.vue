<template>
    <AppLayout title="Compras">
        <div class="space-y-4">

            <!-- Resumen -->
            <section class="grid gap-3 md:grid-cols-3">
                <article class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4">
                    <p class="text-xs text-gray-400">Órdenes en período</p>
                    <p class="mt-1 text-2xl font-bold text-gray-100">{{ summary.total_orders }}</p>
                </article>
                <article class="rounded-2xl border border-emerald-700/40 bg-emerald-950/20 p-4">
                    <p class="text-xs text-emerald-300">Total recibido</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-200">Q{{ money(summary.total_received) }}</p>
                </article>
                <article class="rounded-2xl border border-amber-700/40 bg-amber-950/20 p-4">
                    <p class="text-xs text-amber-300">Borradores pendientes</p>
                    <p class="mt-1 text-2xl font-bold text-amber-200">Q{{ money(summary.total_draft) }}</p>
                </article>
            </section>

            <!-- Filtros -->
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="grid gap-3 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Buscar</label>
                        <input v-model="filters.q" type="text" placeholder="Código o proveedor..."
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Estado</label>
                        <select v-model="filters.status"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100">
                            <option value="">Todos</option>
                            <option value="draft">Borrador</option>
                            <option value="received">Recibida</option>
                            <option value="cancelled">Anulada</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Proveedor</label>
                        <select v-model="filters.supplier_id"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100">
                            <option value="">Todos</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Desde</label>
                        <input v-model="filters.date_from" type="date"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Hasta</label>
                        <input v-model="filters.date_to" type="date"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" class="flex-1 rounded-xl border border-gray-600 px-3 py-2.5 text-sm text-gray-200 hover:bg-gray-800"
                            @click="resetFilters">Limpiar</button>
                        <a href="/admin/purchases/create"
                            class="flex-1 rounded-xl bg-amber-400 px-3 py-2.5 text-center text-sm font-semibold text-black hover:bg-amber-300">
                            <i class="fa-solid fa-plus mr-1"></i> Nueva
                        </a>
                    </div>
                </div>
            </section>

            <!-- Tabla -->
            <section class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/80 ring-1 ring-black/30">
                <!-- Cabecera desktop -->
                <div class="hidden grid-cols-12 gap-2 border-b border-gray-800 bg-gray-900 px-4 py-3 text-xs font-semibold uppercase text-gray-400 md:grid">
                    <div class="col-span-2">Código</div>
                    <div class="col-span-3">Proveedor</div>
                    <div class="col-span-2">Fecha</div>
                    <div class="col-span-1 text-center">Ítems</div>
                    <div class="col-span-2 text-right">Total</div>
                    <div class="col-span-2 text-right">Acciones</div>
                </div>

                <div v-if="purchases.data.length" class="divide-y divide-gray-800">
                    <article v-for="p in purchases.data" :key="p.id"
                        class="grid grid-cols-1 gap-2 px-4 py-4 md:grid-cols-12 md:items-center">
                        <!-- Código + estado -->
                        <div class="md:col-span-2">
                            <p class="font-mono text-sm font-semibold text-gray-100">{{ p.purchase_code }}</p>
                            <span class="mt-0.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                :class="statusClass(p.status)">
                                {{ statusLabel(p.status) }}
                            </span>
                        </div>
                        <!-- Proveedor + usuario -->
                        <div class="md:col-span-3">
                            <p class="text-sm text-gray-100">{{ p.supplier_name }}</p>
                            <p class="text-xs text-gray-500">Por: {{ p.user_name }}</p>
                        </div>
                        <!-- Fecha -->
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-300">{{ p.purchase_date }}</p>
                            <p v-if="p.payment_method" class="text-xs text-gray-500">{{ payMethodLabel(p.payment_method) }}</p>
                        </div>
                        <!-- Ítems -->
                        <div class="md:col-span-1 md:text-center">
                            <p class="text-sm text-gray-300">{{ p.items_count }}</p>
                        </div>
                        <!-- Total -->
                        <div class="md:col-span-2 md:text-right">
                            <p class="text-sm font-semibold text-gray-100">Q{{ money(p.total) }}</p>
                            <p v-if="p.discount > 0" class="text-xs text-amber-400">-Q{{ money(p.discount) }} desc.</p>
                        </div>
                        <!-- Acciones -->
                        <div class="flex gap-2 md:col-span-2 md:justify-end">
                            <template v-if="p.status === 'draft'">
                                <a :href="`/admin/purchases/${p.id}/edit`"
                                    class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800">
                                    Editar
                                </a>
                                <button type="button"
                                    class="rounded-lg border border-emerald-600/50 px-3 py-1.5 text-xs font-semibold text-emerald-300 hover:bg-emerald-500/10"
                                    @click="receive(p)">
                                    Recibir
                                </button>
                                <button type="button"
                                    class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10"
                                    @click="destroy(p)">
                                    Eliminar
                                </button>
                            </template>
                            <span v-else class="text-xs text-gray-600 italic">Sin acciones</span>
                        </div>
                    </article>
                </div>
                <div v-else class="px-4 py-8 text-center text-sm text-gray-400">
                    No hay compras registradas con los filtros actuales.
                </div>
            </section>

            <!-- Paginación -->
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ purchases.data.length }} de {{ purchases.total }} resultados</span>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!purchases.prev_page_url"
                        @click="goTo(purchases.prev_page_url)">Anterior</button>
                    <button class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!purchases.next_page_url"
                        @click="goTo(purchases.next_page_url)">Siguiente</button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    purchases: { type: Object, required: true },
    suppliers: { type: Array,  default: () => [] },
    summary:   { type: Object, default: () => ({ total_orders: 0, total_received: 0, total_draft: 0 }) },
    filters:   { type: Object, default: () => ({}) },
});

const filters = reactive({
    q:           props.filters.q           || '',
    status:      props.filters.status      || '',
    supplier_id: props.filters.supplier_id || '',
    date_from:   props.filters.date_from   || '',
    date_to:     props.filters.date_to     || '',
});

const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
const runFilters = () => {
    const q = {};
    if (filters.q)           q.q           = filters.q;
    if (filters.status)      q.status      = filters.status;
    if (filters.supplier_id) q.supplier_id = filters.supplier_id;
    if (filters.date_from)   q.date_from   = filters.date_from;
    if (filters.date_to)     q.date_to     = filters.date_to;
    router.get('/admin/purchases', q, { preserveState: true, replace: true });
};
const debounced = debounce(runFilters);
watch(() => filters.q, debounced);
watch(() => [filters.status, filters.supplier_id, filters.date_from, filters.date_to], runFilters);

const resetFilters = () => {
    filters.q = ''; filters.status = ''; filters.supplier_id = '';
    filters.date_from = ''; filters.date_to = '';
    runFilters();
};

const receive = (p) => {
    if (!confirm(`¿Marcar "${p.purchase_code}" como recibida?\nEsto actualizará el stock y el costo promedio de cada producto.`)) return;
    router.post(`/admin/purchases/${p.id}/receive`, {}, { preserveScroll: true });
};

const destroy = (p) => {
    if (!confirm(`¿Eliminar el borrador "${p.purchase_code}"?`)) return;
    router.delete(`/admin/purchases/${p.id}`, { preserveScroll: true });
};

const goTo = (url) => { if (url) router.visit(url, { preserveState: true }); };

const money = (v) => Number(v || 0).toFixed(2);

const statusLabel = (s) => ({ draft: 'Borrador', received: 'Recibida', cancelled: 'Anulada' }[s] ?? s);
const statusClass = (s) => ({
    draft:     'bg-amber-500/20 text-amber-300',
    received:  'bg-emerald-500/20 text-emerald-300',
    cancelled: 'bg-red-500/20 text-red-300',
}[s] ?? 'bg-gray-700 text-gray-300');

const payMethodLabel = (m) => ({ cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia', credit: 'Crédito' }[m] ?? m);
</script>
