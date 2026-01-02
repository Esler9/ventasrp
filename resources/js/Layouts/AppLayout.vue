<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 flex flex-col">
        <header class="border-b border-gray-800 bg-gray-900/80 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Panel</p>
                    <h1 class="text-xl font-semibold text-gray-50">{{ title }}</h1>
                </div>
                <slot name="actions" />
            </div>
        </header>

        <main class="flex-1 mx-auto w-full max-w-6xl px-4 py-6 pb-24">
            <slot />
        </main>

        <BottomNav />
    </div>
</template>

<script setup>
import BottomNav from '../Components/BottomNav.vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});
</script>
