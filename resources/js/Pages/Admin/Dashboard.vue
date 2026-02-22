<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <GlobalLoader />
        <div class="mx-auto w-full max-w-md px-4 pt-3">
            <FlashBanner />
        </div>

        <main class="mx-auto w-full max-w-md px-4 pb-28 pt-2" :style="contentSafeArea">
            <section class="rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-950 via-[#0a1a2d] to-[#081626] p-4 shadow-2xl shadow-black/30">
                <header class="mb-5 flex items-center justify-between border-b border-slate-800/70 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-sky-500/20 text-sky-300">
                            <i class="fa-solid fa-store text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">Sucursal</p>
                            <p class="text-xl font-bold leading-5 text-slate-100">
                                {{ branchName }}
                                <i class="fa-solid fa-angle-down ml-1 text-xs text-slate-500"></i>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="relative grid h-10 w-10 place-items-center rounded-full bg-slate-900/70 text-slate-300" title="Notificaciones">
                        <i class="fa-regular fa-bell"></i>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500"></span>
                    </button>
                </header>

                <section>
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.13em] text-slate-400">Resumen financiero</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <article class="rounded-xl border border-sky-400/30 bg-gradient-to-br from-sky-700 to-sky-500 p-3">
                            <p class="text-xs text-sky-100/90">Ventas hoy</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none">{{ currencySymbol }}{{ money(financialSummary.sales_today) }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-slate-950/30 px-2 py-0.5 text-[11px] font-semibold">
                                {{ trend(financialSummary.sales_today_trend) }}
                            </span>
                        </article>

                        <article class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                            <p class="text-xs text-slate-400">Utilidad hoy</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none text-sky-300">{{ currencySymbol }}{{ money(financialSummary.utility_today) }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-emerald-500/20 px-2 py-0.5 text-[11px] font-semibold text-emerald-300">
                                {{ trend(financialSummary.utility_today_trend) }}
                            </span>
                        </article>

                        <article class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                            <p class="text-xs text-slate-400">Ventas del mes</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none">{{ currencySymbol }}{{ money(financialSummary.sales_month) }}</p>
                            <div class="mt-3">
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-700">
                                    <div class="h-full rounded-full bg-sky-400" :style="{ width: `${financialSummary.sales_month_progress}%` }"></div>
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">Meta: {{ currencySymbol }}{{ money(financialSummary.sales_month_goal) }}</p>
                            </div>
                        </article>

                        <article class="rounded-xl border border-slate-700 bg-slate-800/70 p-3">
                            <p class="text-xs text-slate-400">Saldo en Bancos</p>
                            <p class="mt-1 text-3xl font-extrabold leading-none">{{ currencySymbol }}{{ money(financialSummary.bank_balance) }}</p>
                            <p class="mt-2 text-[11px] text-slate-400">
                                <i class="fa-solid fa-building-columns mr-1 text-[10px]"></i>{{ financialSummary.bank_balance_label }}
                            </p>
                        </article>
                    </div>
                </section>

                <section class="mt-6">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.13em] text-slate-400">Acciones rápidas</h2>
                    <div class="space-y-2">
                        <Link
                            v-for="action in quickActions"
                            :key="action.key"
                            :href="action.href"
                            class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-800/70 px-3 py-3 transition-colors hover:bg-slate-700/70"
                        >
                            <div class="grid h-9 w-9 place-items-center rounded-lg" :class="action.icon_bg">
                                <i :class="action.icon"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-semibold leading-tight text-slate-100">{{ action.title }}</p>
                                <p class="truncate text-xs text-slate-400">{{ action.subtitle }}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-slate-500"></i>
                        </Link>
                    </div>
                </section>

                <section class="mt-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.13em] text-slate-400">Actividad reciente</h2>
                        <Link href="/admin/sales" class="text-xs font-semibold text-sky-400">Ver todo</Link>
                    </div>
                    <div class="rounded-xl border border-slate-700 bg-slate-800/70 px-3">
                        <article
                            v-for="(activity, index) in recentActivity"
                            :key="activity.id"
                            class="flex items-center gap-3 py-3"
                            :class="{ 'border-b border-slate-700/80': index < recentActivity.length - 1 }"
                        >
                            <div class="grid h-8 w-8 place-items-center rounded-full bg-slate-700 text-slate-300">
                                <i class="fa-solid fa-receipt text-xs"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-100">{{ activity.title }}</p>
                                <p class="truncate text-xs text-slate-400">{{ relativeTime(activity.created_at) }} · {{ activity.subtitle }}</p>
                            </div>
                            <p v-if="activity.amount !== null" class="text-sm font-semibold text-emerald-400">
                                {{ activity.amount_prefix }}{{ currencySymbol }}{{ money(activity.amount) }}
                            </p>
                            <p v-else class="text-sm font-semibold text-slate-500">-</p>
                        </article>
                        <p v-if="!recentActivity.length" class="py-4 text-center text-xs text-slate-500">
                            Aún no hay actividad reciente para mostrar.
                        </p>
                    </div>
                </section>
            </section>
        </main>

        <Link
            href="/pos"
            class="fixed bottom-28 right-6 z-40 grid h-12 w-12 place-items-center rounded-full bg-sky-500 text-white shadow-lg shadow-sky-700/40 transition hover:bg-sky-400"
        >
            <i class="fa-solid fa-plus text-lg"></i>
        </Link>

        <BottomNav />
    </div>
</template>

<script setup>
import { computed } from 'vue';
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

const contentSafeArea = computed(() => ({
    paddingBottom: 'calc(8rem + env(safe-area-inset-bottom, 0px))',
}));
</script>
