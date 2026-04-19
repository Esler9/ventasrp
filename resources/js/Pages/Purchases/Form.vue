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
                    <div v-if="showDropdown && (filteredProducts.length || productSearch.trim())"
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
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 border-t border-gray-700 px-3 py-2.5 text-left text-sm text-amber-400 hover:bg-gray-800"
                            @click="openQuickCreate"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Crear producto nuevo{{ productSearch.trim() ? `: "${productSearch.trim()}"` : '' }}
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

        <!-- Modal: crear producto rápido -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="quickCreate.open"
                    class="fixed inset-0 z-50 flex items-start justify-center bg-black/70 px-4 py-6 overflow-y-auto"
                    @click.self="quickCreate.open = false">
                    <div class="w-full max-w-lg rounded-2xl border border-gray-700 bg-gray-900 shadow-2xl my-auto">

                        <!-- Cabecera -->
                        <div class="flex items-center justify-between border-b border-gray-800 px-5 py-4">
                            <div>
                                <h3 class="text-sm font-bold text-gray-100">Nuevo producto</h3>
                                <p class="text-xs text-gray-500">El producto se agregará a esta compra y quedará activo en el sistema.</p>
                            </div>
                            <button type="button" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-800 hover:text-gray-200" @click="quickCreate.open = false">
                                <i class="fa-solid fa-xmark text-base"></i>
                            </button>
                        </div>

                        <!-- Cuerpo -->
                        <div class="space-y-5 px-5 py-4">

                            <!-- Foto -->
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-gray-800 ring-1 ring-black/30">
                                    <img v-if="quickCreate.photoPreview" :src="quickCreate.photoPreview" alt="Foto" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center text-gray-600">
                                        <i class="fa-solid fa-image text-xl"></i>
                                    </div>
                                </div>
                                <label class="cursor-pointer rounded-xl border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-300 hover:bg-gray-800">
                                    <i class="fa-solid fa-upload mr-1"></i> Subir foto
                                    <input type="file" accept="image/*" class="hidden" @change="onQuickCreatePhoto" />
                                </label>
                                <button v-if="quickCreate.photoPreview" type="button"
                                    class="text-xs text-red-400 hover:text-red-300"
                                    @click="quickCreate.photoFile = null; quickCreate.photoPreview = null">
                                    Quitar
                                </button>
                            </div>

                            <!-- Nombre + SKU -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="text-xs text-gray-400">Nombre <span class="text-red-400">*</span></label>
                                    <input v-model="quickCreate.name" type="text" autofocus
                                        class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400">SKU / Código</label>
                                    <input v-model="quickCreate.sku" type="text" placeholder="Auto-generado"
                                        class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400">Unidad <span class="text-red-400">*</span></label>
                                    <input v-model="quickCreate.unit_label" type="text" placeholder="Ej: Unidad, Kg, Caja"
                                        class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                </div>
                            </div>

                            <!-- Categoría + crear categoría -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs text-gray-400">Categoría <span class="text-red-400">*</span></label>
                                    <button type="button"
                                        class="rounded-md border border-gray-700 px-2 py-0.5 text-[11px] font-semibold text-gray-300 hover:bg-gray-800"
                                        @click="quickCreate.newCategoryOpen = !quickCreate.newCategoryOpen; quickCreate.newCategoryName = ''; quickCreate.newCategoryError = ''">
                                        {{ quickCreate.newCategoryOpen ? 'Cancelar' : '+ Nueva categoría' }}
                                    </button>
                                </div>
                                <select v-model="quickCreate.category_id"
                                    class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400">
                                    <option :value="null">— Seleccionar —</option>
                                    <option v-for="c in localCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                                <!-- Formulario inline nueva categoría -->
                                <div v-if="quickCreate.newCategoryOpen" class="mt-2 space-y-2 rounded-xl border border-gray-700 bg-gray-950/60 p-3">
                                    <input v-model="quickCreate.newCategoryName" type="text" placeholder="Nombre de categoría"
                                        class="w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                    <input v-model="quickCreate.newCategoryDesc" type="text" placeholder="Descripción (opcional)"
                                        class="w-full rounded-lg border border-gray-700 bg-gray-950 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                    <p v-if="quickCreate.newCategoryError" class="text-xs text-red-400">{{ quickCreate.newCategoryError }}</p>
                                    <button type="button"
                                        class="w-full rounded-lg bg-amber-500/20 px-3 py-1.5 text-xs font-semibold text-amber-300 hover:bg-amber-500/30 disabled:opacity-50"
                                        :disabled="quickCreate.newCategorySaving"
                                        @click="saveQuickCategory">
                                        {{ quickCreate.newCategorySaving ? 'Guardando...' : 'Guardar categoría' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Descripción -->
                            <div>
                                <label class="text-xs text-gray-400">Descripción</label>
                                <textarea v-model="quickCreate.description" rows="2" placeholder="Opcional"
                                    class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 resize-none"></textarea>
                            </div>

                            <!-- Precios -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-400">Precio venta <span class="text-red-400">*</span></label>
                                    <div class="mt-1 flex items-center gap-1">
                                        <span class="text-xs text-gray-500">Q</span>
                                        <input v-model.number="quickCreate.price" type="number" min="0" step="0.01"
                                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400">Costo compra <span class="text-red-400">*</span></label>
                                    <div class="mt-1 flex items-center gap-1">
                                        <span class="text-xs text-gray-500">Q</span>
                                        <input v-model.number="quickCreate.cost_price" type="number" min="0" step="0.0001"
                                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Stock alert + vencimiento + activo -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-400">Stock de alerta</label>
                                    <input v-model.number="quickCreate.stock_alert" type="number" min="0"
                                        class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400">Fecha vencimiento</label>
                                    <input v-model="quickCreate.expires_at" type="date"
                                        class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400" />
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="qc-active" v-model="quickCreate.is_active" type="checkbox"
                                    class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400 focus:ring-amber-500" />
                                <label for="qc-active" class="text-sm text-gray-300">Producto activo</label>
                            </div>

                            <p v-if="quickCreate.error" class="rounded-lg border border-red-700/40 bg-red-950/20 px-3 py-2 text-xs text-red-300">
                                {{ quickCreate.error }}
                            </p>
                        </div>

                        <!-- Pie -->
                        <div class="flex justify-end gap-2 border-t border-gray-800 px-5 py-4">
                            <button type="button"
                                class="rounded-xl border border-gray-600 px-4 py-2 text-sm text-gray-300 hover:bg-gray-800"
                                @click="quickCreate.open = false">
                                Cancelar
                            </button>
                            <button type="button"
                                class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-amber-400 disabled:opacity-50"
                                :disabled="quickCreate.saving"
                                @click="submitQuickCreate">
                                <i class="fa-solid fa-floppy-disk mr-1"></i>
                                {{ quickCreate.saving ? 'Guardando...' : 'Crear y agregar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    purchase:   { type: Object, default: null },
    suppliers:  { type: Array,  default: () => [] },
    products:   { type: Array,  default: () => [] },
    categories: { type: Array,  default: () => [] },
});

const localProducts   = ref([...props.products]);
const localCategories = ref([...props.categories]);

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
    if (!q) return localProducts.value.slice(0, 12);
    return localProducts.value
        .filter(p => p.name.toLowerCase().includes(q) || (p.sku ?? '').toLowerCase().includes(q))
        .slice(0, 12);
});

// ── Crear producto rápido ─────────────────────────────────────────────────────
const quickCreate = reactive({
    open: false,
    saving: false,
    error: '',
    // Campos básicos
    name: '',
    sku: '',
    unit_label: '',
    description: '',
    // Categoría
    category_id: null,
    newCategoryOpen: false,
    newCategoryName: '',
    newCategoryDesc: '',
    newCategoryError: '',
    newCategorySaving: false,
    // Precios
    price: 0,
    cost_price: 0,
    // Extras
    stock_alert: 0,
    expires_at: '',
    is_active: true,
    // Foto
    photoFile: null,
    photoPreview: null,
});

const openQuickCreate = () => {
    Object.assign(quickCreate, {
        open: true,
        saving: false,
        error: '',
        name: productSearch.value.trim(),
        sku: '',
        unit_label: '',
        description: '',
        category_id: null,
        newCategoryOpen: false,
        newCategoryName: '',
        newCategoryDesc: '',
        newCategoryError: '',
        newCategorySaving: false,
        price: 0,
        cost_price: 0,
        stock_alert: 0,
        expires_at: '',
        is_active: true,
        photoFile: null,
        photoPreview: null,
    });
    showDropdown.value = false;
};

const onQuickCreatePhoto = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    quickCreate.photoFile    = file;
    quickCreate.photoPreview = URL.createObjectURL(file);
};

const saveQuickCategory = async () => {
    const name = quickCreate.newCategoryName.trim();
    if (!name) { quickCreate.newCategoryError = 'Ingresa el nombre de la categoría.'; return; }
    quickCreate.newCategorySaving = true;
    quickCreate.newCategoryError  = '';
    try {
        const { data } = await axios.post('/admin/categories', {
            name,
            description: quickCreate.newCategoryDesc.trim() || null,
            is_active: true,
        }, { headers: { Accept: 'application/json' } });
        const created = { id: Number(data.id), name: data.name };
        localCategories.value = [...localCategories.value, created]
            .sort((a, b) => a.name.localeCompare(b.name));
        quickCreate.category_id      = created.id;
        quickCreate.newCategoryOpen  = false;
        quickCreate.newCategoryName  = '';
        quickCreate.newCategoryDesc  = '';
    } catch (e) {
        quickCreate.newCategoryError = e?.response?.data?.errors?.name?.[0]
            || e?.response?.data?.message
            || 'No se pudo crear la categoría.';
    } finally {
        quickCreate.newCategorySaving = false;
    }
};

const submitQuickCreate = async () => {
    quickCreate.error = '';
    if (!quickCreate.name.trim())       { quickCreate.error = 'El nombre es requerido.'; return; }
    if (!quickCreate.unit_label.trim()) { quickCreate.error = 'La unidad es requerida.'; return; }
    if (!quickCreate.category_id)       { quickCreate.error = 'Selecciona una categoría.'; return; }

    quickCreate.saving = true;
    try {
        const fd = new FormData();
        fd.append('name',        quickCreate.name.trim());
        fd.append('unit_label',  quickCreate.unit_label.trim());
        fd.append('category_id', quickCreate.category_id);
        fd.append('price',       quickCreate.price);
        fd.append('cost_price',  quickCreate.cost_price);
        fd.append('stock_alert', quickCreate.stock_alert ?? 0);
        fd.append('is_active',   quickCreate.is_active ? '1' : '0');
        if (quickCreate.sku.trim())         fd.append('sku',         quickCreate.sku.trim());
        if (quickCreate.description.trim()) fd.append('description', quickCreate.description.trim());
        if (quickCreate.expires_at)         fd.append('expires_at',  quickCreate.expires_at);
        if (quickCreate.photoFile)          fd.append('photo',       quickCreate.photoFile);

        const { data } = await axios.post('/admin/products/quick-store', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        localProducts.value.push(data);
        localProducts.value.sort((a, b) => a.name.localeCompare(b.name));
        addItem(data);
        quickCreate.open    = false;
        productSearch.value = '';
    } catch (e) {
        const errors = e?.response?.data?.errors;
        quickCreate.error = errors
            ? Object.values(errors).flat().join(' ')
            : 'Error al crear el producto.';
    } finally {
        quickCreate.saving = false;
    }
};

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
