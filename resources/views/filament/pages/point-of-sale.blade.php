<x-filament-panels::page>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Buscar producto</label>
                        <input
                            type="text"
                            wire:model.debounce.300ms="search"
                            wire:keydown.enter.prevent="searchProducts"
                            placeholder="Nombre o código de barras"
                            class="mt-1 w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                        >
                        <p class="mt-1 text-xs text-gray-500">Escribe, pega el código del escáner o usa la cámara del móvil.</p>
                    </div>
                    <x-filament::button color="gray" wire:click="searchProducts" class="mt-2 sm:mt-6">
                        Buscar
                    </x-filament::button>
                </div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Resultados</h2>
                    <span class="text-xs text-gray-500">{{ $results->count() }} ítems</span>
                </div>
                <div class="space-y-3">
                    @forelse ($results as $product)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 p-3 shadow-sm dark:border-gray-800 dark:bg-gray-800">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500">SKU: {{ $product->sku }} · Stock: {{ $product->stock }}</div>
                                <div class="text-sm font-bold text-amber-600 dark:text-amber-400">${{ number_format($product->price, 2) }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    wire:model.live="quantities.{{ $product->id }}"
                                    class="w-20 rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900"
                                >
                                <x-filament::button color="primary" wire:click="addToCart({{ $product->id }})">
                                    Agregar
                                </x-filament::button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sin productos. Prueba con otro término o crea un producto.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Carrito</h2>
                    <x-filament::icon-button icon="heroicon-m-trash" color="gray" wire:click="clearCart" :disabled="empty($cart)" />
                </div>
                <div class="space-y-3">
                    @forelse ($this->cartItems as $product)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 p-3 dark:border-gray-800 dark:bg-gray-800">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500">SKU: {{ $product->sku }}</div>
                                <div class="text-xs text-gray-500">Stock disponible: {{ $product->stock }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    value="{{ $cart[$product->id] ?? 1 }}"
                                    wire:change="updateCartQuantity({{ $product->id }}, $event.target.value)"
                                    class="w-20 rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900"
                                >
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    ${{ number_format($product->price * ($cart[$product->id] ?? 1), 2) }}
                                </div>
                                <x-filament::icon-button icon="heroicon-m-x-mark" color="gray" wire:click="removeFromCart({{ $product->id }})" />
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Agrega productos desde el buscador.</p>
                    @endforelse
                </div>

                <div class="mt-4 space-y-1 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                        <span>Items</span>
                        <span>{{ array_sum($cart) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 dark:text-gray-100">
                        <span>Total</span>
                        <span>${{ number_format($this->cartTotal, 2) }}</span>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <x-filament::button color="gray" wire:click="clearCart" :disabled="empty($cart)" class="flex-1">
                        Vaciar
                    </x-filament::button>
                    <x-filament::button color="primary" wire:click="confirmSale" :disabled="empty($cart)" class="flex-1">
                        Confirmar venta
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
