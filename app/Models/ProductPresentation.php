<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPresentation extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'factor',
        'price',
        'is_active',
    ];

    protected $casts = [
        'factor' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
