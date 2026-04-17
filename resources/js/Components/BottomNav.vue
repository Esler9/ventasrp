<template>
    <div class="lg:hidden">
        <!-- Bottom bar -->
        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-800 bg-gray-900/95 backdrop-blur"
            :style="safeAreaStyle"
        >
            <div class="flex items-center justify-around px-2 pb-4 pt-3">
                <Link
                    :href="homeRoute"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="navClass('home')"
                >
                    <i class="fa-solid fa-house text-base"></i>
                    <span class="text-xs">Inicio</span>
                </Link>

                <Link
                    v-if="can('pos.view')"
                    href="/pos"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="navClass('pos')"
                >
                    <i class="fa-solid fa-cash-register text-base"></i>
                    <span class="text-xs">POS</span>
                </Link>
                <span v-else class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-gray-600">
                    <i class="fa-solid fa-cash-register text-base"></i>
                    <span class="text-xs">POS</span>
                </span>

                <Link
                    v-if="can('products.view')"
                    href="/admin/products"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="navClass('products')"
                >
                    <i class="fa-solid fa-box-open text-base"></i>
                    <span class="text-xs">Inventario</span>
                </Link>
                <span v-else class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 text-gray-600">
                    <i class="fa-solid fa-box-open text-base"></i>
                    <span class="text-xs">Inventario</span>
                </span>

                <button
                    type="button"
                    class="flex flex-col items-center gap-1 rounded-2xl px-3 py-2 transition-all duration-150 hover:text-amber-200 active:scale-95"
                    :class="showMenu ? 'text-amber-200 font-semibold' : 'text-gray-400'"
                    @click="showMenu = !showMenu"
                >
                    <i class="fa-solid fa-grip text-base"></i>
                    <span class="text-xs">Menú</span>
                </button>
            </div>
        </nav>

        <!-- Full-screen menu sheet -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showMenu" class="fixed inset-0 z-50 flex flex-col justify-end">
                    <div class="absolute inset-0 bg-black/60" @click="showMenu = false" />
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-full"
                        enter-to-class="translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0"
                        leave-to-class="translate-y-full"
                    >
                        <div
                            v-if="showMenu"
                            class="relative rounded-t-3xl border-t border-gray-700 bg-gray-900 px-5 pt-5"
                            :style="safeAreaStyle"
                            @click.stop
                        >
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-base font-semibold text-gray-100">Menú</span>
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-800 hover:text-gray-200"
                                    @click="showMenu = false"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-3 pb-4">
                                <template v-for="item in menuItems" :key="item.key">
                                    <Link
                                        v-if="item.enabled"
                                        :href="item.href"
                                        class="flex flex-col items-center gap-2 rounded-2xl border px-2 py-4 text-center transition-all duration-150 active:scale-95"
                                        :class="
                                            isActiveKey(item.key)
                                                ? 'border-amber-500/50 bg-amber-500/10 text-amber-200'
                                                : 'border-gray-700/60 bg-gray-800/60 text-gray-300 hover:border-amber-500/40 hover:bg-amber-500/10 hover:text-amber-200'
                                        "
                                        @click="showMenu = false"
                                    >
                                        <i :class="`fa-solid ${item.icon} text-xl`"></i>
                                        <span class="text-xs font-medium leading-tight">{{ item.label }}</span>
                                    </Link>
                                    <div
                                        v-else
                                        class="flex flex-col items-center gap-2 rounded-2xl border border-gray-800 bg-gray-900/30 px-2 py-4 text-center opacity-35"
                                    >
                                        <i :class="`fa-solid ${item.icon} text-xl text-gray-600`"></i>
                                        <span class="text-xs font-medium leading-tight text-gray-600">{{ item.label }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page     = usePage();
const showMenu = ref(false);

const user        = computed(() => page.props.auth?.user || null);
const homeRoute   = computed(() => user.value?.home || '/admin');
const permissions = computed(() => user.value?.permissions || []);

const can = (permission) =>
    permissions.value.includes('*') || permissions.value.includes(permission);

const menuItems = computed(() => [
    { key: 'sales',      label: 'Ventas',          icon: 'fa-receipt',               href: '/admin/sales',       enabled: can('sales.view') },
    { key: 'purchases',  label: 'Compras',          icon: 'fa-cart-shopping',         href: '/admin/purchases',   enabled: can('purchases.view') },
    { key: 'suppliers',  label: 'Proveedores',      icon: 'fa-truck',                 href: '/admin/suppliers',   enabled: can('suppliers.view') },
    { key: 'clients',    label: 'Clientes',         icon: 'fa-users',                 href: '/admin/clients',     enabled: can('clients.view') },
    { key: 'categories', label: 'Categorías',       icon: 'fa-tags',                  href: '/admin/categories',  enabled: can('categories.view') },
    { key: 'cash',       label: 'Caja',             icon: 'fa-money-bill-wave',       href: '/admin/cash',        enabled: can('cash.view') },
    { key: 'expenses',   label: 'Gastos',           icon: 'fa-file-invoice',          href: '/admin/expenses',    enabled: can('expenses.view') },
    { key: 'banks',      label: 'Bancos',           icon: 'fa-landmark',              href: '/admin/banks',       enabled: can('banks.view') },
    { key: 'reports',    label: 'Reportes',         icon: 'fa-chart-bar',             href: '/admin/reports',     enabled: can('reports.view') },
    { key: 'users',      label: 'Usuarios',         icon: 'fa-users-gear',            href: '/admin/users',       enabled: can('users.view') },
    { key: 'settings',   label: 'Configuración',    icon: 'fa-sliders',               href: '/admin/settings',    enabled: can('settings.view') },
]);

const activeKey = computed(() => {
    const path = page.url || '';
    if (path === '/' || path === '/admin' || path === '/admin/') return 'home';
    if (path.startsWith('/pos'))               return 'pos';
    if (path.startsWith('/admin/sales'))       return 'sales';
    if (path.startsWith('/admin/products'))    return 'products';
    if (path.startsWith('/admin/purchases'))   return 'purchases';
    if (path.startsWith('/admin/suppliers'))   return 'suppliers';
    if (path.startsWith('/admin/clients'))     return 'clients';
    if (path.startsWith('/admin/categories'))  return 'categories';
    if (path.startsWith('/admin/cash'))        return 'cash';
    if (path.startsWith('/admin/expenses'))    return 'expenses';
    if (path.startsWith('/admin/banks'))       return 'banks';
    if (path.startsWith('/admin/reports'))     return 'reports';
    if (path.startsWith('/admin/users'))       return 'users';
    if (path.startsWith('/admin/settings'))    return 'settings';
    return '';
});

const isActiveKey = (key) => activeKey.value === key;

const navClass = (key) => ({
    'text-amber-200 font-semibold': activeKey.value === key,
    'text-gray-400': activeKey.value !== key,
});

const safeAreaStyle = computed(() => ({
    paddingBottom: 'calc(1rem + env(safe-area-inset-bottom, 0px))',
}));
</script>
