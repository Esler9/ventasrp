<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <GlobalLoader />
        <div class="mx-auto w-full max-w-7xl px-4 pt-3 sm:px-5 lg:px-6">
            <FlashBanner />
        </div>

        <main class="mx-auto w-full max-w-7xl px-4 pb-24 pt-2 sm:px-5 md:pb-28 lg:px-6 lg:pb-8" :style="contentSafeArea">
            <section class="rounded-2xl dashboard-hero border border-gray-800 p-4 shadow-2xl shadow-black/20 sm:p-5 lg:p-6">
                <header class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-gray-800 pb-4 lg:mb-6 lg:pb-5">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-sky-500/20 text-sky-300 lg:h-12 lg:w-12">
                            <i class="fa-solid fa-store text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-400">Sucursal</p>
                            <p class="text-xl font-bold leading-5 text-gray-100 lg:text-2xl lg:leading-6">
                                {{ branchName }}
                                <i class="fa-solid fa-angle-down ml-1 text-xs text-gray-500"></i>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="relative grid h-10 w-10 place-items-center rounded-full bg-gray-900/70 text-gray-300" title="Notificaciones">
                        <i class="fa-regular fa-bell"></i>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500"></span>
                    </button>
                </header>

                <section>
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.13em] text-gray-400">Resumen financiero</h2>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 xl:gap-4">
                        <article class="rounded-xl border border-sky-400/30 bg-gradient-to-br from-sky-700 to-sky-500 p-3 lg:p-4">
                            <p class="text-xs text-sky-100/90">Ventas hoy</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none lg:text-[2rem]">{{ currencySymbol }}{{ money(financialSummary.sales_today) }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-gray-950/30 px-2 py-0.5 text-[11px] font-semibold">
                                {{ trend(financialSummary.sales_today_trend) }}
                            </span>
                        </article>

                        <article class="rounded-xl border border-gray-700 bg-gray-850 p-3 lg:p-4">
                            <p class="text-xs text-gray-400">Utilidad hoy</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none text-sky-300 lg:text-[2rem]">{{ currencySymbol }}{{ money(financialSummary.utility_today) }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-semibold text-emerald-300">
                                {{ trend(financialSummary.utility_today_trend) }}
                            </span>
                        </article>

                        <article class="rounded-xl border border-gray-700 bg-gray-850 p-3 lg:p-4">
                            <p class="text-xs text-gray-400">Ventas del mes</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none lg:text-[2rem]">{{ currencySymbol }}{{ money(financialSummary.sales_month) }}</p>
                            <div class="mt-3">
                                <div class="h-1.5 overflow-hidden rounded-full bg-gray-800">
                                    <div class="h-full rounded-full bg-sky-400" :style="{ width: `${financialSummary.sales_month_progress}%` }"></div>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-500">Meta: {{ currencySymbol }}{{ money(financialSummary.sales_month_goal) }}</p>
                            </div>
                        </article>

                        <article class="rounded-xl border border-gray-700 bg-gray-850 p-3 lg:p-4">
                            <p class="text-xs text-gray-400">Saldo en Bancos</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none lg:text-[2rem]">{{ currencySymbol }}{{ money(financialSummary.bank_balance) }}</p>
                            <p class="mt-2 text-[11px] text-gray-400">
                                <i class="fa-solid fa-building-columns mr-1 text-[10px]"></i>{{ financialSummary.bank_balance_label }}
                            </p>
                        </article>
                    </div>
                </section>

                <div class="mt-6 grid gap-5 md:gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(21rem,24rem)] xl:items-start">
                    <section>
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.13em] text-gray-400">Acciones rápidas</h2>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                            <Link
                                v-for="action in quickActions"
                                :key="action.key"
                                :href="action.href"
                                class="flex items-center gap-3 rounded-xl border border-gray-700 bg-gray-850 px-3 py-3 transition-colors hover:bg-gray-800 lg:px-4"
                            >
                                <div class="grid h-9 w-9 place-items-center rounded-lg" :class="action.icon_bg">
                                    <i :class="action.icon"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[15px] font-semibold leading-tight text-gray-100">{{ action.title }}</p>
                                    <p class="truncate text-xs text-gray-400">{{ action.subtitle }}</p>
                                </div>
                                <i class="fa-solid fa-chevron-right text-xs text-gray-500"></i>
                            </Link>
                        </div>
                    </section>

                    <section class="xl:sticky xl:top-24">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.13em] text-gray-400">Actividad reciente</h2>
                            <Link href="/admin/sales" class="text-xs font-semibold text-sky-400">Ver todo</Link>
                        </div>
                        <div class="rounded-xl border border-gray-700 bg-gray-850 px-3 xl:max-h-[28rem] xl:overflow-y-auto">
                            <article
                                v-for="(activity, index) in recentActivity"
                                :key="activity.id"
                                class="flex items-center gap-3 py-3"
                                :class="{ 'border-b border-gray-700': index < recentActivity.length - 1 }"
                            >
                                <div class="grid h-8 w-8 place-items-center rounded-full bg-gray-800 text-gray-300">
                                    <i class="fa-solid fa-receipt text-xs"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-100">{{ activity.title }}</p>
                                    <p class="truncate text-xs text-gray-400">{{ relativeTime(activity.created_at) }} · {{ activity.subtitle }}</p>
                                </div>
                                <p v-if="activity.amount !== null" class="text-sm font-semibold text-emerald-400">
                                    {{ activity.amount_prefix }}{{ currencySymbol }}{{ money(activity.amount) }}
                                </p>
                                <p v-else class="text-sm font-semibold text-gray-500">-</p>
                            </article>
                            <p v-if="!recentActivity.length" class="py-4 text-center text-xs text-gray-500">
                                Aún no hay actividad reciente para mostrar.
                            </p>
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <Link
            href="/pos"
            class="fixed bottom-28 right-4 z-40 grid h-12 w-12 place-items-center rounded-full bg-sky-500 text-white shadow-lg shadow-sky-700/40 transition hover:bg-sky-400 sm:right-6 lg:bottom-8 lg:right-8"
        >
            <i class="fa-solid fa-plus text-lg"></i>
        </Link>

        <BottomNav />
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import BottomNav from '../../Components/BottomNav.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import GlobalLoader from '../../Components/GlobalLoader.vue';

const props = defineProps({
    branch_name: {
        type: String,
        default: 'Sucursal Principal',
    },
    financial_summary: {
        type: Object,
        default: () => ({
            sales_today: 0,
            sales_today_trend: 0,
            utility_today: 0,
            utility_today_trend: 0,
            sales_month: 0,
            sales_month_goal: 0,
            sales_month_progress: 0,
            bank_balance: 0,
            bank_balance_label: 'Disponible',
        }),
    },
    quick_actions: {
        type: Array,
        default: () => [],
    },
    recent_activity: {
        type: Array,
        default: () => [],
    },
    currency: {
        type: Object,
        default: () => ({
            code: 'GTQ',
            symbol: 'Q',
        }),
    },
});

const branchName = computed(() => props.branch_name || 'Sucursal Principal');
const financialSummary = computed(() => props.financial_summary);
const quickActions = computed(() => props.quick_actions);
const recentActivity = computed(() => props.recent_activity);
const currencySymbol = computed(() => props.currency?.symbol || 'Q');
const isDesktop = ref(false);

const money = (value) => {
    const number = Number(value || 0);
    return number.toLocaleString('en-US', {
        minimumFractionDigits: number % 1 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    });
};

const trend = (value) => {
    const number = Number(value || 0);
    if (number > 0) {
        return `+${number}%`;
    }

    if (number < 0) {
        return `${number}%`;
    }

    return '0%';
};

const relativeTime = (isoDate) => {
    if (!isoDate) {
        return 'Hace un momento';
    }

    const timestamp = new Date(isoDate).getTime();
    const diffMs = Date.now() - timestamp;
    const diffMinutes = Math.max(1, Math.round(diffMs / 60000));

    if (diffMinutes < 60) {
        return `Hace ${diffMinutes} min`;
    }

    const diffHours = Math.round(diffMinutes / 60);
    if (diffHours < 24) {
        return `Hace ${diffHours} h`;
    }

    const diffDays = Math.round(diffHours / 24);
    return `Hace ${diffDays} d`;
};

const syncViewport = () => {
    isDesktop.value = window.innerWidth >= 1024;
};

onMounted(() => {
    syncViewport();
    window.addEventListener('resize', syncViewport);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncViewport);
});

const contentSafeArea = computed(() => ({
    paddingBottom: isDesktop.value
        ? 'calc(1.5rem + env(safe-area-inset-bottom, 0px))'
        : 'calc(7rem + env(safe-area-inset-bottom, 0px))',
}));
</script>
