<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center px-4">
        <div class="w-full max-w-md rounded-3xl bg-gray-900/80 p-8 ring-1 ring-black/30 shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <div class="mx-auto h-14 w-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M5 7l1 12h12l1-12M9 11v2m6-2v2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold">Iniciar sesión</h1>
                <p class="text-sm text-gray-400">Accede al punto de venta o panel</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1">
                    <label class="text-sm text-gray-300">Correo</label>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                        required
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-sm text-gray-300">Contraseña</label>
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                        required
                    />
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-300">
                    <input id="remember" v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-gray-700 bg-gray-900 text-amber-400 focus:ring-amber-500" />
                    <label for="remember">Recordarme</label>
                </div>

                <div v-if="form.errors.email" class="text-sm text-red-400">
                    {{ form.errors.email }}
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black shadow hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500"
                    :disabled="form.processing"
                >
                    Entrar
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
