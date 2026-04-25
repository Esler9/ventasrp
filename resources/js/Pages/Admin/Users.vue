<template>
    <AppLayout title="Usuarios y Permisos">

        <!-- Tabs -->
        <div class="flex gap-1 rounded-xl bg-gray-900/60 p-1 ring-1 ring-black/30 w-fit mb-4">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === tab.key
                    ? 'bg-amber-400 text-black'
                    : 'text-gray-400 hover:text-gray-200'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- ── TAB: USUARIOS ─────────────────────────────────────────────── -->
        <div v-if="activeTab === 'users'" class="space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-50">Equipo ({{ users.length }})</h2>
                    <p class="text-sm text-gray-400">Gestiona accesos, roles y PIN de cada usuario.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300"
                    @click="openCreate"
                >
                    + Nuevo usuario
                </button>
            </div>

            <!-- Users grid -->
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="rounded-2xl border border-gray-800 bg-gray-900/60 p-4 ring-1 ring-black/30 hover-lift fade-in-soft flex flex-col gap-3"
                >
                    <!-- Avatar + name -->
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                            :class="roleColor(user.role).avatar"
                        >
                            {{ initials(user.name) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-50">{{ user.name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ user.email }}</p>
                        </div>
                    </div>

                    <!-- Role badge + meta -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="roleColor(user.role).badge"
                        >
                            {{ user.role_label }}
                        </span>
                        <span v-if="user.has_pin" class="rounded-full bg-gray-800 px-2.5 py-0.5 text-xs text-gray-400">
                            PIN ✓
                        </span>
                    </div>

                    <div class="text-xs text-gray-500 space-y-0.5">
                        <p>Usuario: <span class="text-gray-300">{{ user.username || '—' }}</span></p>
                        <p>Creado: <span class="text-gray-300">{{ user.created_at }}</span></p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-1 border-t border-gray-800 mt-auto">
                        <button
                            type="button"
                            class="text-sm font-semibold text-amber-300 hover:text-amber-200"
                            @click="openEdit(user)"
                        >
                            Editar
                        </button>
                        <button
                            v-if="user.id !== currentUserId"
                            type="button"
                            class="text-sm text-red-400 hover:text-red-300"
                            @click="confirmDelete(user)"
                        >
                            Eliminar
                        </button>
                        <span v-else class="text-xs text-gray-600 italic">cuenta actual</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB: ROLES Y PERMISOS ─────────────────────────────────────── -->
        <div v-if="activeTab === 'permissions'" class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-50">Matriz de permisos</h2>
                    <p class="text-sm text-gray-400">
                        Marca o desmarca permisos para cada rol. El rol Dueño/Gerente tiene acceso total y no se puede modificar.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="rolesDirty" class="text-xs text-amber-300">Hay cambios sin guardar</span>
                    <button
                        type="button"
                        :disabled="!rolesDirty || rolesSaving"
                        class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 disabled:opacity-50"
                        @click="saveAllRoles"
                    >
                        {{ rolesSaving ? 'Guardando…' : 'Guardar cambios' }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl ring-1 ring-black/30">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-900">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 w-48">
                                Permiso
                            </th>
                            <th
                                v-for="role in roles"
                                :key="role.key"
                                class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide"
                                :class="roleColor(role.key).text"
                            >
                                {{ role.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="group in permissionGroups" :key="group.module">
                            <!-- Group header -->
                            <tr class="bg-gray-900/40 border-t border-gray-800">
                                <td
                                    :colspan="roles.length + 1"
                                    class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-400"
                                >
                                    {{ group.label }}
                                </td>
                            </tr>
                            <!-- Permission rows -->
                            <tr
                                v-for="perm in group.perms"
                                :key="perm"
                                class="border-t border-gray-800/50 hover:bg-gray-800/20 transition-colors"
                            >
                                <td class="px-4 py-2.5 text-xs font-mono text-gray-400">
                                    {{ perm }}
                                </td>
                                <td
                                    v-for="role in roles"
                                    :key="role.key"
                                    class="px-3 py-2.5 text-center"
                                >
                                    <input
                                        v-if="!isWildcardRole(role.key)"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-600 bg-gray-900 text-amber-400 focus:ring-amber-400"
                                        :checked="isPermChecked(role.key, perm)"
                                        @change="togglePerm(role.key, perm, $event.target.checked)"
                                    />
                                    <span v-else class="text-emerald-400 text-base" title="Rol con acceso total">✓</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── MODAL: Crear / Editar usuario ──────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="modalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                @click.self="closeModal"
            >
                <div class="w-full max-w-lg rounded-2xl bg-gray-900 ring-1 ring-gray-700 shadow-2xl">

                    <div class="flex items-center justify-between border-b border-gray-800 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-50">
                            {{ editingUser ? 'Editar usuario' : 'Nuevo usuario' }}
                        </h3>
                        <button type="button" class="text-gray-500 hover:text-gray-300 text-xl leading-none" @click="closeModal">✕</button>
                    </div>

                    <form class="p-6 grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">

                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-sm text-gray-300">Nombre completo</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm text-gray-300">Usuario</label>
                            <input
                                v-model="form.username"
                                type="text"
                                autocomplete="username"
                                required
                                class="w-full rounded-xl border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                            />
                            <p v-if="form.errors.username" class="text-xs text-red-400">{{ form.errors.username }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm text-gray-300">Correo</label>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                class="w-full rounded-xl border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                            />
                            <p v-if="form.errors.email" class="text-xs text-red-400">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-sm text-gray-300">Rol</label>
                            <select
                                v-model="form.role"
                                class="w-full rounded-xl border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                            >
                                <option v-for="role in roles" :key="role.key" :value="role.key">
                                    {{ role.label }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500">
                                {{ rolePermCount(form.role) }} permisos asignados
                            </p>
                        </div>

                        <!-- Permisos adicionales por usuario -->
                        <div v-if="!isWildcardRole(form.role)" class="space-y-2 sm:col-span-2">
                            <div class="flex items-center justify-between">
                                <label class="text-sm text-gray-300">Permisos adicionales</label>
                                <span class="text-xs text-gray-500">{{ form.extra_permissions.length }} extra</span>
                            </div>
                            <p class="text-xs text-gray-500">
                                Otorga al usuario permisos fuera de su rol. No afecta a otros usuarios con el mismo rol.
                            </p>
                            <div class="max-h-64 overflow-y-auto rounded-xl border border-gray-800 bg-gray-950/60 p-3 space-y-3">
                                <template v-for="group in permissionGroups" :key="group.module">
                                    <div v-if="groupExtrasOf(group, form.role).length">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                                            {{ group.label }}
                                        </p>
                                        <div class="grid gap-1 sm:grid-cols-2">
                                            <label
                                                v-for="perm in groupExtrasOf(group, form.role)"
                                                :key="perm"
                                                class="inline-flex items-center gap-2 text-xs text-gray-300 hover:text-gray-100"
                                            >
                                                <input
                                                    type="checkbox"
                                                    class="h-3.5 w-3.5 rounded border-gray-600 bg-gray-900 text-amber-400 focus:ring-amber-400"
                                                    :checked="form.extra_permissions.includes(perm)"
                                                    @change="toggleExtra(perm, $event.target.checked)"
                                                />
                                                <span class="font-mono">{{ perm }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                                <p v-if="!hasAnyExtraAvailable(form.role)" class="text-xs text-gray-500 italic">
                                    Este rol ya tiene todos los permisos disponibles.
                                </p>
                            </div>
                        </div>
                        <div v-else class="sm:col-span-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                            Este rol tiene acceso total — no requiere permisos adicionales.
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm text-gray-300">Contraseña</label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!editingUser"
                                autocomplete="new-password"
                                class="w-full rounded-xl border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                                :placeholder="editingUser ? 'Dejar vacío para no cambiar' : ''"
                            />
                            <p v-if="form.errors.password" class="text-xs text-red-400">{{ form.errors.password }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm text-gray-300">PIN <span class="text-gray-500">(opcional)</span></label>
                            <input
                                v-model="form.pin"
                                type="password"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                autocomplete="one-time-code"
                                class="w-full rounded-xl border border-gray-700 bg-gray-950 px-3 py-2.5 text-sm text-gray-100 focus:border-amber-400 focus:outline-none"
                                placeholder="4-10 dígitos"
                            />
                            <p v-if="form.errors.pin" class="text-xs text-red-400">{{ form.errors.pin }}</p>
                        </div>

                        <div class="sm:col-span-2 flex justify-end gap-3 pt-2 border-t border-gray-800">
                            <button
                                type="button"
                                class="rounded-xl border border-gray-700 px-4 py-2 text-sm text-gray-300 hover:border-gray-500"
                                @click="closeModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-xl bg-amber-400 px-5 py-2 text-sm font-semibold text-black shadow hover:bg-amber-300 disabled:opacity-50"
                            >
                                {{ editingUser ? 'Guardar cambios' : 'Crear usuario' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL: Confirmar eliminar ──────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="deleteTarget"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                @click.self="deleteTarget = null"
            >
                <div class="w-full max-w-sm rounded-2xl bg-gray-900 ring-1 ring-gray-700 shadow-2xl p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-50">¿Eliminar usuario?</h3>
                    <p class="text-sm text-gray-400">
                        Se eliminará <span class="font-semibold text-gray-200">{{ deleteTarget.name }}</span> de forma permanente.
                        Esta acción no se puede deshacer.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-xl border border-gray-700 px-4 py-2 text-sm text-gray-300 hover:border-gray-500"
                            @click="deleteTarget = null"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-red-500 px-5 py-2 text-sm font-semibold text-white hover:bg-red-400"
                            @click="executeDelete"
                        >
                            Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    users:            { type: Array, default: () => [] },
    roles:            { type: Array, default: () => [] },
    permissionGroups: { type: Array, default: () => [] },
});

const page         = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = [
    { key: 'users',       label: 'Usuarios' },
    { key: 'permissions', label: 'Roles y Permisos' },
];
const activeTab = ref('users');

// ── Role helpers ──────────────────────────────────────────────────────────────
const ROLE_COLORS = {
    owner_manager:  { avatar: 'bg-amber-400/20 text-amber-300',   badge: 'bg-amber-400/10 text-amber-300 border border-amber-500/30',   text: 'text-amber-300' },
    seller_cashier: { avatar: 'bg-blue-400/20 text-blue-300',     badge: 'bg-blue-400/10 text-blue-300 border border-blue-500/30',     text: 'text-blue-300' },
    warehouse:      { avatar: 'bg-emerald-400/20 text-emerald-300', badge: 'bg-emerald-400/10 text-emerald-300 border border-emerald-500/30', text: 'text-emerald-300' },
    accounting:     { avatar: 'bg-purple-400/20 text-purple-300', badge: 'bg-purple-400/10 text-purple-300 border border-purple-500/30', text: 'text-purple-300' },
    technician:     { avatar: 'bg-gray-400/20 text-gray-300',     badge: 'bg-gray-400/10 text-gray-300 border border-gray-600',         text: 'text-gray-300' },
};

const roleColor = (key) => ROLE_COLORS[key] ?? ROLE_COLORS.technician;

const initials = (name) => name?.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() ?? '?';

const isWildcardRole = (roleKey) => {
    const role = props.roles.find(r => r.key === roleKey);
    return !!role && role.permissions.includes('*');
};

// Editable permission matrix — reactive copy of role → Set(permissions)
const rolePerms = reactive({});
const baselinePerms = reactive({});
const rolesSaving = ref(false);

const resetMatrix = () => {
    for (const role of props.roles) {
        rolePerms[role.key] = new Set(role.permissions);
        baselinePerms[role.key] = new Set(role.permissions);
    }
};
resetMatrix();

// Re-sync on successful saves so server state is reflected
watch(() => props.roles, resetMatrix, { deep: true });

const isPermChecked = (roleKey, perm) => rolePerms[roleKey]?.has(perm) ?? false;

const togglePerm = (roleKey, perm, checked) => {
    const set = rolePerms[roleKey];
    if (!set) return;
    checked ? set.add(perm) : set.delete(perm);
};

const roleDirty = (roleKey) => {
    const current = rolePerms[roleKey];
    const baseline = baselinePerms[roleKey];
    if (!current || !baseline) return false;
    if (current.size !== baseline.size) return true;
    for (const p of current) if (!baseline.has(p)) return true;
    return false;
};

const rolesDirty = computed(() => props.roles.some(r => !isWildcardRole(r.key) && roleDirty(r.key)));

const saveAllRoles = () => {
    const changed = props.roles.filter(r => !isWildcardRole(r.key) && roleDirty(r.key));
    if (!changed.length) return;

    rolesSaving.value = true;
    let remaining = changed.length;

    changed.forEach(role => {
        router.put(`/admin/roles/${role.key}`, {
            permissions: Array.from(rolePerms[role.key]),
        }, {
            preserveScroll: true,
            onFinish: () => {
                if (--remaining === 0) rolesSaving.value = false;
            },
        });
    });
};

const rolePermCount = (roleKey) => {
    const role = props.roles.find(r => r.key === roleKey);
    if (!role) return 0;
    if (role.permissions.includes('*')) return '∞ (todos)';
    return role.permissions.length;
};

// ── Per-user extra permissions ────────────────────────────────────────────────
const rolePermSet = (roleKey) => {
    const role = props.roles.find(r => r.key === roleKey);
    return new Set(role ? role.permissions : []);
};

const groupExtrasOf = (group, roleKey) => {
    const base = rolePermSet(roleKey);
    return group.perms.filter(p => !base.has(p));
};

const hasAnyExtraAvailable = (roleKey) => {
    return props.permissionGroups.some(g => groupExtrasOf(g, roleKey).length > 0);
};

const toggleExtra = (perm, checked) => {
    const set = new Set(form.extra_permissions);
    checked ? set.add(perm) : set.delete(perm);
    form.extra_permissions = Array.from(set);
};

// ── Modal ─────────────────────────────────────────────────────────────────────
const modalOpen   = ref(false);
const editingUser = ref(null);

const form = useForm({
    name:              '',
    username:          '',
    email:             '',
    role:              props.roles[0]?.key ?? 'seller_cashier',
    password:          '',
    pin:               '',
    extra_permissions: [],
});

function openCreate() {
    editingUser.value = null;
    form.reset();
    form.role = props.roles[0]?.key ?? 'seller_cashier';
    form.extra_permissions = [];
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(user) {
    editingUser.value = user;
    form.name              = user.name;
    form.username          = user.username ?? '';
    form.email             = user.email;
    form.role              = user.role;
    form.password          = '';
    form.pin               = '';
    form.extra_permissions = [...(user.extra_permissions ?? [])];
    form.clearErrors();
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    editingUser.value = null;
}

function submitForm() {
    const extras = isWildcardRole(form.role) ? [] : form.extra_permissions;

    const payload = {
        name:              form.name,
        username:          form.username,
        email:             form.email,
        role:              form.role,
        password:          editingUser.value ? (form.password || null) : form.password,
        pin:               form.pin === '' ? null : form.pin,
        extra_permissions: extras,
    };

    const options = {
        onSuccess: () => closeModal(),
    };

    if (editingUser.value) {
        form.transform(() => ({ ...payload, _method: 'PUT' }))
            .post(`/admin/users/${editingUser.value.id}`, options);
    } else {
        form.transform(() => payload).post('/admin/users', options);
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref(null);

function confirmDelete(user) {
    deleteTarget.value = user;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/admin/users/${deleteTarget.value.id}`, {
        onSuccess: () => { deleteTarget.value = null; },
    });
}
</script>
