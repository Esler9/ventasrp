<template>
    <AppLayout title="Repartidores">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <div>
                    <h1 class="text-lg font-semibold text-gray-100">Panel de Repartidores</h1>
                    <p class="text-xs text-gray-400">Disponibilidad y cuentas Delivery asignadas.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/pos" class="rounded-xl border border-gray-700 bg-gray-950/60 px-3 py-2 text-sm text-gray-200">Volver a mesas</a>
                    <button type="button" class="rounded-xl bg-emerald-500 px-3 py-2 text-sm font-semibold text-white" @click="openCreateRiderModal = true">
                        + Repartidor
                    </button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-gray-100">Repartidores activos</h2>
                    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="rider in riders" :key="rider.id" class="rounded-xl border border-gray-800 bg-gray-950/50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-100">{{ rider.name }}</p>
                                    <p class="text-xs text-gray-400">{{ rider.phone || 'Sin teléfono' }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-full px-2 py-1 text-[10px] font-semibold"
                                    :class="rider.is_available ? 'bg-emerald-500/20 text-emerald-200' : 'bg-rose-500/20 text-rose-200'"
                                    @click="toggleAvailability(rider)"
                                >
                                    {{ rider.is_available ? 'Disponible' : 'No disponible' }}
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                <p class="text-xs text-gray-400">Cuentas asignadas: {{ rider.accounts.length }}</p>
                                <div v-if="rider.accounts.length" class="space-y-1">
                                    <div v-for="account in rider.accounts" :key="`${rider.id}-${account.id}`" class="rounded-lg border border-gray-800 bg-gray-900/60 px-2 py-1.5 text-xs">
                                        <p class="font-semibold text-gray-100">{{ account.table_name }} · {{ account.label }}</p>
                                        <p class="text-gray-400">{{ account.items_count }} item(s) · Q{{ money(account.total) }}</p>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-gray-500">Sin cuentas asignadas.</p>
                            </div>
                        </article>
                    </div>
                </section>

                <aside class="rounded-2xl border border-gray-800 bg-gray-900/70 p-3">
                    <h2 class="text-sm font-semibold text-gray-100">Delivery sin repartidor</h2>
                    <div class="mt-3 space-y-2">
                        <article
                            v-for="account in unassignedAccounts"
                            :key="account.id"
                            class="rounded-xl border border-amber-700/40 bg-amber-900/10 p-2"
                        >
                            <p class="text-sm font-semibold text-gray-100">{{ account.table_name }} · {{ account.label }}</p>
                            <p class="text-xs text-gray-400">{{ account.items_count }} item(s) · Q{{ money(account.total) }}</p>
                            <p v-if="account.pending_work" class="mt-1 text-[11px] text-amber-200">Pendiente de cocina</p>
                        </article>
                        <p v-if="!unassignedAccounts.length" class="text-xs text-gray-500">Todas las cuentas delivery tienen repartidor.</p>
                    </div>
                </aside>
            </div>
        </div>

        <div v-if="openCreateRiderModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/65 p-4" @click.self="openCreateRiderModal = false">
            <div class="w-full max-w-md rounded-2xl border border-gray-800 bg-gray-900 p-4">
                <h2 class="text-base font-semibold text-gray-100">Nuevo repartidor</h2>
                <div class="mt-3 grid gap-3">
                    <div>
                        <label class="text-xs text-gray-400">Nombre</label>
                        <input v-model.trim="newRider.name" type="text" maxlength="120" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Teléfono</label>
                        <input v-model.trim="newRider.phone" type="text" maxlength="40" class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm" />
                    </div>
                    <label class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-950/60 p-2 text-sm">
                        <input v-model="newRider.is_available" type="checkbox" />
                        <span>Disponible</span>
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-700 px-3 py-2 text-sm" @click="openCreateRiderModal = false">Cancelar</button>
                    <button type="button" class="rounded-lg bg-emerald-500 px-3 py-2 text-sm font-semibold text-white" @click="createRider">Crear</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    riders: { type: Array, default: () => [] },
    unassigned_accounts: { type: Array, default: () => [] },
});

const openCreateRiderModal = ref(false);
const newRider = reactive({
    name: '',
    phone: '',
    is_available: true,
});

const unassignedAccounts = props.unassigned_accounts || [];
const riders = props.riders || [];

const createRider = () => {
    if (!newRider.name.trim()) return;

    router.post('/pos/restaurant/delivery-riders', {
        name: newRider.name,
        phone: newRider.phone || null,
        is_available: newRider.is_available,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newRider.name = '';
            newRider.phone = '';
            newRider.is_available = true;
            openCreateRiderModal.value = false;
        },
    });
};

const toggleAvailability = (rider) => {
    if (!rider) return;

    router.post(`/pos/restaurant/delivery-riders/${rider.id}/availability`, {
        is_available: !rider.is_available,
    }, {
        preserveScroll: true,
    });
};

const money = (value) => Number(value || 0).toFixed(2);
</script>
