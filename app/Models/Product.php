<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\ProductPresentation;

class Product extends Model
{
    protected $fillable = [
        // NOTE: cost_price se actualiza automáticamente por Costo Promedio Ponderado
        // al recibir compras. No editar manualmente salvo ajuste inicial.
        'category_id',
        'name',
        'unit_label',
        'description',
        'photo',
        'sku',
        'price',
        'cost_price',
        'stock',
        'stock_alert',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_alert' => 'integer',
        'expires_at' => 'date',
        'is_active' => 'bool',
    ];

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function recipe(): HasOne
    {
        return $this->hasOne(ProductRecipe::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Calcula y aplica el Costo Promedio Ponderado (CPP) al recibir una compra.
     *
     * Fórmula: CPP_nuevo = (stock_actual × costo_actual + qty_comprada × costo_compra)
     *                       / (stock_actual + qty_comprada)
     *
     * No llama save() — el caller debe guardar el modelo después de ajustar stock.
     */
    public function applyWeightedAverageCost(int $qty, float $purchaseCost): void
    {
        $currentStock = max(0, (int) $this->stock);
        $currentCost  = (float) $this->cost_price;

        if ($currentStock <= 0 || $currentCost <= 0) {
            // Sin stock previo (o costo cero): el costo de compra es el nuevo CPP
            $this->cost_price = round($purchaseCost, 4);
        } else {
            $this->cost_price = round(
                ($currentStock * $currentCost + $qty * $purchaseCost) / ($currentStock + $qty),
                4
            );
        }
    }
}
