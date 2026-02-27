<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantAccountItem extends Model
{
    protected $fillable = [
        'restaurant_account_id',
        'restaurant_order_id',
        'product_id',
        'added_by_user_id',
        'quantity',
        'unit_price',
        'note',
        'kitchen_status',
        'sent_at',
        'started_at',
        'ready_at',
        'served_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'sent_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(RestaurantAccount::class, 'restaurant_account_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
