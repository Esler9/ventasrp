<template>
    <AppLayout title="Panel">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.key"
                class="rounded-2xl bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft"
            >
                <p class="text-sm text-gray-400">{{ card.title }}</p>
                <p class="mt-2 text-2xl font-semibold text-gray-50">{{ card.headline }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ card.description }}</p>
                <Link
                    :href="card.href"
                    class="mt-3 inline-flex items-center gap-2 rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-100"
                >
                    {{ card.button }}
                </Link>
            </div>

            <div
                v-if="!cards.length"
                class="sm:col-span-2 lg:col-span-4 rounded-2xl bg-gray-900/60 p-5 ring-1 ring-black/30 text-sm text-gray-400"
            >
                No hay módulos habilitados para este usuario.
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    cards: {
        type: Array,
        default: () => [],
    },
});
</script>
