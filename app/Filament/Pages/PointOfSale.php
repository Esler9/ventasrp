<?php

namespace App\Filament\Pages;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class PointOfSale extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static UnitEnum|string|null $navigationGroup = 'Ventas';
    protected static ?string $navigationLabel = 'Vender';
    protected static ?string $title = 'Punto de venta';

    protected string $view = 'filament.pages.point-of-sale';

    public ?string $busqueda = '';
    public ?string $search = '';
    public Collection $results;
    public array $confirmProduct = [];
    public int $confirmQuantity = 1;
    public string $confirmPrice = '';
    public ?string $confirmNote = null;
    public bool $showConfirmModal = false;

    public function mount(): void
    {
        $this->search = $this->busqueda;
        $this->searchProducts();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user?->isAdmin() || $user?->isSeller();
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public function searchProducts(): void
    {
        $this->search = $this->busqueda;
        $query = trim((string) $this->busqueda);

        $this->results = $this->productos;
    }

    public function getProductosProperty(): Collection
    {
        $query = trim((string) $this->busqueda);

        return Product::query()
            ->where('is_active', true)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($subQuery) use ($query) {
                    $subQuery
                        ->where('name', 'like', '%' . $query . '%')
                        ->orWhere('sku', 'like', '%' . $query . '%')
                        ->orWhere('description', 'like', '%' . $query . '%');
                });
                $builder->orderByRaw(
                    "CASE WHEN name LIKE ? THEN 0 WHEN sku LIKE ? THEN 1 WHEN description LIKE ? THEN 2 ELSE 3 END",
                    ["%{$query}%", "%{$query}%", "%{$query}%"]
                );
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'description', 'sku', 'price', 'stock', 'expires_at']);
    }

    public function openConfirm(int $productId): void
    {
        $product = Product::query()->find($productId);

        if (! $product || ! $product->is_active) {
            Notification::make()
                ->title('Producto no disponible')
                ->warning()
                ->send();

            return;
        }

        if ($product->stock <= 0) {
            Notification::make()
                ->title('Sin stock disponible')
                ->body("{$product->name} no tiene stock. Ajusta inventario para continuar.")
                ->warning()
                ->actions([
                    NotificationAction::make('Editar')
                        ->url(route('filament.admin.resources.products.edit', $product->id))
                        ->openUrlInNewTab(),
                ])
                ->send();

            return;
        }

        $this->confirmProduct = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'expires_at' => optional($product->expires_at)?->toDateString(),
        ];

        $this->confirmQuantity = 1;
        $this->confirmPrice = (string) $product->price;
        $this->confirmNote = null;
        $this->showConfirmModal = true;
    }

    public function confirmSale(): void
    {
        if (empty($this->confirmProduct)) {
            Notification::make()
                ->title('Selecciona un producto')
                ->warning()
                ->send();

            return;
        }

        $productId = $this->confirmProduct['id'];
        $quantity = max(1, (int) $this->confirmQuantity);
        $unitPrice = (float) max(0, (float) $this->confirmPrice);
        $note = $this->confirmNote;
        $userId = Auth::id();

        try {
            DB::transaction(function () use ($userId, $productId, $quantity, $unitPrice, $note): void {
                /** @var Product|null $product */
                $product = Product::whereKey($productId)->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    throw new \RuntimeException('Producto no disponible.');
                }

                if ($product->stock < $quantity) {
                    throw new \RuntimeException("Stock insuficiente para {$product->name} (disponible {$product->stock}).");
                }

                $product->decrement('stock', $quantity);

                $sale = Sale::create([
                    'user_id' => $userId,
                    'items_count' => $quantity,
                    'total' => $unitPrice * $quantity,
                ]);

                $discount = max(0, ((float) $product->price - $unitPrice) * $quantity);

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_price' => $product->price,
                    'discount_amount' => $discount,
                    'note' => $note,
                ]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'sale',
                    'quantity' => -$quantity,
                    'note' => $note ?: 'Venta rápida',
                ]);
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo completar la venta')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->showConfirmModal = false;
        $this->confirmProduct = [];
        $this->confirmQuantity = 1;
        $this->confirmPrice = '';
        $this->confirmNote = null;
        $this->searchProducts();

        Notification::make()
            ->title('Venta registrada')
            ->success()
            ->send();
    }
}
