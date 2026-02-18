<template>
    <AppLayout title="Categorías">
        <div class="space-y-4">
            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-400">Buscar categoría</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nombre o descripción"
                            class="mt-1 w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            class="w-full rounded-xl border border-gray-600 px-4 py-3 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                            @click="resetForm"
                        >
                            Nueva categoría
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-800 bg-gray-900/80 p-4 ring-1 ring-black/30">
                <h2 class="text-base font-semibold text-gray-100">{{ form.id ? 'Editar categoría' : 'Crear categoría' }}</h2>
                <form class="mt-3 grid gap-3 md:grid-cols-4" @submit.prevent="submit">
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs text-gray-400">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-xs text-gray-400">Descripción</label>
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Opcional"
                            class="w-full rounded-xl border border-gray-700 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-300 md:col-span-1">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400 focus:ring-amber-500" />
                        Activa
                    </label>
                    <div class="flex gap-2 md:col-span-3 md:justify-end">
                        <button
                            v-if="form.id"
                            type="button"
                            class="rounded-xl border border-gray-600 px-4 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800"
                            @click="resetForm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300"
                            :disabled="form.processing"
                        >
                            {{ form.id ? 'Guardar cambios' : 'Guardar categoría' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/80 ring-1 ring-black/30">
                <div v-if="categories.data.length" class="divide-y divide-gray-800">
                    <article v-for="category in categories.data" :key="category.id" class="space-y-3 px-4 py-4 md:flex md:items-center md:justify-between md:space-y-0">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-100">{{ category.name }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                    :class="category.is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-gray-700 text-gray-300'"
                                >
                                    {{ category.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">{{ category.description || 'Sin descripción' }}</p>
                            <p class="text-xs text-gray-500">{{ category.products_count }} producto(s)</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800"
                                @click="editCategory(category)"
                            >
                                Editar
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-semibold text-red-300 hover:bg-red-500/10"
                                @click="removeCategory(category)"
                            >
                                Eliminar
                            </button>
                        </div>
                    </article>
                </div>
                <div v-else class="px-4 py-6 text-center text-sm text-gray-400">
                    No hay categorías registradas.
                </div>
            </section>

            <div class="flex items-center justify-between text-xs text-gray-500">
                <div>Mostrando {{ categories.data.length }} de {{ categories.total }} resultados</div>
                <div class="flex gap-2">
                    <button
                        class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!categories.prev_page_url"
                        @click="goTo(categories.prev_page_url)"
                    >
                        Anterior
                    </button>
                    <button
                        class="rounded-lg border border-gray-700 px-3 py-1"
                        :disabled="!categories.next_page_url"
                        @click="goTo(categories.next_page_url)"
                    >
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
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const search = ref(props.filters.q || '');
const form = useForm({
    id: null,
    name: '',
    description: '',
    is_active: true,
});

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const debouncedSearch = debounce((value) => {
    router.get('/admin/categories', { q: value }, { preserveState: true, replace: true, preserveScroll: true });
});

watch(search, (value) => debouncedSearch(value));

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.is_active = true;
};

const editCategory = (category) => {
    form.id = category.id;
    form.name = category.name;
    form.description = category.description || '';
    form.is_active = !!category.is_active;
};

const submit = () => {
    const payload = {
        name: form.name,
        description: form.description || null,
        is_active: !!form.is_active,
    };

    if (form.id) {
        form.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/categories/${form.id}`, {
                preserveScroll: true,
                onSuccess: () => resetForm(),
            });
        return;
    }

    form.transform(() => payload).post('/admin/categories', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const removeCategory = (category) => {
    if (!confirm(`¿Eliminar la categoría "${category.name}"?`)) return;

    router.delete(`/admin/categories/${category.id}`, {
        preserveScroll: true,
    });
};

const goTo = (url) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true, preserveState: true });
};
</script>

