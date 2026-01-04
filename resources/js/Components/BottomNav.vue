<template>
    <nav class="fixed inset-x-0 bottom-0 z-40 bg-gray-900/95 backdrop-blur border-t border-gray-800" :style="safeAreaStyle">
        <div class="mx-auto flex max-w-6xl items-center justify-around px-4 pt-3 pb-4 text-xs text-gray-300">
            <Link
                :href="routes.pos"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('pos')"
            >
                <i class="fa-solid fa-cash-register text-base"></i>
                <span>POS</span>
            </Link>
            <Link
                :href="routes.products"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('products')"
            >
                <i class="fa-solid fa-box-open text-base"></i>
                <span>Productos</span>
            </Link>
            <Link
                :href="routes.sales"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('sales')"
            >
                <i class="fa-solid fa-receipt text-base"></i>
                <span>Ventas</span>
            </Link>
            <Link
                :href="routes.panel"
                class="flex flex-col items-center gap-1 transition-colors duration-150 hover:text-amber-200"
                :class="isActive('panel')"
            >
                <i class="fa-solid fa-gauge-high text-base"></i>
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
