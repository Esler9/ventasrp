<template>
    <div>
        <nav class="fixed inset-x-0 bottom-0 z-40 bg-gray-900/95 backdrop-blur border-t border-gray-800" :style="safeAreaStyle">
            <div class="mx-auto flex max-w-6xl items-center justify-around px-4 pt-3 pb-4 text-xs text-gray-300">
                <Link
                    :href="homeRoute"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-sm transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="isActive('home')"
                >
                    <i class="fa-solid fa-house text-base"></i>
                    <span>Inicio</span>
                </Link>

                <Link
                    v-if="can('pos.view')"
                    href="/pos"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-sm transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="isActive('pos')"
                    title="POS"
                >
                    <i class="fa-solid fa-cash-register text-base"></i>
                    <span>POS</span>
                </Link>
                <button
                    v-else
                    type="button"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-sm text-gray-500"
                    title="Sin permiso para POS"
                >
                    <i class="fa-solid fa-cash-register text-base"></i>
                    <span>POS</span>
                </button>

                <Link
                    v-if="can('products.view')"
                    href="/admin/products"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-sm transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="isActive('products')"
                    title="Inventario"
                >
                    <i class="fa-solid fa-box-open text-base"></i>
                    <span>Inventario</span>
                </Link>
                <button
                    v-else
                    type="button"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-sm text-gray-500"
                    title="Sin permiso para inventario"
                >
                    <i class="fa-solid fa-box-open text-base"></i>
                    <span>Inventario</span>
                </button>

                <button
                    type="button"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-sm transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="{ 'text-amber-200 font-semibold': showMore, 'text-gray-400': !showMore }"
                    @click="showMore = !showMore"
                >
                    <i class="fa-solid fa-ellipsis text-base"></i>
                    <span>Más</span>
                </button>
            </div>
        </nav>

        <div v-if="showMore" class="fixed inset-0 z-50 bg-black/60" @click="showMore = false">
            <div
                class="absolute inset-x-0 bottom-0 rounded-t-3xl border border-gray-800 bg-gray-900 p-4 text-gray-100"
                :style="safeAreaStyle"
                @click.stop
            >
                <div class="mb-3 text-sm font-semibold text-gray-200">Más módulos</div>
                <div class="space-y-2">
                    <template v-for="item in moreItems" :key="item.key">
                        <Link
                            v-if="item.enabled && item.href"
                            :href="item.href"
                            class="flex items-center justify-between rounded-xl border border-gray-800 bg-gray-950/60 px-4 py-3 text-sm"
                            @click="showMore = false"
                        >
                            <span>{{ item.label }}</span>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-500"></i>
                        </Link>
                        <button
                            v-else
                            type="button"
                            class="flex w-full items-center justify-between rounded-xl border border-gray-800 bg-gray-950/40 px-4 py-3 text-sm text-gray-500"
                            :title="item.disabledReason"
                        >
                            <span>{{ item.label }}</span>
                            <span class="text-xs">{{ item.disabledTag }}</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const showMore = ref(false);

const user = computed(() => page.props.auth?.user || null);
const homeRoute = computed(() => user.value?.home || '/admin');
const permissions = computed(() => user.value?.permissions || []);

const can = (permission) => permissions.value.includes('*') || permissions.value.includes(permission);

const moreItems = computed(() => [
    {
        key: 'clients',
        label: 'Clientes',
        href: '/admin/clients',
        enabled: can('clients.view'),
        disabledReason: 'Sin permiso para Clientes.',
        disabledTag: 'Sin permiso',
    },
    {
        key: 'categories',
        label: 'Categorías',
        href: '/admin/categories',
        enabled: can('categories.view'),
        disabledReason: 'Sin permiso para Categorías.',
        disabledTag: 'Sin permiso',
    },
    {
        key: 'cash',
        label: 'Caja',
        href: '/admin/cash',
        enabled: can('cash.view'),
        disabledReason: 'Sin permiso para Caja.',
        disabledTag: 'Sin permiso',
    },
    {
        key: 'reports',
        label: 'Reportes',
        href: '/admin/reports',
        enabled: can('reports.view'),
        disabledReason: 'Sin permiso para Reportes.',
        disabledTag: 'Sin permiso',
    },
    {
        key: 'expenses',
        label: 'Gastos',
        href: null,
        enabled: can('expenses.view') && false,
        disabledReason: can('expenses.view') ? 'Disponible en la siguiente fase.' : 'Sin permiso para Gastos.',
        disabledTag: can('expenses.view') ? 'Próximamente' : 'Sin permiso',
    },
    {
        key: 'banks',
        label: 'Bancos',
        href: null,
        enabled: can('banks.view') && false,
        disabledReason: can('banks.view') ? 'Disponible en la siguiente fase.' : 'Sin permiso para Bancos.',
        disabledTag: can('banks.view') ? 'Próximamente' : 'Sin permiso',
    },
    {
        key: 'settings',
        label: 'Configuraciones',
        href: '/admin/settings',
        enabled: can('settings.view'),
        disabledReason: 'Sin permiso para Configuraciones.',
        disabledTag: 'Sin permiso',
    },
]);

const isActive = (key) => {
    const path = page.url || '';
    const active =
        (key === 'home' && (path === '/' || path === '/admin')) ||
        (key === 'pos' && path.startsWith('/pos')) ||
        (key === 'products' && path.startsWith('/admin/products')) ||
        (key === 'clients' && path.startsWith('/admin/clients'));

    return {
        'text-amber-200': active,
        'font-semibold': active,
        'text-gray-400': !active,
    };
};

const safeAreaStyle = computed(() => ({
    paddingBottom: 'calc(1rem + env(safe-area-inset-bottom, 0px))',
}));
</script>
