<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_presentation_id',
        'presentation_name',
        'presentation_factor',
        'presentation_price',
        'presentation_quantity',
        'quantity',
        'unit_price',
        'original_price',
        'unit_cost',     // CPP del producto al momento de la venta (para COGS)
        'discount_amount',
        'note',
    ];

    protected $casts = [
        'quantity'              => 'integer',
        'presentation_factor'   => 'integer',
        'presentation_quantity' => 'integer',
        'presentation_price'    => 'decimal:2',
        'unit_price'            => 'decimal:2',
        'original_price'        => 'decimal:2',
        'unit_cost'             => 'decimal:4',
        'discount_amount'       => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
