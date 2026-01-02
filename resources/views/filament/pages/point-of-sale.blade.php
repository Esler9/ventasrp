<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Buscar producto</label>
                    <input
                        type="text"
                        wire:model.debounce.300ms="search"
                        wire:keydown.enter.prevent="searchProducts"
                        placeholder="Nombre o código de barras"
                        class="mt-1 w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                    >
                    <p class="mt-1 text-xs text-gray-500">Escribe, pega el código o usa la cámara.</p>
                </div>
                <div class="flex gap-2">
                    <x-filament::button color="gray" wire:click="searchProducts">
                        Buscar
                    </x-filament::button>
                    <x-filament::button color="primary" x-data @click="window.dispatchEvent(new Event('pos-scan'))">
                        Escanear
                    </x-filament::button>
                </div>
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
                            <div class="text-xs text-gray-500">
                                @if($product->expires_at)
                                    Vence: {{ \Illuminate\Support\Carbon::parse($product->expires_at)->toDateString() }}
                                @endif
                            </div>
                            <div class="text-sm font-bold text-amber-600 dark:text-amber-400">${{ number_format($product->price, 2) }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-filament::button color="primary" wire:click="openConfirm({{ $product->id }})">
                                Vender
                            </x-filament::button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Sin productos. Prueba con otro término o crea un producto.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($showConfirmModal && !empty($confirmProduct))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Confirmar venta</h3>
                        <p class="text-sm text-gray-500">{{ $confirmProduct['name'] }} · SKU: {{ $confirmProduct['sku'] }}</p>
                        <p class="text-xs text-gray-500">Stock disponible: {{ $confirmProduct['stock'] }}</p>
                        @if($confirmProduct['expires_at'])
                            <p class="text-xs text-red-500">Vence: {{ $confirmProduct['expires_at'] }}</p>
                        @endif
                    </div>
                    <x-filament::icon-button icon="heroicon-m-x-mark" color="gray" wire:click="$set('showConfirmModal', false)" />
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Cantidad</label>
                        <input
                            type="number"
                            min="1"
                            wire:model.live="confirmQuantity"
                            class="mt-1 w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                        >
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Precio unitario</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model.live="confirmPrice"
                            class="mt-1 w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                        >
                        <p class="mt-1 text-xs text-gray-500">Puedes ajustar el precio. La diferencia quedará como descuento.</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Nota (opcional)</label>
                        <textarea
                            rows="2"
                            wire:model.live="confirmNote"
                            class="mt-1 w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                        ></textarea>
                    </div>
                    <div class="flex justify-between rounded-lg bg-gray-50 p-3 text-sm font-semibold text-gray-900 dark:bg-gray-800 dark:text-gray-100">
                        <span>Total</span>
                        <span>
                            ${{ number_format((float) $confirmPrice * (int) $confirmQuantity, 2) }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <x-filament::button color="gray" wire:click="$set('showConfirmModal', false)" class="flex-1">
                        Cancelar
                    </x-filament::button>
                    <x-filament::button color="primary" wire:click="confirmSale" class="flex-1">
                        Vender
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
