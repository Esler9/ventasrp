<template>
    <AppLayout title="Usuarios">
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-4 ring-1 ring-black/30">
                <h2 class="text-lg font-semibold text-gray-50">Equipo</h2>
                <p class="mt-1 text-sm text-gray-400">Revisa quién tiene acceso y valida sus roles o PIN configurados.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div v-for="user in users" :key="user.id" class="rounded-2xl border border-gray-800 bg-gray-900/60 p-4 ring-1 ring-black/30">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Nombre</p>
                            <p class="text-lg font-semibold text-gray-50">{{ user.name }}</p>
                        </div>
                        <span class="rounded-full border border-gray-700 bg-gray-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-200">
                            {{ roleLabel(user.role) }}
                        </span>
                    </div>
                    <div class="mt-3 space-y-1 text-sm text-gray-300">
                        <p class="text-gray-400">Usuario: <span class="text-gray-200">{{ user.email }}</span></p>
                        <p class="text-gray-400">PIN configurado: <span class="text-gray-200">{{ user.has_pin ? 'Sí' : 'No' }}</span></p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const roleLabel = (role) => {
    if (role === 'admin') return 'Admin';
    if (role === 'seller') return 'Vendedor';
    return role || 'Usuario';
};

const users = props.users.map((user) => ({
    ...user,
    has_pin: Boolean(user.has_pin),
}));
</script>
