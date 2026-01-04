<template>
    <transition name="fade">
        <div
            v-if="visible && message"
            class="mb-4 flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg"
            :class="message.type === 'error' ? 'border-red-400/40 bg-red-900/30 text-red-50' : 'border-amber-300/40 bg-amber-900/20 text-amber-50'"
        >
            <div class="mt-0.5">
                <svg v-if="message.type === 'error'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01m-6.938 0h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L4.34 14c-.77 1.333.192 3 1.732 3Z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="flex-1 space-y-1">
                <p class="text-sm font-semibold">{{ message.title }}</p>
                <p v-if="message.description" class="text-xs opacity-90">{{ message.description }}</p>
                <slot />
            </div>
            <button type="button" class="text-xs text-gray-200 hover:text-white" @click="visible = false">Cerrar</button>
        </div>
    </transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);

const rawFlash = computed(() => page.props?.flash || {});

const message = computed(() => {
    const success = rawFlash.value?.success;
    const error = rawFlash.value?.error;

    if (success) {
        if (typeof success === 'string') {
            return { type: 'success', title: success, description: '' };
        }
        return { type: 'success', title: success.title || 'Listo', description: success.description || '' };
    }
    if (error) {
        if (typeof error === 'string') {
            return { type: 'error', title: 'Error', description: error };
        }
        return { type: 'error', title: error.title || 'Error', description: error.description || '' };
    }
    return null;
});

watch(
    () => ({ ...rawFlash.value }),
    (val) => {
        if (val?.success || val?.error) {
            visible.value = true;
            setTimeout(() => (visible.value = false), 3800);
        }
    },
    { deep: true },
);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 180ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
