<template>
    <transition name="banner-pop" appear>
        <div
            v-if="visible && message"
            class="relative mb-4 flex items-start gap-3 overflow-hidden rounded-xl border px-4 py-3 shadow-lg"
            :class="message.type === 'error' ? 'border-red-400/40 bg-red-900/30 text-red-50' : 'border-emerald-300/40 bg-emerald-900/20 text-emerald-50'"
        >
            <div
                v-if="message.type === 'success'"
                class="pointer-events-none absolute inset-0 opacity-70"
                :class="isCashOpenSuccess ? 'banner-glow' : ''"
            ></div>

            <div class="mt-0.5">
                <svg v-if="message.type === 'error'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01m-6.938 0h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L4.34 14c-.77 1.333.192 3 1.732 3Z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-200 success-check" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="relative z-10 flex-1 space-y-1">
                <p class="text-sm font-semibold">{{ message.title }}</p>
                <p v-if="message.description" class="text-xs opacity-90">{{ message.description }}</p>
                <p v-if="isCashOpenSuccess" class="text-[11px] font-semibold uppercase tracking-wide text-emerald-200/90">Caja lista para vender</p>
                <slot />
            </div>
            <button type="button" class="relative z-10 text-xs text-gray-200 hover:text-white" @click="visible = false">Cerrar</button>
            <div v-if="message.type === 'success'" :key="progressKey" class="progress-line"></div>
        </div>
    </transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const progressKey = ref(0);

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

const isCashOpenSuccess = computed(() => {
    if (!message.value || message.value.type !== 'success') return false;
    const title = String(message.value.title || '').toLowerCase();
    return title.includes('caja abierta');
});

watch(
    () => ({ ...rawFlash.value }),
    (val) => {
        if (val?.success || val?.error) {
            visible.value = true;
            progressKey.value += 1;
            setTimeout(() => (visible.value = false), 3800);
        }
    },
    { deep: true },
);
</script>

<style scoped>
.banner-pop-enter-active,
.banner-pop-leave-active {
    transition: opacity 240ms ease, transform 240ms ease;
}
.banner-pop-enter-from,
.banner-pop-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.985);
}

.success-check {
    animation: check-pop 460ms ease;
}

.progress-line {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, rgba(16, 185, 129, 0.9), rgba(110, 231, 183, 0.9));
    transform-origin: left;
    animation: dismiss-progress 3.8s linear forwards;
}

.banner-glow {
    background: radial-gradient(circle at 15% 20%, rgba(16, 185, 129, 0.2), transparent 40%),
        radial-gradient(circle at 85% 0%, rgba(52, 211, 153, 0.18), transparent 35%);
}

@keyframes dismiss-progress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

@keyframes check-pop {
    0% {
        transform: scale(0.65);
        opacity: 0.5;
    }
    65% {
        transform: scale(1.12);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
