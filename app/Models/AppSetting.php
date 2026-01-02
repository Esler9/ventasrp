<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'app_icon_color',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'app_icon_color' => '#f59e0b',
        ]);
    }
}
