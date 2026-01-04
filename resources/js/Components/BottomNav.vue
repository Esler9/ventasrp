<template>
    <nav class="fixed inset-x-0 bottom-0 z-40 bg-gray-900/95 backdrop-blur border-t border-gray-800" :style="safeAreaStyle">
        <div class="mx-auto flex max-w-6xl items-center justify-around px-4 pt-3 pb-4 text-xs text-gray-300">
            <Link
                :href="routes.pos"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('pos')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M5 7l1 12h12l1-12M9 11v2m6-2v2" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
                </svg>
                <span>POS</span>
            </Link>
            <Link
                :href="routes.products"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('products')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8-4 8 4m-14 0v10l6 3m0-13 6-3v10l-6 3m0-13v13" />
                </svg>
                <span>Productos</span>
            </Link>
            <Link
                :href="routes.sales"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('sales')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v4H4V7Zm0 6h16v4H4v-4Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 9h.01M7 15h.01m4-6h6M11 15h6" />
                </svg>
                <span>Ventas</span>
            </Link>
            <Link
                :href="routes.panel"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('panel')"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m-8-8h16" />
                </svg>
                <span>Panel</span>
            </Link>
        </div>
    </nav>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const routes = {
    pos: '/pos',
    products: '/admin/products',
    sales: '/admin/sales',
    panel: '/admin',
};

const isActive = (key) => {
    const path = page.url || '';
    const active =
        (key === 'pos' && path.startsWith('/pos')) ||
        (key === 'products' && path.startsWith('/admin/products')) ||
        (key === 'sales' && path.startsWith('/admin/sales')) ||
        (key === 'panel' && path === '/admin');

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
