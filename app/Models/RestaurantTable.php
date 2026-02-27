<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_takeaway',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_takeaway' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(RestaurantAccount::class);
    }
}
