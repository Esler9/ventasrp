<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'app_icon_color',
        'app_logo_path',
        'primary_color',
        'secondary_color',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'app_icon_color' => '#f59e0b',
            'primary_color' => '#0773A4',
            'secondary_color' => '#0EA5E9',
        ]);
    }

    public function logoUrl(): ?string
    {
        $path = trim((string) $this->app_logo_path);

        return $path === '' ? null : asset($path);
    }
}
