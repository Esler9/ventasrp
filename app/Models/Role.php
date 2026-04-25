<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'key',
        'label',
        'permissions',
        'default_home',
        'can_switch_branch',
    ];

    protected $casts = [
        'permissions'       => 'array',
        'can_switch_branch' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function hasWildcard(): bool
    {
        return in_array('*', (array) $this->permissions, true);
    }
}
