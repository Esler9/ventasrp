<template>
    <AppLayout :title="purchase ? `Editar ${purchase.purchase_code}` : 'Nueva compra'">
        <form class="space-y-4" @submit.prevent="submit(false)">

            <!-- Encabezado de la compra -->
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <h2 class="mb-3 text-sm font-semibold text-gray-300 uppercase tracking-wide">Datos generales</h2>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="text-xs text-gray-400">Proveedor</label>
                        <select v-model="form.supplier_id"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400">
                            <option :value="null">— Sin proveedor —</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                {{ s.name }}{{ s.phone ? ` · ${s.phone}` : '' }}
                            </option>
                        </select>
                        <a href="/admin/suppliers" target="_blank"
                            class="mt-0.5 block text-right text-[11px] text-amber-400 hover:underline">
                            + Gestionar proveedores
                        </a>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Fecha de compra <span class="text-red-400">*</span></label>
                        <input v-model="form.purchase_date" type="date" required
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400"
                            :class="{ 'border-red-500': form.errors.purchase_date }" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Método de pago</label>
                        <select v-model="form.payment_method"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400">
                            <option :value="null">— No especificado —</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                            <option value="credit">Crédito proveedor</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Referencia / No. Factura</label>
                        <input v-model="form.payment_reference" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Notas</label>
                        <input v-model="form.notes" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                </div>
            </section>

            <!-- Buscador de productos -->
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <h2 class="mb-3 text-sm font-semibold text-gray-300 uppercase tracking-wide">Productos a comprar</h2>

                <!-- Buscador -->
                <div class="relative mb-3">
                    <input
                        v-model="productSearch"
                        type="text"
                        placeholder="Buscar producto por nombre o SKU..."
                        class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400"
                        @input="showDropdown = true"
                        @focus="showDropdown = true"
                    />
                    <!-- Dropdown resultados -->
                    <div v-if="showDropdown && filteredProducts.length"
                        class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-gray-700 bg-gray-900 shadow-xl">
                        <button
                            v-for="p in filteredProducts"
                            :key="p.id"
                            type="button"
                            class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm hover:bg-gray-800"
                            @click="addItem(p)"
                        >
                            <span>
                                <span class="font-semibold text-gray-100">{{ p.name }}</span>
                                <span class="ml-2 text-xs text-gray-500">{{ p.sku }}</span>
                            </span>
                            <span class="text-xs text-gray-400">
                                Stock: {{ p.stock }} · CPP actual: Q{{ money(p.cost_price) }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tabla de ítems -->
                <div v-if="form.items.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-700 text-xs text-gray-400">
                                <th class="pb-2 text-left font-medium">Producto</th>
                                <th class="pb-2 text-center font-medium w-24">Cantidad</th>
                                <th class="pb-2 text-right font-medium w-36">
                                    Costo unitario
                                    <span class="block text-[10px] text-gray-600 font-normal">(este ingreso)</span>
                                </th>
                                <th class="pb-2 text-right font-medium w-32">Subtotal</th>
                                <th class="pb-2 text-right font-medium w-28">CPP nuevo*</th>
                                <th class="pb-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <tr v-for="(item, idx) in form.items" :key="idx">
                                <td class="py-2 pr-3">
                                    <p class="font-semibold text-gray-100">{{ item.product_name }}</p>
                                    <p class="text-xs text-gray-500">{{ item.product_sku }} · {{ item.product_unit_label }} · Stock: {{ item.product_stock }}</p>
                                    <input v-model="item.note" type="text" placeholder="Nota (opcional)"
                                        class="mt-1 w-full rounded-lg border border-gray-800 bg-gray-950/60 px-2 py-1 text-xs text-gray-300 focus:border-amber-400" />
                                </td>
                                <td class="py-2 text-center">
                                    <input v-model.number="item.quantity" type="number" min="1"
                                        class="w-20 rounded-lg border border-gray-700 bg-gray-950/80 px-2 py-1.5 text-center text-sm text-gray-100 focus:border-amber-400"
                                        @input="recalcItem(idx)" />
                                </td>
                                <td class="py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <span class="text-gray-400 text-xs">Q</span>
                                        <input v-model.number="item.unit_cost" type="number" min="0" step="0.0001"
                                            class="w-28 rounded-lg border border-gray-700 bg-gray-950/80 px-2 py-1.5 text-right text-sm text-gray-100 focus:border-amber-400"
                                            @input="recalcItem(idx)" />
                                    </div>
                                    <p class="mt-0.5 text-right text-[10px] text-gray-600">
                                        CPP actual: Q{{ money(item.product_current_cost) }}
                                    </p>
                                </td>
                                <td class="py-2 text-right">
                                    <p class="font-semibold text-gray-100">Q{{ money(item.subtotal) }}</p>
                                </td>
                                <td class="py-2 text-right">
                                    <!-- Preview del nuevo CPP si se recibe ahora -->
                                    <p class="text-xs font-semibold text-emerald-300">
                                        Q{{ previewCPP(item) }}
                                    </p>
                                    <p class="text-[10px] text-gray-600">estimado</p>
                                </td>
                                <td class="py-2 pl-2">
                                    <button type="button"
                                        class="rounded-lg p-1.5 text-red-400 hover:bg-red-500/10"
                                        @click="removeItem(idx)">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-1 text-[10px] text-gray-600">
                        * CPP nuevo = (stock_actual × costo_actual + qty × costo_compra) / (stock_actual + qty)
                    </p>
                </div>

                <div v-else class="rounded-xl border border-dashed border-gray-700 py-8 text-center text-sm text-gray-500">
                    Busca y agrega productos para registrar esta compra.
                </div>
            </section>

            <!-- Totales -->
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="grid gap-3 md:grid-cols-3 flex-1">
                        <div>
                            <label class="text-xs text-gray-400">Subtotal</label>
                            <p class="mt-1 text-xl font-bold text-gray-100">Q{{ money(subtotal) }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400">Descuento global (Q)</label>
                            <div class="mt-1 flex items-center gap-1">
                                <span class="text-gray-400 text-sm">Q</span>
                                <input v-model.number="form.discount" type="number" min="0" step="0.01"
                                    class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400">Total</label>
                            <p class="mt-1 text-2xl font-bold text-amber-300">Q{{ money(total) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Errores -->
                <div v-if="form.errors.purchase || form.errors.items" class="mt-3 rounded-xl border border-red-600/40 bg-red-950/20 px-3 py-2 text-sm text-red-300">
                    {{ form.errors.purchase || form.errors.items }}
                </div>

                <!-- Botones -->
                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <a href="/admin/purchases"
                        class="rounded-xl border border-gray-600 px-5 py-2.5 text-center text-sm text-gray-200 hover:bg-gray-800">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="rounded-xl border border-gray-600 px-5 py-2.5 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                        :disabled="form.processing || form.items.length === 0">
                        <i class="fa-regular fa-floppy-disk mr-1"></i>
                        Guardar borrador
                    </button>
                    <button type="button"
                        class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-400 disabled:opacity-50"
                        :disabled="form.processing || form.items.length === 0"
                        @click="submit(true)">
                        <i class="fa-solid fa-box-open mr-1"></i>
                        Recibir ahora
                    </button>
                </div>

                <p class="mt-2 text-right text-[11px] text-gray-600">
                    "Recibir ahora" aplica el CPP y actualiza el stock inmediatamente.
                </p>
            </section>

        </form>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    purchase:  { type: Object, default: null },
    suppliers: { type: Array,  default: () => [] },
    products:  { type: Array,  default: () => [] },
});

// ── Formulario ──────────────────────────────────────────────────────────────
const form = useForm({
    supplier_id:       props.purchase?.supplier_id       ?? null,
    purchase_date:     props.purchase?.purchase_date     ?? new Date().toISOString().slice(0, 10),
    payment_method:    props.purchase?.payment_method    ?? null,
    payment_reference: props.purchase?.payment_reference ?? '',
    notes:             props.purchase?.notes             ?? '',
    discount:          props.purchase?.discount          ?? 0,
    receive_now:       false,
    items: (props.purchase?.items ?? []).map(i => ({
        product_id:            i.product_id,
        product_name:          i.product_name,
        product_sku:           i.product_sku,
        product_unit_label:    i.product_unit_label,
        product_current_cost:  i.product_current_cost,
        product_stock:         i.product_stock,
        quantity:              i.quantity,
        unit_cost:             i.unit_cost,
        subtotal:              i.subtotal,
        note:                  i.note ?? '',
    })),
});

// ── Búsqueda de productos ────────────────────────────────────────────────────
const productSearch = ref('');
const showDropdown  = ref(false);

const filteredProducts = computed(() => {
    const q = productSearch.value.toLowerCase().trim();
    if (!q) return props.products.slice(0, 12);
    return props.products
        .filter(p => p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q))
        .slice(0, 12);
});

// Cerrar dropdown al hacer click fuera
if (typeof document !== 'undefined') {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) showDropdown.value = false;
    });
}

const addItem = (product) => {
    // Si ya está en la lista, incrementar cantidad
    const existing = form.items.find(i => i.product_id === product.id);
    if (existing) {
        existing.quantity++;
        recalcItem(form.items.indexOf(existing));
    } else {
        form.items.push({
            product_id:           product.id,
            product_name:         product.name,
            product_sku:          product.sku,
            product_unit_label:   product.unit_label,
            product_current_cost: product.cost_price,
            product_stock:        product.stock,
            quantity:             1,
            unit_cost:            product.cost_price, // Costo actual como sugerencia
            subtotal:             product.cost_price,
            note:                 '',
        });
    }
    productSearch.value = '';
    showDropdown.value  = false;
};

const removeItem = (idx) => form.items.splice(idx, 1);

const recalcItem = (idx) => {
    const item = form.items[idx];
    if (item) {
        item.subtotal = round(item.quantity * item.unit_cost, 2);
    }
};

// ── Totales ──────────────────────────────────────────────────────────────────
const subtotal = computed(() =>
    form.items.reduce((acc, item) => acc + (item.subtotal || 0), 0)
);
const total = computed(() =>
    Math.max(0, subtotal.value - (form.discount || 0))
);

// ── Preview CPP ──────────────────────────────────────────────────────────────
const previewCPP = (item) => {
    const stock     = Number(item.product_stock) || 0;
    const oldCost   = Number(item.product_current_cost) || 0;
    const qty       = Number(item.quantity)    || 0;
    const newCost   = Number(item.unit_cost)   || 0;
    if (qty <= 0) return money(oldCost);
    if (stock <= 0 || oldCost <= 0) return money(newCost);
    return money((stock * oldCost + qty * newCost) / (stock + qty));
};

// ── Submit ───────────────────────────────────────────────────────────────────
const submit = (receiveNow) => {
    form.receive_now = receiveNow;

    const payload = {
        supplier_id:       form.supplier_id,
        purchase_date:     form.purchase_date,
        payment_method:    form.payment_method,
        payment_reference: form.payment_reference || null,
        notes:             form.notes || null,
        discount:          form.discount || 0,
        receive_now:       receiveNow,
        items: form.items.map(i => ({
            product_id: i.product_id,
            quantity:   i.quantity,
            unit_cost:  i.unit_cost,
            note:       i.note || null,
        })),
    };

    if (props.purchase?.id) {
        form.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/purchases/${props.purchase.id}`, { preserveScroll: true });
    } else {
        form.transform(() => payload).post('/admin/purchases', { preserveScroll: true });
    }
};

// ── Helpers ──────────────────────────────────────────────────────────────────
const money = (v) => Number(v || 0).toFixed(2);
const round = (v, d) => Math.round(v * 10 ** d) / 10 ** d;
</script>
