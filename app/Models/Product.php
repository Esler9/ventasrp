<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'price',
        'stock',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'expires_at' => 'date',
        'is_active' => 'bool',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
