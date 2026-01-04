<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 flex flex-col">
        <GlobalLoader />
        <div class="mx-auto w-full max-w-6xl px-4 pt-3">
            <FlashBanner />
        </div>
        <header class="border-b border-gray-800 bg-gray-900/80 backdrop-blur fade-in-soft">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Panel</p>
                    <h1 class="text-xl font-semibold text-gray-50">{{ title }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <slot name="actions" />
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-700/80 bg-gray-850 text-gray-200 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-red-400/80 hover:bg-red-500/15 hover:text-red-200 focus:outline-none focus:ring-2 focus:ring-red-400/50 active:translate-y-0"
                        aria-label="Cerrar sesión"
                        title="Cerrar sesión"
                        @click="logout"
                    >
                        <i class="fa-solid fa-power-off text-lg"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 mx-auto w-full max-w-6xl px-4 py-6 pb-24 fade-in-soft">
            <slot />
        </main>

        <BottomNav />
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import BottomNav from '../Components/BottomNav.vue';
import GlobalLoader from '../Components/GlobalLoader.vue';
import FlashBanner from '../Components/FlashBanner.vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/logout');
};
</script>
