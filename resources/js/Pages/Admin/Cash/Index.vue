<template>
    <AppLayout title="Caja">
        <div class="space-y-4">
            <section v-if="!openSession" class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4 space-y-3">
                <h2 class="text-lg font-semibold text-gray-100">Abrir caja</h2>
                <p class="text-sm text-gray-400">Debes abrir una caja para registrar ventas en POS.</p>

                <form class="grid gap-3 md:grid-cols-3" @submit.prevent="openCash">
                    <div class="space-y-1 md:col-span-1">
                        <label class="text-xs text-gray-400">Caja</label>
                        <select v-model="openForm.cash_register_id" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100">
                            <option value="" disabled>Selecciona caja</option>
                            <option v-for="register in registers" :key="register.id" :value="register.id">
                                {{ register.name }} · {{ register.branch_name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1 md:col-span-1">
                        <label class="text-xs text-gray-400">Saldo inicial</label>
                        <input v-model.number="openForm.opening_amount" type="number" min="0" step="0.01" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div class="space-y-1 md:col-span-1">
                        <label class="text-xs text-gray-400">Nota</label>
                        <input v-model="openForm.open_note" type="text" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100" />
                    </div>
                    <div class="md:col-span-3">
                        <button type="submit" class="rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black">
                            Abrir caja
                        </button>
                    </div>
                </form>
            </section>

            <section v-else class="space-y-4">
                <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                    <p class="text-sm text-gray-400">Caja {{ openSession.register_name }} · {{ openSession.branch_name }}</p>
                    <p class="text-xs text-gray-500">Abierta: {{ openSession.opened_at }}</p>
                </div>

                <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                    <h3 class="text-lg font-semibold text-gray-100">Movimientos del sistema</h3>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between"><span class="text-gray-400">Saldo inicial</span><span>Q{{ money(summary.opening) }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-gray-400">Ventas (efectivo)</span><span class="text-emerald-400">+Q{{ money(summary.cash_sales) }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-gray-400">Ingresos varios</span><span class="text-emerald-400">+Q{{ money(summary.incomes) }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-gray-400">Gastos / retiros</span><span class="text-red-400">-Q{{ money(summary.expenses) }}</span></div>
                        <div class="border-t border-gray-700 pt-2 flex items-center justify-between font-semibold text-lg">
                            <span>Total esperado</span>
                            <span>Q{{ money(summary.expected) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4 space-y-3">
                    <h3 class="text-lg font-semibold text-gray-100">Movimiento rápido</h3>
                    <form class="grid gap-3 md:grid-cols-4" @submit.prevent="saveMovement">
                        <select v-model="movementForm.type" class="rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100">
                            <option value="income">Ingreso</option>
                            <option value="expense">Gasto / Retiro</option>
                        </select>
                        <input v-model.number="movementForm.amount" type="number" min="0.01" step="0.01" placeholder="Monto" class="rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100" />
                        <input v-model="movementForm.note" type="text" placeholder="Motivo" class="rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100 md:col-span-2" />
                        <button type="submit" class="rounded-xl border border-gray-600 px-4 py-3 text-sm font-semibold text-gray-100 md:col-span-4">Guardar movimiento</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4 space-y-3">
                    <h3 class="text-lg font-semibold text-gray-100">Cierre y arqueo</h3>
                    <form class="grid gap-3 md:grid-cols-3" @submit.prevent="closeCash">
                        <div class="space-y-1">
                            <label class="text-xs text-gray-400">Monto real en caja</label>
                            <input v-model.number="closeForm.counted_amount" type="number" min="0" step="0.01" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100" />
                        </div>
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs text-gray-400">Observaciones</label>
                            <input v-model="closeForm.close_note" type="text" class="w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm text-gray-100" />
                        </div>
                        <div class="md:col-span-3">
                            <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white">Cerrar caja y arqueo</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    registers: { type: Array, default: () => [] },
    open_session: { type: Object, default: null },
    summary: { type: Object, default: () => ({}) },
    sales_by_method: { type: Object, default: () => ({}) },
    recent_movements: { type: Array, default: () => [] },
});

const openSession = props.open_session;

const openForm = useForm({
    cash_register_id: props.registers[0]?.id || '',
    opening_amount: 0,
    open_note: '',
});

const movementForm = useForm({
    type: 'income',
    amount: '',
    note: '',
});

const closeForm = useForm({
    counted_amount: props.summary.expected || 0,
    close_note: '',
});

const openCash = () => {
    openForm.post('/admin/cash/open', { preserveScroll: true });
};

const saveMovement = () => {
    movementForm.post('/admin/cash/movements', {
        preserveScroll: true,
        onSuccess: () => movementForm.reset('amount', 'note'),
    });
};

const closeCash = () => {
    closeForm.post('/admin/cash/close', { preserveScroll: true });
};

const money = (value) => Number(value || 0).toFixed(2);
</script>
