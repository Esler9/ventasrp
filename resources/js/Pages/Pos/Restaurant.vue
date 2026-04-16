<template>
    <AppLayout title="Mesas Restaurante">
        <div class="space-y-5">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-100">Salón principal</h1>
                        <p class="text-xs text-gray-400">Administra mesas, disponibilidad y acceso rápido a gestión por mesa.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="/pos/kitchen" class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm font-semibold text-amber-300">
                            Ver cocina
                        </a>
                        <a href="/pos/delivery" class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm font-semibold text-emerald-300">
                            Repartidores
                        </a>
                        <button
                            type="button"
                            class="rounded-xl bg-brand-primary px-3 py-2 text-sm font-semibold text-white"
                            @click="openCreateTableModal"
                        >
                            + Agregar mesa
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-4 text-xs">
                    <span class="inline-flex items-center gap-1.5 text-gray-300">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        DISPONIBLE ({{ availableCount }})
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-gray-300">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                        OCUPADA ({{ occupiedCount }})
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-gray-300">
                        <span class="h-2.5 w-2.5 rounded-full bg-gray-500"></span>
                        INACTIVA ({{ inactiveCount }})
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        v-for="option in areaFilters"
                        :key="option.value"
                        type="button"
                        class="rounded-xl border px-3 py-1.5 text-xs font-semibold"
                        :class="selectedArea === option.value
                            ? 'border-sky-400 bg-sky-500/20 text-sky-100'
                            : 'border-gray-700 bg-gray-950/60 text-gray-300'"
                        @click="selectedArea = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <div v-if="firstError" class="rounded-xl border border-rose-700/60 bg-rose-900/20 px-3 py-2 text-sm text-rose-200">
                {{ firstError }}
            </div>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <article
                    v-for="table in filteredTables"
                    :key="table.id"
                    class="rounded-2xl border p-4"
                    :class="tableCardClass(table)"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span class="rounded-md px-2 py-1 text-[10px] font-semibold" :class="tableBadgeClass(table)">
                            {{ tableStatusLabel(table) }}
                        </span>
                        <button
                            type="button"
                            class="rounded-md border border-gray-700 px-2 py-1 text-[11px] text-gray-300"
                            @click="openEditTableModal(table)"
                        >
                            Editar
                        </button>
                    </div>

                    <div class="mt-5 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-2xl font-bold"
                             :class="tableCircleClass(table)">
                            {{ table.code || `T${table.id}` }}
                        </div>
                        <p class="mt-3 text-xl font-semibold text-gray-100">{{ table.name }}</p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ table.is_takeaway ? takeawayTypeLabel(table.takeaway_service_type) : 'Salón' }}
                            · {{ table.accounts.length }} cuenta(s)
                        </p>
                        <p v-if="tableOpenElapsed(table)" class="mt-1 text-[11px] text-gray-500">{{ tableOpenElapsed(table) }}</p>
                    </div>

                    <a
                        :href="`/pos/restaurant/tables/${table.id}/workspace`"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-brand-primary px-3 py-2 text-sm font-semibold text-white"
                    >
                        Gestionar mesa
                    </a>
                </article>
            </section>

            <p v-if="!filteredTables.length" class="rounded-xl border border-gray-800 bg-gray-900/70 px-4 py-5 text-center text-sm text-gray-400">
                No hay mesas para este filtro.
            </p>
        </div>

        <div v-if="tableModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4" @click.self="closeTableModal">
            <div class="w-full max-w-md rounded-2xl border border-gray-800 bg-gray-900 p-4">
                <h2 class="text-base font-semibold text-gray-100">{{ tableModalMode === 'create' ? 'Agregar mesa' : 'Editar mesa' }}</h2>
                <p class="mt-1 text-xs text-gray-400">Configura mesa para que aparezca en el tablero y en el flujo de POS.</p>

                <div class="mt-3 grid gap-3">
                    <div>
                        <label class="text-xs text-gray-400">Nombre</label>
                        <input v-model.trim="tableForm.name" type="text" maxlength="80" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" placeholder="Ej. Mesa 11" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Código</label>
                        <input v-model.trim="tableForm.code" type="text" maxlength="40" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" placeholder="Ej. T11" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Orden</label>
                        <input v-model.number="tableForm.sort_order" type="number" min="0" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" />
                    </div>

                    <label class="flex items-center gap-2 rounded-xl border border-gray-700 bg-gray-950/50 px-3 py-2 text-sm">
                        <input v-model="tableForm.is_takeaway" type="checkbox" />
                        <span>Mesa de takeaway</span>
                    </label>
                    <div v-if="tableForm.is_takeaway">
                        <label class="text-xs text-gray-400">Tipo de servicio para llevar</label>
                        <select v-model="tableForm.takeaway_service_type" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100">
                            <option value="pickup">Recoger en tienda</option>
                            <option value="delivery">Delivery</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 rounded-xl border border-gray-700 bg-gray-950/50 px-3 py-2 text-sm">
                        <input v-model="tableForm.is_active" type="checkbox" />
                        <span>Mesa activa</span>
                    </label>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-sm" @click="closeTableModal">Cancelar</button>
                    <button type="button" class="rounded-lg bg-brand-primary px-3 py-2 text-sm font-semibold text-white" @click="submitTable">
                        {{ tableModalMode === 'create' ? 'Crear mesa' : 'Guardar cambios' }}
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
    tables: { type: Array, default: () => [] },
});

const page = usePage();
const selectedArea = ref('all');

const tableModalOpen = ref(false);
const tableModalMode = ref('create');
const editingTableId = ref(null);

const tableForm = reactive({
    name: '',
    code: '',
    sort_order: 0,
    is_takeaway: false,
    takeaway_service_type: 'delivery',
    is_active: true,
});

const areaFilters = [
    { value: 'all', label: 'Todas' },
    { value: 'salon', label: 'Salón' },
    { value: 'takeaway', label: 'Takeaway' },
    { value: 'inactive', label: 'Inactivas' },
];

const firstError = computed(() => {
    const errors = page.props.errors || {};
    return Object.values(errors)[0] || null;
});

const filteredTables = computed(() => {
    if (selectedArea.value === 'all') return props.tables;
    if (selectedArea.value === 'salon') return props.tables.filter((table) => !table.is_takeaway && table.is_active);
    if (selectedArea.value === 'takeaway') return props.tables.filter((table) => table.is_takeaway && table.is_active);
    if (selectedArea.value === 'inactive') return props.tables.filter((table) => !table.is_active);
    return props.tables;
});

const availableCount = computed(() => props.tables.filter((table) => table.is_active && table.status === 'free').length);
const occupiedCount = computed(() => props.tables.filter((table) => table.is_active && table.status === 'occupied').length);
const inactiveCount = computed(() => props.tables.filter((table) => !table.is_active).length);

const tableStatusLabel = (table) => {
    if (!table.is_active) return 'INACTIVA';
    return table.status === 'occupied' ? 'OCUPADA' : 'DISPONIBLE';
};

const tableCardClass = (table) => {
    if (!table.is_active) return 'border-gray-700 bg-gray-900/40';
    return table.status === 'occupied'
        ? 'border-rose-500/40 bg-rose-500/5'
        : 'border-emerald-500/40 bg-emerald-500/5';
};

const tableBadgeClass = (table) => {
    if (!table.is_active) return 'bg-gray-700/30 text-gray-300';
    return table.status === 'occupied'
        ? 'bg-rose-500/20 text-rose-200'
        : 'bg-emerald-500/20 text-emerald-200';
};

const tableCircleClass = (table) => {
    if (!table.is_active) return 'bg-gray-700/30 text-gray-300';
    return table.status === 'occupied'
        ? 'bg-rose-500/10 text-rose-300'
        : 'bg-emerald-500/10 text-emerald-300';
};

const tableOpenElapsed = (table) => {
    const firstOpened = (table.accounts || [])
        .map((account) => account.opened_at)
        .filter(Boolean)
        .sort()[0];

    if (!firstOpened || table.status !== 'occupied') return '';

    const ts = Date.parse(String(firstOpened).replace(' ', 'T'));
    if (Number.isNaN(ts)) return '';

    const minutes = Math.max(0, Math.floor((Date.now() - ts) / 60000));
    if (minutes < 60) return `${minutes} min abierta`;

    const hours = Math.floor(minutes / 60);
    const rem = minutes % 60;
    return `${hours}h ${rem}m abierta`;
};

const openCreateTableModal = () => {
    tableModalMode.value = 'create';
    editingTableId.value = null;
    tableForm.name = '';
    tableForm.code = '';
    tableForm.sort_order = props.tables.length + 1;
    tableForm.is_takeaway = false;
    tableForm.takeaway_service_type = 'delivery';
    tableForm.is_active = true;
    tableModalOpen.value = true;
};

const openEditTableModal = (table) => {
    tableModalMode.value = 'edit';
    editingTableId.value = table.id;
    tableForm.name = table.name || '';
    tableForm.code = table.code || '';
    tableForm.sort_order = Number(table.sort_order || 0);
    tableForm.is_takeaway = Boolean(table.is_takeaway);
    tableForm.takeaway_service_type = table.takeaway_service_type || 'delivery';
    tableForm.is_active = Boolean(table.is_active);
    tableModalOpen.value = true;
};

const closeTableModal = () => {
    tableModalOpen.value = false;
};

const submitTable = () => {
    const payload = {
        name: tableForm.name,
        code: tableForm.code,
        sort_order: Number(tableForm.sort_order || 0),
        is_takeaway: tableForm.is_takeaway,
        takeaway_service_type: tableForm.is_takeaway ? tableForm.takeaway_service_type : null,
        is_active: tableForm.is_active,
    };

    if (tableModalMode.value === 'create') {
        router.post('/pos/restaurant/tables', payload, {
            preserveScroll: true,
            onSuccess: () => {
                tableModalOpen.value = false;
            },
        });

        return;
    }

    if (!editingTableId.value) return;

    router.post(`/pos/restaurant/tables/${editingTableId.value}`, payload, {
        preserveScroll: true,
        onSuccess: () => {
            tableModalOpen.value = false;
        },
    });
};

const takeawayTypeLabel = (type) => {
    if (type === 'pickup') return 'Recoger en tienda';
    if (type === 'delivery') return 'Delivery';
    return 'Takeaway';
};
</script>
