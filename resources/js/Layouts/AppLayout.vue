<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 flex">
        <GlobalLoader />

        <!-- ── Desktop sidebar ── -->
        <aside class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 w-60 border-r border-gray-800/80 bg-gray-900">
            <!-- Brand -->
            <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-800/80">
                <img :src="logoUrl" alt="Logo" class="h-9 w-9 rounded-xl object-cover ring-1 ring-black/40 shrink-0" />
                <div class="min-w-0">
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 font-medium">Panel admin</p>
                    <p class="text-sm font-bold text-gray-100 leading-tight truncate">{{ appName }}</p>
                </div>
            </div>

            <!-- Nav (scrollable) -->
            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-4">
                <!-- Inicio -->
                <div>
                    <Link
                        :href="homeRoute"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-all duration-150 hover:bg-gray-800 hover:text-amber-200"
                        :class="sidebarClass('home')"
                    >
                        <i class="fa-solid fa-house w-4 text-center text-base"></i>
                        <span>Inicio</span>
                    </Link>
                </div>

                <!-- Operaciones -->
                <div>
                    <p class="mb-1 px-3 text-[10px] uppercase tracking-widest text-gray-600 font-semibold">Operaciones</p>
                    <div class="space-y-0.5">
                        <template v-for="item in navGroups.operaciones" :key="item.key">
                            <NavItem :item="item" :active="activeKey === item.key" />
                        </template>
                    </div>
                </div>

                <!-- Inventario -->
                <div>
                    <p class="mb-1 px-3 text-[10px] uppercase tracking-widest text-gray-600 font-semibold">Inventario</p>
                    <div class="space-y-0.5">
                        <template v-for="item in navGroups.inventario" :key="item.key">
                            <NavItem :item="item" :active="activeKey === item.key" />
                        </template>
                    </div>
                </div>

                <!-- Finanzas -->
                <div>
                    <p class="mb-1 px-3 text-[10px] uppercase tracking-widest text-gray-600 font-semibold">Finanzas</p>
                    <div class="space-y-0.5">
                        <template v-for="item in navGroups.finanzas" :key="item.key">
                            <NavItem :item="item" :active="activeKey === item.key" />
                        </template>
                    </div>
                </div>

                <!-- Sistema -->
                <div>
                    <p class="mb-1 px-3 text-[10px] uppercase tracking-widest text-gray-600 font-semibold">Sistema</p>
                    <div class="space-y-0.5">
                        <template v-for="item in navGroups.sistema" :key="item.key">
                            <NavItem :item="item" :active="activeKey === item.key" />
                        </template>
                    </div>
                </div>
            </nav>

            <!-- Footer: user + actions -->
            <div class="border-t border-gray-800/80 px-3 py-3 space-y-1">
                <!-- User info -->
                <div class="flex items-center gap-2.5 rounded-xl px-2 py-2 mb-1">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-xs font-bold text-amber-300 ring-1 ring-amber-500/30">
                        {{ userInitials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-gray-200">{{ userName }}</p>
                        <p class="truncate text-[10px] text-gray-500">{{ userRoleLabel }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-400 transition-all duration-150 hover:bg-gray-800 hover:text-amber-200"
                    @click="toggleTheme"
                >
                    <i :class="`fa-solid ${theme === 'dark' ? 'fa-sun' : 'fa-moon'} w-4 text-center`"></i>
                    <span>{{ theme === 'dark' ? 'Modo claro' : 'Modo oscuro' }}</span>
                </button>
                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-400 transition-all duration-150 hover:bg-red-500/10 hover:text-red-300"
                    @click="logout"
                >
                    <i class="fa-solid fa-power-off w-4 text-center"></i>
                    <span>Cerrar sesión</span>
                </button>
            </div>
        </aside>

        <!-- ── Main area ── -->
        <div class="flex flex-1 flex-col min-w-0 lg:ml-60">
            <!-- Flash banner -->
            <div class="mx-auto w-full max-w-5xl px-4 pt-3">
                <FlashBanner />
            </div>

            <!-- Mobile header -->
            <header class="lg:hidden border-b border-gray-800 bg-gray-900/80 backdrop-blur fade-in-soft">
                <div class="flex items-center justify-between px-4 py-3.5">
                    <div class="flex items-center gap-3">
                        <img :src="logoUrl" alt="Logo" class="h-8 w-8 rounded-xl object-cover ring-1 ring-black/30" />
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-gray-500">{{ appName }}</p>
                            <h1 class="text-base font-semibold text-gray-50 leading-tight">{{ title }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <slot name="actions" />
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-700/80 text-gray-300 transition-all hover:border-amber-400/60 hover:bg-amber-500/10 hover:text-amber-200"
                            @click="toggleTheme"
                        >
                            <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                        </button>
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-700/80 text-gray-300 transition-all hover:border-red-400/60 hover:bg-red-500/10 hover:text-red-300"
                            @click="logout"
                        >
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Desktop title bar -->
            <div class="hidden lg:flex items-center justify-between px-8 py-4 border-b border-gray-800/60 fade-in-soft">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-600 font-medium">{{ sectionLabel }}</p>
                    <h1 class="text-xl font-bold text-gray-50 leading-tight">{{ title }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <slot name="actions" />
                    <!-- User badge -->
                    <div class="flex items-center gap-2 rounded-xl border border-gray-700/60 bg-gray-800/50 px-3 py-1.5">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500/20 text-[10px] font-bold text-amber-300">
                            {{ userInitials }}
                        </div>
                        <div class="leading-tight">
                            <p class="text-xs font-semibold text-gray-200">{{ userName }}</p>
                            <p class="text-[10px] text-gray-500">{{ userRoleLabel }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1 mx-auto w-full max-w-5xl px-4 py-6 pb-28 lg:pb-8 fade-in-soft">
                <slot />
            </main>
        </div>

        <!-- Mobile bottom nav -->
        <BottomNav />
    </div>
</template>

<script setup>
import { computed, defineComponent, h } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import BottomNav from '../Components/BottomNav.vue';
import GlobalLoader from '../Components/GlobalLoader.vue';
import FlashBanner from '../Components/FlashBanner.vue';
import { useTheme } from '../composables/useTheme';

defineProps({
    title: { type: String, default: '' },
});

// ── Internal NavItem component ───────────────────────────────────────────────
const NavItem = defineComponent({
    props: { item: Object, active: Boolean },
    setup(props) {
        return () => props.item.enabled
            ? h(Link, {
                href: props.item.href,
                class: [
                    'flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-all duration-150 hover:bg-gray-800 hover:text-amber-200',
                    props.active
                        ? 'bg-amber-500/10 text-amber-200 font-semibold'
                        : 'text-gray-400',
                ],
            }, () => [
                h('i', { class: `fa-solid ${props.item.icon} w-4 text-center text-[13px]` }),
                h('span', props.item.label),
            ])
            : h('span', {
                class: 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-700 cursor-not-allowed select-none',
                title: `Sin permiso: ${props.item.label}`,
            }, [
                h('i', { class: `fa-solid ${props.item.icon} w-4 text-center text-[13px]` }),
                h('span', props.item.label),
            ]);
    },
});

// ── State ────────────────────────────────────────────────────────────────────
const logoutForm = useForm({});
const { theme, toggleTheme } = useTheme();
const page        = usePage();
const appSettings = computed(() => page.props.app?.settings || {});
const logoUrl     = computed(() => appSettings.value.logo_url || '/logos/iconofm-32.png');
const appName     = computed(() => appSettings.value.name || 'SellPOS');

const user          = computed(() => page.props.auth?.user || null);
const homeRoute     = computed(() => user.value?.home || '/admin');
const permissions   = computed(() => user.value?.permissions || []);
const userName      = computed(() => user.value?.name || '');
const userRoleLabel = computed(() => user.value?.role_label || user.value?.role || '');
const userInitials  = computed(() => {
    const name = userName.value.trim();
    if (!name) return '?';
    const parts = name.split(' ').filter(Boolean);
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.slice(0, 2).toUpperCase();
});

const can = (permission) =>
    permissions.value.includes('*') || permissions.value.includes(permission);

// ── Nav groups ───────────────────────────────────────────────────────────────
const navGroups = computed(() => ({
    operaciones: [
        { key: 'pos',    label: 'Punto de Venta', icon: 'fa-cash-register', href: '/pos',           enabled: can('pos.view') },
        { key: 'sales',  label: 'Ventas',          icon: 'fa-receipt',       href: '/admin/sales',   enabled: can('sales.view') },
        { key: 'cash',   label: 'Caja',            icon: 'fa-money-bill-wave', href: '/admin/cash',  enabled: can('cash.view') },
    ],
    inventario: [
        { key: 'products',   label: 'Inventario',   icon: 'fa-box-open',      href: '/admin/products',   enabled: can('products.view') },
        { key: 'purchases',  label: 'Compras',       icon: 'fa-cart-shopping', href: '/admin/purchases',  enabled: can('purchases.view') },
        { key: 'suppliers',  label: 'Proveedores',   icon: 'fa-truck',         href: '/admin/suppliers',  enabled: can('suppliers.view') },
        { key: 'clients',    label: 'Clientes',      icon: 'fa-users',         href: '/admin/clients',    enabled: can('clients.view') },
        { key: 'categories', label: 'Categorías',    icon: 'fa-tags',          href: '/admin/categories', enabled: can('categories.view') },
    ],
    finanzas: [
        { key: 'expenses', label: 'Gastos',   icon: 'fa-file-invoice', href: '/admin/expenses', enabled: can('expenses.view') },
        { key: 'banks',    label: 'Bancos',   icon: 'fa-landmark',     href: '/admin/banks',    enabled: can('banks.view') },
        { key: 'reports',  label: 'Reportes', icon: 'fa-chart-bar',    href: '/admin/reports',  enabled: can('reports.view') },
    ],
    sistema: [
        { key: 'users',    label: 'Usuarios',       icon: 'fa-users-gear', href: '/admin/users',    enabled: can('users.view') },
        { key: 'settings', label: 'Configuración',  icon: 'fa-sliders',    href: '/admin/settings', enabled: can('settings.view') },
    ],
}));

// ── Active key & section label ────────────────────────────────────────────────
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

const sectionLabel = computed(() => {
    const key = activeKey.value;
    if (['pos', 'sales', 'cash'].includes(key)) return 'Operaciones';
    if (['products', 'purchases', 'suppliers', 'clients', 'categories'].includes(key)) return 'Inventario';
    if (['expenses', 'banks', 'reports'].includes(key)) return 'Finanzas';
    if (['users', 'settings'].includes(key)) return 'Sistema';
    return appName.value;
});

const sidebarClass = (key) => ({
    'bg-amber-500/10 text-amber-200 font-semibold': activeKey.value === key,
    'text-gray-400': activeKey.value !== key,
});

const logout = () => logoutForm.post('/logout');
</script>
