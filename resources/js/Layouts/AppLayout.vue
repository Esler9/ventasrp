<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 flex">
        <GlobalLoader />

        <!-- ── Desktop sidebar ── -->
        <aside class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 w-56 border-r border-gray-800 bg-gray-900">
            <!-- Brand -->
            <div class="flex items-center gap-3 px-4 py-5 border-b border-gray-800">
                <img :src="logoUrl" alt="Logo" class="h-9 w-9 rounded-xl object-cover ring-1 ring-black/30 shrink-0" />
                <div>
                    <p class="text-xs uppercase tracking-widest text-gray-500">Panel</p>
                    <p class="text-sm font-semibold text-gray-100 leading-tight">{{ appName }}</p>
                </div>
            </div>

            <!-- Nav items (scrollable) -->
            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
                <Link
                    :href="homeRoute"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-all duration-150 hover:bg-gray-800 hover:text-amber-200"
                    :class="sidebarClass('home')"
                >
                    <i class="fa-solid fa-house w-4 text-center"></i>
                    <span>Inicio</span>
                </Link>

                <template v-for="item in sidebarItems" :key="item.key">
                    <Link
                        v-if="item.enabled"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-all duration-150 hover:bg-gray-800 hover:text-amber-200"
                        :class="sidebarClass(item.key)"
                    >
                        <i :class="`fa-solid ${item.icon} w-4 text-center`"></i>
                        <span>{{ item.label }}</span>
                    </Link>
                    <span
                        v-else
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-600 cursor-not-allowed select-none"
                        :title="`Sin permiso: ${item.label}`"
                    >
                        <i :class="`fa-solid ${item.icon} w-4 text-center`"></i>
                        <span>{{ item.label }}</span>
                    </span>
                </template>
            </nav>

            <!-- Footer actions -->
            <div class="px-3 py-4 border-t border-gray-800 space-y-1">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-400 transition-all duration-150 hover:bg-gray-800 hover:text-amber-200"
                    @click="toggleTheme"
                >
                    <i :class="`fa-solid ${theme === 'dark' ? 'fa-sun' : 'fa-moon'} w-4 text-center`"></i>
                    <span>{{ theme === 'dark' ? 'Modo claro' : 'Modo oscuro' }}</span>
                </button>
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-gray-400 transition-all duration-150 hover:bg-red-500/10 hover:text-red-300"
                    @click="logout"
                >
                    <i class="fa-solid fa-power-off w-4 text-center"></i>
                    <span>Cerrar sesión</span>
                </button>
            </div>
        </aside>

        <!-- ── Main area ── -->
        <div class="flex flex-1 flex-col min-w-0 lg:ml-56">
            <!-- Flash banner -->
            <div class="mx-auto w-full max-w-5xl px-4 pt-3">
                <FlashBanner />
            </div>

            <!-- Mobile header (hidden on desktop) -->
            <header class="lg:hidden border-b border-gray-800 bg-gray-900/80 backdrop-blur fade-in-soft">
                <div class="flex items-center justify-between px-4 py-4">
                    <div class="flex items-center gap-3">
                        <img :src="logoUrl" alt="Logo" class="h-9 w-9 rounded-xl object-cover ring-1 ring-black/30" />
                        <div class="space-y-0.5">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Panel</p>
                            <h1 class="text-xl font-semibold text-gray-50">{{ title }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <slot name="actions" />
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-700/80 text-gray-200 transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-400/80 hover:bg-amber-500/10 hover:text-amber-200"
                            @click="toggleTheme"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun text-lg' : 'fa-solid fa-moon text-lg'"></i>
                        </button>
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-700/80 text-gray-200 transition-all duration-200 hover:-translate-y-0.5 hover:border-red-400/80 hover:bg-red-500/15 hover:text-red-200"
                            @click="logout"
                        >
                            <i class="fa-solid fa-power-off text-lg"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Desktop page title bar -->
            <div class="hidden lg:flex items-center justify-between px-8 py-5 border-b border-gray-800/60 fade-in-soft">
                <h1 class="text-2xl font-semibold text-gray-50">{{ title }}</h1>
                <div class="flex items-center gap-3">
                    <slot name="actions" />
                </div>
            </div>

            <main class="flex-1 mx-auto w-full max-w-5xl px-4 py-6 pb-28 lg:pb-8 fade-in-soft">
                <slot />
            </main>
        </div>

        <!-- Mobile bottom nav (hidden on desktop via internal lg:hidden) -->
        <BottomNav />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import BottomNav from '../Components/BottomNav.vue';
import GlobalLoader from '../Components/GlobalLoader.vue';
import FlashBanner from '../Components/FlashBanner.vue';
import { useTheme } from '../composables/useTheme';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const logoutForm = useForm({});
const { theme, toggleTheme } = useTheme();
const page        = usePage();
const appSettings = computed(() => page.props.app?.settings || {});
const logoUrl     = computed(() => appSettings.value.logo_url || '/logos/iconofm-32.png');
const appName     = computed(() => appSettings.value.name || 'SellPOS');

const user        = computed(() => page.props.auth?.user || null);
const homeRoute   = computed(() => user.value?.home || '/admin');
const permissions = computed(() => user.value?.permissions || []);
const can = (permission) =>
    permissions.value.includes('*') || permissions.value.includes(permission);

const sidebarItems = computed(() => [
    { key: 'pos',        label: 'Punto de Venta',  icon: 'fa-cash-register',    href: '/pos',               enabled: can('pos.view') },
    { key: 'sales',      label: 'Ventas',           icon: 'fa-receipt',          href: '/admin/sales',       enabled: can('sales.view') },
    { key: 'products',   label: 'Inventario',       icon: 'fa-box-open',         href: '/admin/products',    enabled: can('products.view') },
    { key: 'purchases',  label: 'Compras',          icon: 'fa-cart-shopping',    href: '/admin/purchases',   enabled: can('purchases.view') },
    { key: 'suppliers',  label: 'Proveedores',      icon: 'fa-truck',            href: '/admin/suppliers',   enabled: can('suppliers.view') },
    { key: 'clients',    label: 'Clientes',         icon: 'fa-users',            href: '/admin/clients',     enabled: can('clients.view') },
    { key: 'categories', label: 'Categorías',       icon: 'fa-tags',             href: '/admin/categories',  enabled: can('categories.view') },
    { key: 'cash',       label: 'Caja',             icon: 'fa-money-bill-wave',  href: '/admin/cash',        enabled: can('cash.view') },
    { key: 'expenses',   label: 'Gastos',           icon: 'fa-file-invoice',     href: '/admin/expenses',    enabled: can('expenses.view') },
    { key: 'banks',      label: 'Bancos',           icon: 'fa-landmark',         href: '/admin/banks',       enabled: can('banks.view') },
    { key: 'reports',    label: 'Reportes',         icon: 'fa-chart-bar',        href: '/admin/reports',     enabled: can('reports.view') },
    { key: 'users',      label: 'Usuarios',         icon: 'fa-users-gear',       href: '/admin/users',       enabled: can('users.view') },
    { key: 'settings',   label: 'Configuración',    icon: 'fa-sliders',          href: '/admin/settings',    enabled: can('settings.view') },
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

const sidebarClass = (key) => ({
    'bg-amber-500/10 text-amber-200 font-semibold': activeKey.value === key,
    'text-gray-400': activeKey.value !== key,
});

const logout = () => logoutForm.post('/logout');
</script>
