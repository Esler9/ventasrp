<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantAccountItem extends Model
{
    protected $fillable = [
        'restaurant_account_id',
        'restaurant_order_id',
        'product_id',
        'added_by_user_id',
        'canceled_by_user_id',
        'quantity',
        'unit_price',
        'note',
        'kitchen_status',
        'sent_at',
        'started_at',
        'ready_at',
        'served_at',
        'canceled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'sent_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'canceled_at' => 'datetime',
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

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by_user_id');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(RestaurantItemConsumption::class, 'restaurant_account_item_id');
    }
}
