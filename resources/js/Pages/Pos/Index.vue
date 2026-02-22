<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <GlobalLoader />
        <div class="px-4 pt-3">
            <FlashBanner />
        </div>

        <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-900/80 px-4 pb-3 pt-4 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                <div class="flex items-center">
                    <div>
                        <p class="text-xl font-semibold">Nueva Venta #{{ displaySaleCode }}</p>
                        <p class="text-xs text-slate-400">Cliente: {{ selectedClientName }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-slate-800/80 text-slate-300">
                        <i class="fa-solid fa-bell"></i>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500"></span>
                    </button>
                    <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-800/80 text-slate-300" @click="toggleTheme" title="Tema">
                        <i :class="theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"></i>
                    </button>
                    <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-800/80 text-slate-300" @click="logout" title="Salir">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 pb-10 pt-4 lg:flex lg:h-[calc(100vh-11.5rem)] lg:flex-col lg:overflow-hidden lg:pb-4" :style="contentSafeArea">
            <div class="mb-3 hidden lg:flex lg:justify-end">
                <button
                    v-if="desktopCheckoutCollapsed"
                    type="button"
                    class="rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800"
                    @click="desktopCheckoutCollapsed = false"
                >
                    Mostrar venta
                </button>
            </div>

            <div class="grid gap-4 lg:min-h-0 lg:flex-1 lg:items-start" :class="desktopCheckoutCollapsed ? 'lg:grid-cols-1' : 'lg:grid-cols-[minmax(0,1fr)_26rem]'">
                <section class="space-y-4 lg:flex lg:min-h-0 lg:flex-col lg:space-y-3">
                    <div v-if="!openCashSession" class="rounded-xl border border-rose-700/60 bg-rose-900/20 p-3 text-sm text-rose-200">
                        Debes abrir caja antes de vender.
                        <a href="/admin/cash" class="font-semibold underline">Ir a Caja</a>
                    </div>

                    <section class="space-y-3">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar producto (Código, Nombre)..."
                                class="w-full rounded-2xl border border-slate-700 bg-slate-900/70 py-3 pl-10 pr-14 text-sm text-slate-100 placeholder-slate-500 focus:border-sky-400 focus:outline-none"
                            />
                            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-xl bg-slate-700/80 px-3 py-2 text-slate-200" title="Buscar por código">
                                <i class="fa-solid fa-qrcode"></i>
                            </button>
                        </div>

                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <button
                                type="button"
                                class="whitespace-nowrap rounded-full border px-4 py-2 text-sm"
                                :class="selectedCategoryId === null ? 'border-sky-500 bg-sky-500 text-white' : 'border-slate-700 bg-slate-900/70 text-slate-300'"
                                @click="setCategory(null)"
                            >
                                Todas
                            </button>
                            <button
                                v-for="category in topCategories"
                                :key="category.id"
                                type="button"
                                class="whitespace-nowrap rounded-full border px-4 py-2 text-sm"
                                :class="selectedCategoryId === category.id ? 'border-sky-500 bg-sky-500 text-white' : 'border-slate-700 bg-slate-900/70 text-slate-300'"
                                @click="setCategory(category.id)"
                            >
                                {{ category.name }}
                            </button>
                        </div>
                    </section>

                    <section class="lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:pr-1">
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
                            <article
                                v-for="product in products"
                                :key="product.id"
                                class="group overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 shadow-sm"
                            >
                                <div class="relative h-36 bg-slate-800">
                                    <button
                                        v-if="product.photo_url"
                                        type="button"
                                        class="h-full w-full cursor-zoom-in"
                                        :aria-label="`Ver foto de ${product.name}`"
                                        @click="openPhoto(product)"
                                    >
                                        <img :src="product.photo_url" :alt="`Foto de ${product.name}`" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                    </button>
                                    <div v-else class="flex h-full w-full items-center justify-center text-slate-500">
                                        <i class="fa-solid fa-box text-2xl"></i>
                                    </div>

                                    <div class="absolute right-2 top-2 rounded-lg bg-black/55 px-2 py-1 text-[10px] text-white">
                                        <i class="fa-solid fa-boxes-stacked mr-1"></i>{{ product.stock }}
                                    </div>

                                    <div v-if="Number(product.stock) <= 5" class="absolute left-2 top-2 rounded-lg bg-amber-400 px-2 py-1 text-[10px] font-semibold text-black">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ product.stock }} left
                                    </div>

                                    <div v-if="qtyByProduct[product.id]" class="absolute left-2 top-2 rounded-full bg-sky-500 px-2 py-1 text-[11px] font-bold text-white" :class="Number(product.stock) <= 5 ? 'top-10' : ''">
                                        {{ qtyByProduct[product.id] }}
                                    </div>
                                </div>

                                <div class="space-y-2 p-3">
                                    <span class="inline-flex rounded-lg bg-sky-900/50 px-2 py-1 text-xs font-semibold text-sky-300">
                                        {{ product.sku || 'SIN-SKU' }}
                                    </span>
                                    <h3 class="line-clamp-2 min-h-12 text-base font-semibold leading-tight">{{ product.name }}</h3>
                                    <div class="flex items-end justify-between">
                                        <p class="text-3xl font-bold tracking-tight">Q{{ money(product.price) }}</p>
                                        <button
                                            type="button"
                                            class="flex h-11 w-11 items-center justify-center rounded-full"
                                            :class="qtyByProduct[product.id] ? 'bg-sky-500 text-white' : 'bg-sky-500/20 text-sky-300'"
                                            @click="addProduct(product)"
                                        >
                                            <i :class="qtyByProduct[product.id] ? 'fa-solid fa-check' : 'fa-solid fa-plus'"></i>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </section>

                <aside v-if="!desktopCheckoutCollapsed" class="hidden lg:flex lg:h-full lg:flex-col lg:overflow-hidden lg:rounded-2xl lg:border lg:border-slate-800 lg:bg-slate-900/95">
                    <div class="border-b border-slate-800 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Detalle de venta</p>
                            <button type="button" class="text-xs text-slate-400 hover:text-slate-200" @click="desktopCheckoutCollapsed = true">Ocultar</button>
                        </div>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto p-4">
                        <div class="space-y-1">
                            <label class="text-xs text-slate-400">Código de venta</label>
                            <input v-model="saleForm.sale_code" type="text" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm" />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="text-xs text-slate-400">Cliente</label>
                                <button
                                    v-if="canCreateClient"
                                    type="button"
                                    class="rounded-md border border-slate-700 px-2 py-1 text-[11px] font-semibold text-slate-200 hover:bg-slate-800"
                                    @click="toggleQuickClient"
                                >
                                    {{ quickClientOpen ? 'Cancelar' : '+ Rápido' }}
                                </button>
                            </div>
                            <select v-model="saleForm.customer_id" class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm">
                                <option :value="null">Consumidor Final (CF)</option>
                                <option v-for="client in clientsOptions" :key="client.id" :value="client.id">
                                    {{ client.name }}{{ client.tax_id ? ` · ${client.tax_id}` : '' }}
                                </option>
                            </select>
                            <div v-if="quickClientOpen && canCreateClient" class="space-y-2 rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                                <input
                                    v-model="quickClient.name"
                                    type="text"
                                    placeholder="Nombre del cliente"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm"
                                />
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        v-model="quickClient.phone"
                                        type="text"
                                        placeholder="Teléfono"
                                        class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm"
                                    />
                                    <input
                                        v-model="quickClient.tax_id"
                                        type="text"
                                        placeholder="NIT"
                                        class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm"
                                    />
                                </div>
                                <p v-if="quickClientError" class="text-xs text-rose-300">{{ quickClientError }}</p>
                                <button
                                    type="button"
                                    class="w-full rounded-lg bg-sky-500 px-3 py-2 text-xs font-semibold text-white disabled:opacity-60"
                                    :disabled="quickClientSaving"
                                    @click="createQuickClient"
                                >
                                    {{ quickClientSaving ? 'Guardando...' : 'Guardar cliente rápido' }}
                                </button>
                            </div>
                        </div>

                        <section class="rounded-xl border border-sky-800/60 bg-sky-950/20 p-3">
                            <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-sky-300">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <span>Productos agregados</span>
                            </div>
                            <div class="space-y-2">
                                <article v-for="(item, index) in cart" :key="item.row_key" class="rounded-xl border border-sky-900/70 bg-slate-950/70 p-3">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-semibold">{{ item.name }}</p>
                                            <p class="text-xs text-slate-400">{{ item.presentation_name }}</p>
                                        </div>
                                        <button type="button" class="text-xs text-rose-300" @click="removeItem(index)">Quitar</button>
                                    </div>
                                    <div class="mt-2 grid grid-cols-3 gap-2">
                                        <input v-model.number="item.quantity" type="number" min="1" class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm" />
                                        <input v-model.number="item.price" :disabled="!canChangePrice" type="number" min="0" step="0.01" class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm disabled:opacity-60" />
                                        <div class="flex flex-col items-end justify-center">
                                            <p class="text-[10px] uppercase tracking-wide text-sky-300">Subtotal</p>
                                            <p class="text-sm font-bold text-sky-200">Q{{ money(item.quantity * item.price) }}</p>
                                        </div>
                                    </div>
                                </article>
                                <p v-if="!cart.length" class="rounded-lg border border-dashed border-slate-700 px-3 py-3 text-xs text-slate-400">
                                    No hay productos agregados.
                                </p>
                            </div>
                        </section>

                        <section class="space-y-2 rounded-xl border border-amber-700/50 bg-amber-950/20 p-3">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-300">
                                <i class="fa-solid fa-credit-card"></i>
                                <span>Formas de pago</span>
                            </div>
                            <div v-for="(payment, idx) in saleForm.payments" :key="`desk-pay-${idx}`" class="grid grid-cols-5 gap-2">
                                <select v-model="payment.method" class="col-span-2 rounded-lg border border-amber-800/50 bg-slate-900 px-2 py-2 text-sm">
                                    <option value="cash">Efectivo</option>
                                    <option value="card">Tarjeta</option>
                                    <option value="transfer">Transferencia</option>
                                </select>
                                <input v-model.number="payment.amount" type="number" min="0.01" step="0.01" class="col-span-2 rounded-lg border border-amber-800/50 bg-slate-900 px-2 py-2 text-sm" />
                                <button type="button" class="rounded-lg border border-amber-800/60 text-xs" @click="removePayment(idx)">X</button>
                            </div>
                            <button type="button" class="rounded-lg border border-amber-700/70 px-3 py-2 text-xs text-amber-200" @click="addPayment">Agregar método</button>
                        </section>
                    </div>

                    <div class="shrink-0 border-t border-slate-800 bg-slate-900 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Resumen de venta</p>
                        <p class="mt-1 text-5xl font-bold leading-none">Q{{ money(total) }}</p>
                        <p class="mt-1 text-xs" :class="paymentsMatch ? 'text-emerald-300' : 'text-rose-300'">Pagado: Q{{ money(paymentsTotal) }}</p>
                        <div v-if="saleForm.errors.sale" class="mt-2 text-xs text-rose-300">{{ saleForm.errors.sale }}</div>
                        <button
                            type="button"
                            class="mt-3 w-full rounded-2xl bg-sky-500 px-4 py-3 text-lg font-semibold text-white shadow-lg shadow-sky-700/30 disabled:opacity-50"
                            :disabled="!canSubmit"
                            @click="submitSale"
                        >
                            Pagar Ahora <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </aside>
            </div>
        </div>

        <div class="fixed inset-x-0 z-30 lg:hidden" :style="checkoutDockStyle">
            <div class="mx-auto max-w-7xl px-4">
                <section class="rounded-2xl border border-slate-800 bg-slate-900/95 px-3 py-2 shadow-2xl backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] uppercase tracking-wide text-slate-400">Total ({{ itemsCount }} items)</p>
                            <p class="text-3xl font-bold leading-none">Q{{ money(total) }}</p>
                            <p class="text-[11px]" :class="paymentsMatch ? 'text-emerald-300' : 'text-rose-300'">Pagado: Q{{ money(paymentsTotal) }}</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-700/30"
                            :disabled="cart.length === 0"
                            @click="mobileCheckoutOpen = true"
                        >
                            Pagar Ahora
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <div v-if="mobileCheckoutOpen" class="fixed inset-0 z-50 bg-slate-950 lg:hidden">
            <div class="flex h-full flex-col">
                <div class="border-b border-slate-800 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Detalle de venta</p>
                        <button type="button" class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-200" @click="mobileCheckoutOpen = false">
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="flex-1 space-y-3 overflow-y-auto px-4 pb-40 pt-3">
                    <div>
                        <label class="text-xs text-slate-400">Código de venta</label>
                        <input v-model="saleForm.sale_code" type="text" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-xs text-slate-400">Cliente</label>
                            <button
                                v-if="canCreateClient"
                                type="button"
                                class="rounded-md border border-slate-700 px-2 py-1 text-[11px] font-semibold text-slate-200 hover:bg-slate-800"
                                @click="toggleQuickClient"
                            >
                                {{ quickClientOpen ? 'Cancelar' : '+ Rápido' }}
                            </button>
                        </div>
                        <select v-model="saleForm.customer_id" class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm">
                            <option :value="null">Consumidor Final (CF)</option>
                            <option v-for="client in clientsOptions" :key="`mobile-client-${client.id}`" :value="client.id">
                                {{ client.name }}{{ client.tax_id ? ` · ${client.tax_id}` : '' }}
                            </option>
                        </select>
                        <div v-if="quickClientOpen && canCreateClient" class="space-y-2 rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                            <input
                                v-model="quickClient.name"
                                type="text"
                                placeholder="Nombre del cliente"
                                class="w-full rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm"
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    v-model="quickClient.phone"
                                    type="text"
                                    placeholder="Teléfono"
                                    class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm"
                                />
                                <input
                                    v-model="quickClient.tax_id"
                                    type="text"
                                    placeholder="NIT"
                                    class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm"
                                />
                            </div>
                            <p v-if="quickClientError" class="text-xs text-rose-300">{{ quickClientError }}</p>
                            <button
                                type="button"
                                class="w-full rounded-lg bg-sky-500 px-3 py-2 text-xs font-semibold text-white disabled:opacity-60"
                                :disabled="quickClientSaving"
                                @click="createQuickClient"
                            >
                                {{ quickClientSaving ? 'Guardando...' : 'Guardar cliente rápido' }}
                            </button>
                        </div>
                    </div>

                    <section class="rounded-xl border border-sky-800/60 bg-sky-950/20 p-3">
                        <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-sky-300">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <span>Productos agregados</span>
                        </div>
                        <div class="space-y-2">
                            <article v-for="(item, index) in cart" :key="`mobile-item-${item.row_key}`" class="rounded-xl border border-sky-900/70 bg-slate-950/70 p-3">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold">{{ item.name }}</p>
                                        <p class="text-xs text-slate-400">{{ item.presentation_name }}</p>
                                    </div>
                                    <button type="button" class="text-xs text-rose-300" @click="removeItem(index)">Quitar</button>
                                </div>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            <input v-model.number="item.quantity" type="number" min="1" class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm" />
                            <input v-model.number="item.price" :disabled="!canChangePrice" type="number" min="0" step="0.01" class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm disabled:opacity-60" />
                            <div class="flex flex-col items-end justify-center">
                                <p class="text-[10px] uppercase tracking-wide text-sky-300">Subtotal</p>
                                <p class="text-sm font-bold text-sky-200">Q{{ money(item.quantity * item.price) }}</p>
                            </div>
                        </div>
                    </article>
                            <p v-if="!cart.length" class="rounded-lg border border-dashed border-slate-700 px-3 py-3 text-xs text-slate-400">
                                No hay productos agregados.
                            </p>
                        </div>
                    </section>

                    <section class="space-y-2 rounded-xl border border-amber-700/50 bg-amber-950/20 p-3">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-300">
                            <i class="fa-solid fa-credit-card"></i>
                            <span>Formas de pago</span>
                        </div>
                        <div v-for="(payment, idx) in saleForm.payments" :key="`mobile-pay-${idx}`" class="grid grid-cols-5 gap-2">
                            <select v-model="payment.method" class="col-span-2 rounded-lg border border-amber-800/50 bg-slate-900 px-2 py-2 text-sm">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="transfer">Transferencia</option>
                            </select>
                            <input v-model.number="payment.amount" type="number" min="0.01" step="0.01" class="col-span-2 rounded-lg border border-amber-800/50 bg-slate-900 px-2 py-2 text-sm" />
                            <button type="button" class="rounded-lg border border-amber-800/60 text-xs" @click="removePayment(idx)">X</button>
                        </div>
                        <button type="button" class="rounded-lg border border-amber-700/70 px-3 py-2 text-xs text-amber-200" @click="addPayment">Agregar método</button>
                    </section>
                </div>

                <div class="border-t border-slate-800 bg-slate-900 px-4 pb-[calc(1rem+env(safe-area-inset-bottom,0px))] pt-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Resumen de venta</p>
                    <p class="mt-1 text-4xl font-bold leading-none">Q{{ money(total) }}</p>
                    <p class="mt-1 text-xs" :class="paymentsMatch ? 'text-emerald-300' : 'text-rose-300'">Pagado: Q{{ money(paymentsTotal) }}</p>
                    <div v-if="saleForm.errors.sale" class="mt-2 text-xs text-rose-300">{{ saleForm.errors.sale }}</div>
                    <button
                        type="button"
                        class="mt-3 w-full rounded-2xl bg-sky-500 px-4 py-3 text-lg font-semibold text-white shadow-lg shadow-sky-700/30 disabled:opacity-50"
                        :disabled="!canSubmit"
                        @click="submitSale"
                    >
                        Pagar Ahora <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="previewPhotoUrl"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            role="dialog"
            aria-modal="true"
            @click.self="closePhoto"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-full bg-black/60 px-3 py-2 text-sm font-semibold text-white hover:bg-black/75"
                aria-label="Cerrar vista ampliada"
                @click="closePhoto"
            >
                Cerrar
            </button>
            <img
                :src="previewPhotoUrl"
                :alt="previewPhotoName ? `Foto de ${previewPhotoName}` : 'Foto de producto ampliada'"
                class="max-h-[85vh] max-w-[95vw] rounded-xl object-contain"
            />
        </div>

        <BottomNav />
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import BottomNav from '../../Components/BottomNav.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import GlobalLoader from '../../Components/GlobalLoader.vue';
import { useTheme } from '../../composables/useTheme';

const props = defineProps({
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '' }) },
    top_categories: { type: Array, default: () => [] },
    selected_category_id: { type: Number, default: 0 },
    clients: { type: Array, default: () => [] },
    defaults: { type: Object, default: () => ({ sale_code: '', customer_id: null, customer_name: 'CF' }) },
    open_cash_session: { type: Object, default: null },
});

const { theme, toggleTheme } = useTheme();
const page = usePage();
const search = ref(props.filters.q || '');
const products = ref(props.products);
const selectedCategoryId = ref(props.selected_category_id > 0 ? props.selected_category_id : null);
const cart = ref([]);
const mobileCheckoutOpen = ref(false);
const desktopCheckoutCollapsed = ref(false);
const isDesktop = ref(false);
const topCategories = computed(() => props.top_categories || []);
const clientsOptions = ref([...(props.clients || [])]);
const quickClientOpen = ref(false);
const quickClientSaving = ref(false);
const quickClientError = ref('');
const quickClient = ref({
    name: '',
    phone: '',
    tax_id: '',
});
const previewPhotoUrl = ref(null);
const previewPhotoName = ref('');

const saleForm = useForm({
    sale_code: props.defaults.sale_code || '',
    customer_id: props.defaults.customer_id || null,
    customer_name: props.defaults.customer_name || 'CF',
    items: [],
    payments: [{ method: 'cash', amount: 0 }],
});

const displaySaleCode = computed(() => saleForm.sale_code || '---');
const openCashSession = computed(() => props.open_cash_session);
const canChangePrice = computed(() => {
    const permissions = page.props.auth?.user?.permissions || [];
    return permissions.includes('*') || permissions.includes('pos.change_price');
});

const canCreateClient = computed(() => {
    const permissions = page.props.auth?.user?.permissions || [];
    return permissions.includes('*') || permissions.includes('clients.create');
});

const selectedClientName = computed(() => {
    if (!saleForm.customer_id) return saleForm.customer_name || 'CF';
    const found = clientsOptions.value.find((client) => Number(client.id) === Number(saleForm.customer_id));
    return found?.name || saleForm.customer_name || 'CF';
});

watch(
    () => props.products,
    (value) => {
        products.value = value;
    },
);

watch(
    () => props.clients,
    (value) => {
        clientsOptions.value = [...(value || [])];
    },
);

watch(
    () => props.selected_category_id,
    (value) => {
        selectedCategoryId.value = value > 0 ? value : null;
    },
);

const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const fetchProducts = (overrides = {}) => {
    router.get(
        '/pos',
        {
            q: search.value || undefined,
            category_id: selectedCategoryId.value ?? undefined,
            ...overrides,
        },
        { replace: true, preserveState: true, preserveScroll: true },
    );
};

const debouncedSearch = debounce(() => {
    fetchProducts();
});

watch(search, () => debouncedSearch());

const setCategory = (categoryId) => {
    selectedCategoryId.value = categoryId;
    fetchProducts();
};

const addProduct = (product) => {
    const existing = cart.value.find((item) => item.product_id === product.id && item.presentation_name === (product.unit_label || 'Unidad'));
    if (existing) {
        existing.quantity += 1;
        return;
    }

    cart.value.push({
        row_key: `${product.id}-${Date.now()}`,
        product_id: product.id,
        product_presentation_id: null,
        name: product.name,
        presentation_name: product.unit_label || 'Unidad',
        presentation_factor: 1,
        quantity: 1,
        price: Number(product.price || 0),
        note: '',
    });
};

const removeItem = (index) => {
    cart.value.splice(index, 1);
};

const total = computed(() => cart.value.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.price || 0), 0));
const itemsCount = computed(() => cart.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0));
const paymentsTotal = computed(() => saleForm.payments.reduce((sum, p) => sum + Number(p.amount || 0), 0));
const paymentsMatch = computed(() => Math.abs(paymentsTotal.value - total.value) < 0.01);

const qtyByProduct = computed(() => {
    const qty = {};
    cart.value.forEach((item) => {
        qty[item.product_id] = (qty[item.product_id] || 0) + Number(item.quantity || 0);
    });
    return qty;
});

watch(total, (value) => {
    if (saleForm.payments.length === 1 && saleForm.payments[0].method === 'cash') {
        saleForm.payments[0].amount = Number(value.toFixed(2));
    }
});

watch(
    () => saleForm.customer_id,
    (value) => {
        if (!value) {
            saleForm.customer_name = 'CF';
            return;
        }

        const found = clientsOptions.value.find((client) => Number(client.id) === Number(value));
        if (found) {
            saleForm.customer_name = found.name;
        }
    },
);

const addPayment = () => {
    saleForm.payments.push({ method: 'cash', amount: 0 });
};

const removePayment = (index) => {
    if (saleForm.payments.length === 1) return;
    saleForm.payments.splice(index, 1);
};

const canSubmit = computed(() => openCashSession.value && cart.value.length > 0 && paymentsMatch.value && !saleForm.processing);

const submitSale = () => {
    saleForm.items = cart.value.map((item) => ({
        product_id: item.product_id,
        product_presentation_id: item.product_presentation_id,
        presentation_name: item.presentation_name,
        presentation_factor: item.presentation_factor,
        quantity: Number(item.quantity || 0),
        price: Number(item.price || 0),
        note: item.note || null,
    }));

    saleForm.post('/pos/sales', {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            saleForm.payments = [{ method: 'cash', amount: 0 }];
            saleForm.sale_code = '';
            saleForm.customer_id = null;
            saleForm.customer_name = 'CF';
            mobileCheckoutOpen.value = false;
            quickClientOpen.value = false;
            quickClient.value = { name: '', phone: '', tax_id: '' };
        },
    });
};

const toggleQuickClient = () => {
    quickClientOpen.value = !quickClientOpen.value;
    quickClientError.value = '';
    if (!quickClientOpen.value) {
        quickClient.value = { name: '', phone: '', tax_id: '' };
    }
};

const createQuickClient = async () => {
    const name = quickClient.value.name.trim();
    if (!name) {
        quickClientError.value = 'Ingresa el nombre del cliente.';
        return;
    }

    quickClientSaving.value = true;
    quickClientError.value = '';

    try {
        const response = await axios.post(
            '/admin/clients',
            {
                name,
                phone: quickClient.value.phone?.trim() || null,
                tax_id: quickClient.value.tax_id?.trim() || null,
                is_active: true,
            },
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        const created = {
            id: Number(response.data.id),
            name: response.data.name,
            phone: response.data.phone,
            tax_id: response.data.tax_id,
        };

        clientsOptions.value = [...clientsOptions.value, created]
            .filter((client, index, array) => array.findIndex((item) => Number(item.id) === Number(client.id)) === index)
            .sort((a, b) => String(a.name).localeCompare(String(b.name), 'es'));

        saleForm.customer_id = created.id;
        saleForm.customer_name = created.name;
        quickClient.value = { name: '', phone: '', tax_id: '' };
        quickClientOpen.value = false;
    } catch (error) {
        quickClientError.value = error?.response?.data?.errors?.name?.[0]
            || error?.response?.data?.message
            || 'No se pudo crear el cliente.';
    } finally {
        quickClientSaving.value = false;
    }
};

const bottomNavHeight = '5.5rem';

const checkoutDockStyle = computed(() => ({
    bottom: `calc(${bottomNavHeight} + env(safe-area-inset-bottom, 0px))`,
}));

const contentSafeArea = computed(() => ({
    paddingBottom: isDesktop.value
        ? `calc(1rem + env(safe-area-inset-bottom, 0px))`
        : `calc(7.5rem + ${bottomNavHeight} + env(safe-area-inset-bottom, 0px))`,
}));

const logoutForm = useForm({});
const logout = () => logoutForm.post('/logout');

const money = (value) => Number(value || 0).toFixed(2);

const openPhoto = (product) => {
    previewPhotoUrl.value = product.photo_url || null;
    previewPhotoName.value = product.name || '';
};

const closePhoto = () => {
    previewPhotoUrl.value = null;
    previewPhotoName.value = '';
};

const onKeydown = (event) => {
    if (event.key === 'Escape' && previewPhotoUrl.value) {
        closePhoto();
    }

    if (event.key === 'Escape' && mobileCheckoutOpen.value) {
        mobileCheckoutOpen.value = false;
    }
};

const syncViewport = () => {
    const desktop = window.innerWidth >= 1024;
    isDesktop.value = desktop;

    if (desktop) {
        mobileCheckoutOpen.value = false;
    }
};

onMounted(() => {
    syncViewport();
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', syncViewport);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', syncViewport);
});
</script>
