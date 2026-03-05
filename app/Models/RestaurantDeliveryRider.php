<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantDeliveryRider extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'is_active',
        'is_available',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(RestaurantAccount::class, 'delivery_rider_id');
    }
}

