<x-filament-panels::page>
    <div class="space-y-6 px-2 sm:px-4 lg:px-8 bg-gray-900 text-gray-100">
        <div class="w-full rounded-3xl bg-gray-800 p-4 shadow-lg ring-1 ring-black/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <input
                        type="text"
                        wire:model.debounce.250ms="search"
                        wire:keydown.enter.prevent="searchProducts"
                        placeholder="Buscar por nombre o código"
                        class="w-full rounded-2xl border border-gray-700 bg-gray-850 px-5 py-4 text-lg text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                    >
                </div>
                <x-filament::button color="primary" icon="heroicon-m-qr-code" size="lg" class="w-full sm:w-auto bg-amber-400 text-black hover:bg-amber-300" x-data @click="window.dispatchEvent(new Event('pos-scan'))">
                    Escanear
                </x-filament::button>
            </div>
        </div>

        <div class="w-full space-y-3">
            @forelse ($results as $product)
                <div class="rounded-3xl bg-gray-800 p-4 shadow-lg ring-1 ring-black/20">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="text-lg font-semibold text-gray-50">{{ $product->name }}</div>
                            <div class="text-xs text-gray-400">SKU: {{ $product->sku }}</div>
                            <div class="text-xs text-gray-400">Stock: {{ $product->stock }}</div>
                            @if($product->expires_at)
                                <div class="text-xs text-gray-400">Vence: {{ \Illuminate\Support\Carbon::parse($product->expires_at)->toDateString() }}</div>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="text-lg font-semibold text-gray-50">${{ number_format($product->price, 2) }}</div>
                            <x-filament::button color="primary" size="sm" class="bg-amber-400 text-black hover:bg-amber-300" icon="heroicon-m-shopping-cart" wire:click="openConfirm({{ $product->id }})">
                                Vender
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl bg-gray-800 p-6 text-center text-sm text-gray-400 shadow-lg ring-1 ring-black/20">
                    Sin productos. Busca otro término o crea uno nuevo.
                </div>
            @endforelse
        </div>
    </div>

    @if ($showConfirmModal && !empty($confirmProduct))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-gray-850 p-5 shadow-2xl ring-1 ring-black/30 text-gray-100">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Confirmar venta</h3>
                        <p class="text-sm text-gray-400">{{ $confirmProduct['name'] }} · SKU: {{ $confirmProduct['sku'] }}</p>
                        <p class="text-xs text-gray-500">Stock disponible: {{ $confirmProduct['stock'] }}</p>
                        @if($confirmProduct['expires_at'])
                            <p class="text-xs text-amber-300">Vence: {{ $confirmProduct['expires_at'] }}</p>
                        @endif
                    </div>
                    <x-filament::icon-button icon="heroicon-m-x-mark" color="gray" wire:click="$set('showConfirmModal', false)" />
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Cantidad</label>
                        <input
                            type="number"
                            min="1"
                            wire:model.live="confirmQuantity"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        >
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Precio unitario</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model.live="confirmPrice"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        >
                        <p class="mt-1 text-xs text-gray-500">Puedes ajustar el precio. La diferencia queda registrada como descuento.</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-200">Nota (opcional)</label>
                        <textarea
                            rows="2"
                            wire:model.live="confirmNote"
                            class="mt-1 w-full rounded-lg border border-gray-700 bg-gray-800 text-base text-gray-100 shadow-inner focus:border-amber-400 focus:ring-amber-400"
                        ></textarea>
                    </div>
                    <div class="flex justify-between rounded-lg bg-gray-800 p-3 text-sm font-semibold text-gray-100">
                        <span>Total</span>
                        <span>${{ number_format((float) $confirmPrice * (int) $confirmQuantity, 2) }}</span>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <x-filament::button color="gray" wire:click="$set('showConfirmModal', false)" class="flex-1">
                        Cancelar
                    </x-filament::button>
                    <x-filament::button color="primary" wire:click="confirmSale" class="flex-1 bg-amber-400 text-black hover:bg-amber-300">
                        Vender
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
