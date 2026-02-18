<template>
    <AppLayout title="Configuraciones">
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="lg:col-span-2 rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <h2 class="text-lg font-semibold text-gray-50">Marca y tema</h2>
                <p class="mt-1 text-sm text-gray-400">Configura logo y colores principales del sistema.</p>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs text-gray-400">Color primario</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input v-model="form.primary_color" type="color" class="h-11 w-14 rounded border border-gray-700 bg-gray-900" />
                                <input v-model="form.primary_color" type="text" class="flex-1 rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="text-xs text-gray-400">Color secundario</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input v-model="form.secondary_color" type="color" class="h-11 w-14 rounded border border-gray-700 bg-gray-900" />
                                <input v-model="form.secondary_color" type="text" class="flex-1 rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-400">Logo</label>
                        <input type="file" accept="image/*" class="mt-1 block w-full rounded-xl border border-gray-700 bg-gray-950/70 px-3 py-3 text-sm" @change="onLogoChange" />
                        <p class="mt-1 text-xs text-gray-500">PNG/JPG, máximo 2MB.</p>
                    </div>

                    <div v-if="hasErrors" class="text-sm text-red-400">{{ firstError }}</div>

                    <button type="submit" class="rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black" :disabled="form.processing">
                        Guardar configuraciones
                    </button>
                </form>
            </section>

            <aside class="rounded-2xl border border-gray-800 bg-gray-900/70 p-4">
                <h3 class="text-sm font-semibold text-gray-200">Vista previa</h3>
                <div class="mt-3 rounded-2xl border border-gray-800 bg-gray-950/60 p-4" :style="previewStyle">
                    <img v-if="previewLogo" :src="previewLogo" alt="Logo" class="mb-3 h-12 w-12 rounded-xl object-cover" />
                    <div v-else class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--brand-primary)] text-white">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-50">VentasRP</p>
                    <button type="button" class="mt-3 rounded-xl px-3 py-2 text-sm font-semibold text-white" :style="{ backgroundColor: form.primary_color }">
                        Boton primario
                    </button>
                    <button type="button" class="mt-2 block rounded-xl px-3 py-2 text-sm font-semibold text-white" :style="{ backgroundColor: form.secondary_color }">
                        Boton secundario
                    </button>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            app_logo_url: null,
            primary_color: '#0773A4',
            secondary_color: '#0EA5E9',
        }),
    },
});

const form = useForm({
    app_logo: null,
    primary_color: props.settings.primary_color || '#0773A4',
    secondary_color: props.settings.secondary_color || '#0EA5E9',
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);
const firstError = computed(() => Object.values(form.errors)[0] || '');

const previewLogo = computed(() => {
    if (form.app_logo instanceof File) {
        return URL.createObjectURL(form.app_logo);
    }

    return props.settings.app_logo_url;
});

const previewStyle = computed(() => ({
    '--brand-primary': form.primary_color,
    '--brand-secondary': form.secondary_color,
}));

const onLogoChange = (event) => {
    form.app_logo = event.target.files?.[0] || null;
};

const submit = () => {
    form.post('/admin/settings', {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>
