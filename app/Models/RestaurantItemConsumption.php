<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantItemConsumption extends Model
{
    protected $fillable = [
        'restaurant_account_item_id',
        'product_id',
        'quantity',
        'source_type',
        'reversed_by_user_id',
        'reversed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reversed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(RestaurantAccountItem::class, 'restaurant_account_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }
}
