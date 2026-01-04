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
                        class="rounded-lg border border-gray-700 px-3 py-2 text-xs font-semibold text-gray-200 hover:bg-gray-800 hover-lift"
                        @click="logout"
                    >
                        Cerrar sesión
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
