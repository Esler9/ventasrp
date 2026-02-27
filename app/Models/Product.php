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
}
