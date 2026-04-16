<template>
    <AppLayout title="Proveedores">
        <div class="space-y-4">

            <!-- Buscador + botón nuevo -->
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="md:col-span-3">
                        <label class="text-xs text-gray-400">Buscar proveedor</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nombre, contacto, teléfono, NIT..."
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            class="w-full rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black hover:bg-amber-300"
                            @click="openNew"
                        >
                            <i class="fa-solid fa-plus mr-1"></i> Nuevo proveedor
                        </button>
                    </div>
                </div>
            </section>

            <!-- Formulario crear/editar -->
            <section v-if="showForm" class="rounded-2xl border border-amber-500/30 bg-amber-950/10 p-4 ring-1 ring-black/30">
                <h2 class="mb-3 text-base font-semibold text-amber-200">
                    {{ form.id ? 'Editar proveedor' : 'Nuevo proveedor' }}
                </h2>
                <form class="grid gap-3 md:grid-cols-3" @submit.prevent="submit">
                    <div>
                        <label class="text-xs text-gray-400">Nombre <span class="text-red-400">*</span></label>
                        <input v-model="form.name" type="text" required
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400"
                            :class="{ 'border-red-500': form.errors.name }" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Contacto</label>
                        <input v-model="form.contact_name" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Teléfono</label>
                        <input v-model="form.phone" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Correo electrónico</label>
                        <input v-model="form.email" type="email"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400"
                            :class="{ 'border-red-500': form.errors.email }" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">NIT / Tax ID</label>
                        <input v-model="form.tax_id" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Estado</label>
                        <select v-model="form.is_active"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400">
                            <option :value="true">Activo</option>
                            <option :value="false">Inactivo</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Dirección</label>
                        <input v-model="form.address" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">Notas</label>
                        <input v-model="form.notes" type="text"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400" />
                    </div>
                    <div class="md:col-span-3 flex justify-end gap-2 pt-1">
                        <button type="button"
                            class="rounded-xl border border-gray-600 px-4 py-2 text-sm text-gray-200 hover:bg-gray-800"
                            @click="closeForm">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="rounded-xl bg-amber-400 px-5 py-2 text-sm font-semibold text-black hover:bg-amber-300"
                            :disabled="form.processing">
                            {{ form.id ? 'Guardar cambios' : 'Crear proveedor' }}
                        </button>
                    </div>
                </form>
            </section>

            <!-- Lista -->
            <section class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/80 ring-1 ring-black/30">
                <div v-if="suppliers.data.length" class="divide-y divide-gray-800">
                    <article
                        v-for="s in suppliers.data"
                        :key="s.id"
                        class="flex flex-col gap-2 px-4 py-4 md:flex-row md:items-center md:justify-between"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-gray-100">{{ s.name }}</p>
                                <span v-if="!s.is_active"
                                    class="rounded-full bg-red-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase text-red-300">
                                    Inactivo
                                </span>
                                <span class="rounded-full bg-gray-700/60 px-2 py-0.5 text-[10px] text-gray-400">
                                    {{ s.purchases_count }} {{ s.purchases_count === 1 ? 'compra' : 'compras' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">
                                <span v-if="s.contact_name">{{ s.contact_name }} · </span>
                                <span v-if="s.phone">{{ s.phone }} · </span>
                                <span v-if="s.email">{{ s.email }} · </span>
                                <span v-if="s.tax_id">NIT: {{ s.tax_id }}</span>
                            </p>
                            <p v-if="s.address" class="text-xs text-gray-500">{{ s.address }}</p>
                            <p v-if="s.notes" class="text-xs text-gray-500 italic">{{ s.notes }}</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button type="button"
                                class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800"
                                @click="edit(s)">
                                Editar
                            </button>
                            <button type="button"
                                class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10"
                                @click="remove(s)">
                                Eliminar
                            </button>
                        </div>
                    </article>
                </div>
                <div v-else class="px-4 py-8 text-center text-sm text-gray-400">
                    No hay proveedores registrados.
                </div>
            </section>

            <!-- Paginación -->
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>Mostrando {{ suppliers.data.length }} de {{ suppliers.total }}</span>
                <div class="flex gap-2">
                    <button class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!suppliers.prev_page_url"
                        @click="goTo(suppliers.prev_page_url)">Anterior</button>
                    <button class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!suppliers.next_page_url"
                        @click="goTo(suppliers.next_page_url)">Siguiente</button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    suppliers: { type: Object, required: true },
    filters:   { type: Object, default: () => ({ q: '' }) },
});

const search   = ref(props.filters.q || '');
const showForm = ref(false);

const form = useForm({
    id:           null,
    name:         '',
    contact_name: '',
    phone:        '',
    email:        '',
    address:      '',
    tax_id:       '',
    notes:        '',
    is_active:    true,
});

// --- filtros ---
const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
const runSearch = debounce(() => {
    router.get('/admin/suppliers', { q: search.value || undefined }, { preserveState: true, replace: true });
});
watch(search, runSearch);

// --- formulario ---
const openNew = () => {
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showForm.value = true;
};

const edit = (s) => {
    form.id           = s.id;
    form.name         = s.name;
    form.contact_name = s.contact_name || '';
    form.phone        = s.phone || '';
    form.email        = s.email || '';
    form.address      = s.address || '';
    form.tax_id       = s.tax_id || '';
    form.notes        = s.notes || '';
    form.is_active    = s.is_active;
    showForm.value    = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeForm = () => {
    showForm.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const payload = {
        name:         form.name,
        contact_name: form.contact_name || null,
        phone:        form.phone || null,
        email:        form.email || null,
        address:      form.address || null,
        tax_id:       form.tax_id || null,
        notes:        form.notes || null,
        is_active:    form.is_active,
    };

    if (form.id) {
        form.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/suppliers/${form.id}`, {
                preserveScroll: true,
                onSuccess: () => closeForm(),
            });
    } else {
        form.transform(() => payload).post('/admin/suppliers', {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
    }
};

const remove = (s) => {
    if (s.purchases_count > 0) {
        alert(`"${s.name}" tiene ${s.purchases_count} compras. Desactívalo en lugar de eliminarlo.`);
        return;
    }
    if (!confirm(`¿Eliminar proveedor "${s.name}"?`)) return;
    router.delete(`/admin/suppliers/${s.id}`, { preserveScroll: true });
};

const goTo = (url) => { if (url) router.visit(url, { preserveState: true, preserveScroll: true }); };
</script>
