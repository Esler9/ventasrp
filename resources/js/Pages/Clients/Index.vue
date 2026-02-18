<template>
    <AppLayout title="Clientes">
        <div class="space-y-4">
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Buscar cliente</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nombre, teléfono, correo o NIT"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="flex items-end">
                        <button
                            v-if="canCreate"
                            type="button"
                            class="w-full rounded-xl border border-gray-600 px-4 py-3 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                            @click="resetForm"
                        >
                            Nuevo cliente
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <h2 class="text-base font-semibold text-gray-100">{{ form.id ? 'Editar cliente' : 'Crear cliente' }}</h2>
                <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Teléfono</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">Correo</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-gray-400">NIT / Documento</label>
                        <input
                            v-model="form.tax_id"
                            type="text"
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs text-gray-400">Dirección</label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-300 md:col-span-1">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400 focus:ring-amber-500" />
                        Activo
                    </label>
                    <div class="flex gap-2 md:col-span-1 md:justify-end">
                        <button
                            v-if="form.id"
                            type="button"
                            class="rounded-xl border border-gray-600 px-4 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                            @click="resetForm"
                        >
                            Cancelar
                        </button>
                        <button
                            v-if="(form.id && canEdit) || (!form.id && canCreate)"
                            type="submit"
                            class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300"
                            :disabled="form.processing"
                        >
                            {{ form.id ? 'Guardar cambios' : 'Guardar cliente' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/80 ring-1 ring-black/30">
                <div v-if="clients.data.length" class="divide-y divide-gray-800">
                    <article v-for="client in clients.data" :key="client.id" class="space-y-3 px-4 py-4 md:flex md:items-center md:justify-between md:space-y-0">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-100">{{ client.name }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                    :class="client.is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-gray-700 text-gray-300'"
                                >
                                    {{ client.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">Tel: {{ client.phone || '—' }} · NIT: {{ client.tax_id || 'CF' }}</p>
                            <p class="text-xs text-gray-500">{{ client.email || 'Sin correo' }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-if="canEdit"
                                type="button"
                                class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800"
                                @click="editClient(client)"
                            >
                                Editar
                            </button>
                            <button
                                v-if="canDelete"
                                type="button"
                                class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10"
                                @click="removeClient(client)"
                            >
                                Eliminar
                            </button>
                        </div>
                    </article>
                </div>
                <div v-else class="px-4 py-6 text-center text-sm text-gray-400">
                    No hay clientes registrados.
                </div>
            </section>

            <div class="flex items-center justify-between text-xs text-gray-500">
                <div>Mostrando {{ clients.data.length }} de {{ clients.total }} resultados</div>
                <div class="flex gap-2">
                    <button
                        class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!clients.prev_page_url"
                        @click="goTo(clients.prev_page_url)"
                    >
                        Anterior
                    </button>
                    <button
                        class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!clients.next_page_url"
                        @click="goTo(clients.next_page_url)"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    clients: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const search = ref(props.filters.q || '');
const page = usePage();
const form = useForm({
    id: null,
    name: '',
    phone: '',
    email: '',
    tax_id: '',
    address: '',
    is_active: true,
});

const can = (permission) => {
    const permissions = page.props.auth?.user?.permissions || [];
    return permissions.includes('*') || permissions.includes(permission);
};

const canEdit = computed(() => can('clients.edit'));
const canDelete = computed(() => can('clients.delete'));
const canCreate = computed(() => can('clients.create'));

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const debouncedSearch = debounce((value) => {
    router.get('/admin/clients', { q: value }, { preserveState: true, replace: true, preserveScroll: true });
});

watch(search, (value) => debouncedSearch(value));

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.is_active = true;
};

const editClient = (client) => {
    form.id = client.id;
    form.name = client.name;
    form.phone = client.phone || '';
    form.email = client.email || '';
    form.tax_id = client.tax_id || '';
    form.address = client.address || '';
    form.is_active = !!client.is_active;
};

const submit = () => {
    const payload = {
        name: form.name,
        phone: form.phone || null,
        email: form.email || null,
        tax_id: form.tax_id || null,
        address: form.address || null,
        is_active: !!form.is_active,
    };

    if (form.id) {
        form.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/clients/${form.id}`, {
                preserveScroll: true,
                onSuccess: () => resetForm(),
            });
        return;
    }

    form.transform(() => payload).post('/admin/clients', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const removeClient = (client) => {
    if (!confirm(`¿Eliminar el cliente "${client.name}"?`)) return;

    router.delete(`/admin/clients/${client.id}`, {
        preserveScroll: true,
    });
};

const goTo = (url) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>
