<template>
    <teleport to="body">
        <transition name="fade">
            <div
                v-if="state.active"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
            >
                <div class="h-12 w-12 rounded-full border-4 border-amber-400 border-t-transparent animate-spin" />
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { useGlobalLoaderState } from '../composables/useGlobalLoader';

const state = useGlobalLoaderState();
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 150ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
