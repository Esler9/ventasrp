<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center px-4">
        <GlobalLoader />
        <div class="w-full max-w-md rounded-3xl bg-gray-900/80 p-8 ring-1 ring-black/30 shadow-2xl space-y-6 fade-in-soft">
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

            <div class="grid grid-cols-2 gap-2 rounded-xl bg-gray-950/60 p-1">
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm font-semibold transition"
                    :class="isPinLogin ? 'bg-transparent text-gray-400' : 'bg-gray-800 text-amber-200 shadow-inner'"
                    @click="setMethod('password')"
                >
                    Correo y contraseña
                </button>
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm font-semibold transition"
                    :class="isPinLogin ? 'bg-gray-800 text-amber-200 shadow-inner' : 'bg-transparent text-gray-400'"
                    @click="setMethod('pin')"
                >
                    Usuario y PIN
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <template v-if="!isPinLogin">
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
                </template>

                <template v-else>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">Usuario</label>
                        <input
                            v-model="form.username"
                            type="text"
                            autocomplete="username"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm text-gray-300">PIN</label>
                        <input
                            v-model="form.pin"
                            type="password"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            autocomplete="one-time-code"
                            class="w-full rounded-xl border border-gray-800 bg-gray-950/80 px-3 py-3 text-sm text-gray-100 placeholder-gray-500 focus:border-amber-400 focus:ring-amber-400"
                            required
                        />
                    </div>
                </template>

                <div v-if="form.errors.email || form.errors.password || form.errors.username || form.errors.pin" class="text-sm text-red-400">
                    {{ form.errors.email || form.errors.password || form.errors.username || form.errors.pin }}
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
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GlobalLoader from '../../Components/GlobalLoader.vue';

const form = useForm({
    method: 'pin',
    email: '',
    password: '',
    username: '',
    pin: '',
    remember: false,
});

const isPinLogin = computed(() => form.method === 'pin');

const setMethod = (method) => {
    form.method = method;
    form.clearErrors();
};

const submit = () => {
    const payload = isPinLogin.value
        ? {
              method: 'pin',
              username: form.username,
              pin: form.pin,
          }
        : {
              method: 'password',
              email: form.email,
              password: form.password,
              remember: form.remember,
          };

    form.transform(() => payload).post('/login', {
        onFinish: () => {
            form.transform((data) => data);
            form.reset('password', 'pin');
        },
    });
};
</script>
