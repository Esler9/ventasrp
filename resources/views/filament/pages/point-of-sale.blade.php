<x-filament-panels::page>
    <div class="space-y-6">
        <div class="mx-auto flex max-w-4xl items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.debounce.300ms="search"
                    wire:keydown.enter.prevent="searchProducts"
                    placeholder="Buscar por nombre o código de barras"
                    class="w-full rounded-xl border-gray-300 px-4 py-3 text-lg shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                >
                <p class="mt-1 text-xs text-gray-500">En móvil: escribe o toca Escanear para usar la cámara.</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <x-filament::button color="gray" wire:click="searchProducts">
                    Buscar
                </x-filament::button>
                <x-filament::button color="primary" icon="heroicon-m-qr-code" x-data @click="window.dispatchEvent(new Event('pos-scan'))">
                    Escanear
                </x-filament::button>
            </div>
        </div>

        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Resultados</h2>
                <span class="text-xs text-gray-500">{{ $results->count() }} ítems</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-gray-800 dark:text-gray-100">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">SKU</th>
                            <th class="px-4 py-3 text-left">Vence</th>
                            <th class="px-4 py-3 text-left">Stock</th>
                            <th class="px-4 py-3 text-left">Precio</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($results as $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/70">
                                <td class="px-4 py-3 font-semibold">{{ $product->name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $product->sku }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    @if($product->expires_at)
                                        {{ \Illuminate\Support\Carbon::parse($product->expires_at)->toDateString() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $product->stock }}</td>
                                <td class="px-4 py-3 font-semibold text-amber-600 dark:text-amber-400">${{ number_format($product->price, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-filament::button color="primary" size="sm" wire:click="openConfirm({{ $product->id }})">
                                        Vender
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Sin productos. Busca otro término o crea uno nuevo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                        <p class="mt-1 text-xs text-gray-500">Puedes ajustar el precio. La diferencia queda registrada como descuento.</p>
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
                        <span>${{ number_format((float) $confirmPrice * (int) $confirmQuantity, 2) }}</span>
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
