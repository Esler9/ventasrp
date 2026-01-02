<?php

namespace App\Filament\Pages;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
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

    public ?string $search = '';

    /**
     * @var array<int, int> productId => quantity
     */
    public array $cart = [];

    /**
     * @var array<int, int> productId => desired quantity in search list
     */
    public array $quantities = [];

    public Collection $results;

    public function mount(): void
    {
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
        $query = trim((string) $this->search);

        $this->results = Product::query()
            ->where('is_active', true)
            ->when($query !== '', function ($builder) use ($query) {
                $builder
                    ->where(function ($subQuery) use ($query) {
                        $subQuery
                            ->where('name', 'like', '%' . $query . '%')
                            ->orWhere('sku', 'like', '%' . $query . '%');
                    });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'price', 'stock']);

        foreach ($this->results as $result) {
            $this->quantities[$result->id] = $this->quantities[$result->id] ?? 1;
        }
    }

    public function addToCart(int $productId): void
    {
        $qty = max(1, (int) ($this->quantities[$productId] ?? 1));
        $this->cart[$productId] = ($this->cart[$productId] ?? 0) + $qty;

        Notification::make()
            ->title('Producto agregado')
            ->body("Cantidad: {$this->cart[$productId]}")
            ->success()
            ->send();
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function updateCartQuantity(int $productId, $value): void
    {
        $quantity = max(1, (int) $value);
        $this->cart[$productId] = $quantity;
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function getCartItemsProperty(): Collection
    {
        if (empty($this->cart)) {
            return collect();
        }

        return Product::whereIn('id', array_keys($this->cart))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock']);
    }

    public function getCartTotalProperty(): float
    {
        return $this->cartItems->sum(function (Product $product) {
            $qty = $this->cart[$product->id] ?? 0;

            return (float) $product->price * $qty;
        });
    }

    public function confirmSale(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('El carrito está vacío')
                ->warning()
                ->send();

            return;
        }

        $userId = Auth::id();

        try {
            DB::transaction(function () use ($userId): void {
                $itemsData = [];
                $itemsCount = 0;
                $total = 0;

                foreach ($this->cart as $productId => $qty) {
                    /** @var Product|null $product */
                    $product = Product::whereKey($productId)->lockForUpdate()->first();

                    if (! $product || ! $product->is_active) {
                        throw new \RuntimeException('Producto no disponible.');
                    }

                    $quantity = max(1, (int) $qty);

                    if ($product->stock < $quantity) {
                        throw new \RuntimeException("Stock insuficiente para {$product->name} (disponible {$product->stock}).");
                    }

                    $product->decrement('stock', $quantity);

                    $itemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                    ];

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'type' => 'sale',
                        'quantity' => -$quantity,
                        'note' => 'Venta rápida',
                    ]);

                    $itemsCount += $quantity;
                    $total += (float) $product->price * $quantity;
                }

                $sale = Sale::create([
                    'user_id' => $userId,
                    'items_count' => $itemsCount,
                    'total' => $total,
                ]);

                foreach ($itemsData as $item) {
                    $sale->items()->create($item);
                }
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo completar la venta')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->clearCart();
        $this->searchProducts();

        Notification::make()
            ->title('Venta registrada')
            ->success()
            ->send();
    }
}
