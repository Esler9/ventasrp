<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantAccount extends Model
{
    protected $fillable = [
        'restaurant_table_id',
        'sale_id',
        'opened_by_user_id',
        'settled_by_user_id',
        'status',
        'split_type',
        'label',
        'opened_at',
        'closed_at',
        'settled_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantAccountItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class);
    }
}
