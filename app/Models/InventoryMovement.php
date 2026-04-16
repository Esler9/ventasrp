<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',          // sale | purchase | adjustment | initial
        'quantity',      // negativo en salidas, positivo en entradas
        'unit_cost',     // CPP vigente (ventas) o costo de compra (ingresos)
        'reference_type',// sale | purchase | null
        'reference_id',  // FK al registro origen
        'stock_before',
        'stock_after',
        'note',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'unit_cost'    => 'decimal:4',
        'reference_id' => 'integer',
        'stock_before' => 'integer',
        'stock_after'  => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
