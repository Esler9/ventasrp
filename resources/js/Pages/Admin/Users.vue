<template>
    <AppLayout title="Usuarios">
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-4 ring-1 ring-black/30 fade-in-soft">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-50">Equipo</h2>
                        <p class="mt-1 text-sm text-gray-400">Crea, edita y define PIN para accesos rápidos.</p>
                    </div>
                    <button v-if="isEditing" type="button" class="text-sm text-amber-300 underline" @click="startCreate">
                        Crear nuevo
                    </button>
                </div>

                <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="space-y-1 sm:col-span-1">
                        <label class="text-sm text-gray-300">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                    <div class="space-y-1 sm:col-span-1">
                        <label class="text-sm text-gray-300">Usuario</label>
                        <input
                            v-model="form.username"
                            type="text"
                            autocomplete="username"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                        <p class="text-xs text-gray-500">Usa este usuario con PIN para entrar rápido.</p>
                    </div>
                    <div class="space-y-1 sm:col-span-1">
                        <label class="text-sm text-gray-300">Correo</label>
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                    <div class="space-y-1 sm:col-span-1">
                        <label class="text-sm text-gray-300">Rol</label>
                        <select
                            v-model="form.role"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 focus:border-amber-400 focus:ring-amber-400"
                        >
                            <option v-for="role in roles" :key="role.key" :value="role.key">
                                {{ role.label }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1 sm:col-span-1">
                        <label class="text-sm text-gray-300">Contraseña</label>
                        <input
                            v-model="form.password"
                            type="password"
                            :required="!isEditing"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                        />
                        <p class="text-xs text-gray-500" v-if="isEditing">Déjala en blanco para mantener la actual.</p>
                    </div>
                    <div class="space-y-1 sm:col-span-1">
                        <label class="text-sm text-gray-300">PIN</label>
                        <input
                            v-model="form.pin"
                            type="password"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            autocomplete="one-time-code"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                        />
                        <p class="text-xs text-gray-500">Deja vacío para no cambiarlo.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <div v-if="hasErrors" class="mb-2 text-sm text-red-400">
                            {{ firstError }}
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                :disabled="form.processing"
                            >
                                {{ isEditing ? 'Actualizar usuario' : 'Crear usuario' }}
                            </button>
                            <button
                                v-if="isEditing"
                                type="button"
                                class="text-sm text-gray-300 underline"
                                @click="startCreate"
                            >
                                Cancelar edición
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="rounded-2xl border border-gray-800 bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm text-gray-400">Nombre</p>
                            <p class="text-lg font-semibold text-gray-50">{{ user.name }}</p>
                        </div>
                        <span class="rounded-full border border-gray-700 bg-gray-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-200">
                            {{ user.role_label }}
                        </span>
                    </div>
                    <div class="mt-3 space-y-1 text-sm text-gray-300">
                        <p class="text-gray-400">Usuario: <span class="text-gray-200">{{ user.username || 'Sin usuario' }}</span></p>
                        <p class="text-gray-400">Correo: <span class="text-gray-200">{{ user.email }}</span></p>
                        <p class="text-gray-400">PIN configurado: <span class="text-gray-200">{{ user.has_pin ? 'Sí' : 'No' }}</span></p>
                    </div>
                    <button
                        type="button"
                        class="mt-3 text-sm font-semibold text-amber-300 underline"
                        @click="startEdit(user)"
                    >
                        Editar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const users = props.users.map((user) => ({
    ...user,
    has_pin: Boolean(user.has_pin),
}));

const defaultRole = computed(() => props.roles[0]?.key || 'seller_cashier');

const form = useForm({
    id: null,
    name: '',
    username: '',
    email: '',
    role: defaultRole.value,
    password: '',
    pin: '',
});

const isEditing = computed(() => form.id !== null);
const hasErrors = computed(() => Object.keys(form.errors).length > 0);
const firstError = computed(() => Object.values(form.errors)[0]);

const startEdit = (user) => {
    form.id = user.id;
    form.name = user.name;
    form.username = user.username || '';
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    form.pin = '';
    form.clearErrors();
};

const startCreate = () => {
    form.id = null;
    form.name = '';
    form.username = '';
    form.email = '';
    form.role = defaultRole.value;
    form.password = '';
    form.pin = '';
    form.clearErrors();
};

const submit = () => {
    const payload = {
        name: form.name,
        username: form.username,
        email: form.email,
        role: form.role,
        password: isEditing.value ? form.password || null : form.password,
        pin: form.pin === '' ? null : form.pin,
    };

    const onSuccess = () => {
        startCreate();
    };

    if (isEditing.value) {
        form.transform(() => ({
            ...payload,
            _method: 'PUT',
        })).post(`/admin/users/${form.id}`, { onSuccess });
    } else {
        form.transform(() => payload).post('/admin/users', { onSuccess });
    }
};
</script>
