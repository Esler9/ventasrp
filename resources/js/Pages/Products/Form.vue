<template>
    <AppLayout :title="title">
        <div class="mx-auto max-w-3xl space-y-6">
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl bg-gray-900/80 p-5 ring-1 ring-black/30">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">Unidad base</label>
                        <input
                            v-model="form.unit_label"
                            type="text"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                            placeholder="Ej. Unidad, Litro, Metro"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">SKU / Código</label>
                        <input
                            v-model="form.sku"
                            type="text"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                            placeholder="Opcional, se genera del nombre si se deja vacío"
                        />
                    </div>
                    <div class="space-y-1 md:col-span-2">
                        <label class="text-sm text-gray-300">Descripción (opcional)</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                            placeholder="Resumen breve del producto"
                        ></textarea>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">Precio (Q)</label>
                        <input
                            v-model.number="form.price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">Stock</label>
                        <input
                            v-model.number="form.stock"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">Fecha de vencimiento</label>
                        <input
                            v-model="form.expires_at"
                            type="date"
                            class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        />
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <input id="active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400 focus:ring-amber-500" />
                        <label for="active" class="text-sm text-gray-300">Activo</label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm text-gray-300">Foto</label>
                    <div class="flex items-center gap-4">
                        <div class="h-20 w-20 overflow-hidden rounded-xl bg-gray-850 ring-1 ring-black/20">
                            <img v-if="preview" :src="preview" alt="Foto" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-gray-600 text-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 7 6 6m0 0 4-4 8 8M13 13h6v6M3 5h6v6" />
                                </svg>
                            </div>
                        </div>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800">
                            Subir foto
                            <input type="file" accept="image/*" class="hidden" @change="onFileChange" />
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-100">Presentaciones</p>
                            <p class="text-xs text-gray-400">Opcional: ej. Blíster, Caja, Pack</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-100 hover:bg-gray-800"
                            @click="addPresentation"
                        >
                            Agregar
                        </button>
                    </div>

                    <div v-if="form.presentations.length" class="space-y-3">
                        <div
                            v-for="(presentation, index) in form.presentations"
                            :key="presentation.id || index"
                            class="grid gap-2 rounded-xl border border-gray-800 bg-gray-900/60 p-3 md:grid-cols-5 md:items-center"
                        >
                            <div class="space-y-1 md:col-span-2">
                                <label class="text-xs text-gray-400">Nombre</label>
                                <input
                                    v-model="presentation.name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                                    placeholder="Ej. Blíster"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs text-gray-400">Factor (unidades)</label>
                                <input
                                    v-model.number="presentation.factor"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs text-gray-400">Precio (Q)</label>
                                <input
                                    v-model.number="presentation.price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border border-gray-800 bg-gray-950/80 px-3 py-2 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                                />
                            </div>
                            <div class="flex items-center gap-3 md:justify-end">
                                <label class="inline-flex items-center gap-2 text-xs text-gray-300">
                                    <input v-model="presentation.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400 focus:ring-amber-500" />
                                    Activa
                                </label>
                                <button
                                    type="button"
                                    class="text-xs text-red-400 hover:text-red-300"
                                    @click="removePresentation(index)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-lg border border-dashed border-gray-800 px-4 py-3 text-xs text-gray-400">
                        Sin presentaciones adicionales. Se venderá como {{ form.unit_label || 'Unidad' }}.
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <Link href="/admin/products" class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100 hover:bg-gray-800">
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        :disabled="form.processing"
                    >
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    name: props.product?.name ?? '',
    unit_label: props.product?.unit_label ?? 'Unidad',
    description: props.product?.description ?? '',
    sku: props.product?.sku ?? '',
    price: props.product?.price ?? '',
    stock: props.product?.stock ?? 0,
    expires_at: props.product?.expires_at ?? '',
    is_active: props.product?.is_active ?? true,
    photo: null,
    presentations: props.product?.presentations?.map((p) => ({
        id: p.id,
        name: p.name,
        factor: p.factor,
        price: p.price,
        is_active: p.is_active,
    })) ?? [],
});

const preview = ref(props.product?.photo_url ?? null);

const title = computed(() => (props.product ? 'Editar producto' : 'Crear producto'));

const onFileChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.photo = file;
    preview.value = URL.createObjectURL(file);
};

const addPresentation = () => {
    form.presentations.push({
        id: null,
        name: '',
        factor: 1,
        price: 0,
        is_active: true,
    });
};

const removePresentation = (index) => {
    form.presentations.splice(index, 1);
};

const submit = () => {
    if (props.product) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(`/admin/products/${props.product.id}`, {
            preserveScroll: true,
            forceFormData: true,
        });
    } else {
        form.post('/admin/products', {
            preserveScroll: true,
            forceFormData: true,
        });
    }
};
</script>

<style scoped>
.bg-gray-850 {
    background-color: #1f2937;
}
</style>
