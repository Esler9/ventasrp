<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'pin',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->roleKey() === 'owner_manager';
    }

    public function isSeller(): bool
    {
        return $this->roleKey() === 'seller_cashier';
    }

    public function roleKey(): string
    {
        $role = (string) $this->role;
        $aliases = config('access.role_aliases', []);
        $roles = config('access.roles', []);

        $resolved = $aliases[$role] ?? $role;

        if (array_key_exists($resolved, $roles)) {
            return $resolved;
        }

        return 'seller_cashier';
    }

    public function roleLabel(): string
    {
        $label = config('access.roles.' . $this->roleKey() . '.label');

        return is_string($label) ? $label : 'Usuario';
    }

    /**
     * @return array<int, string>
     */
    public function permissions(): array
    {
        $permissions = config('access.role_permissions.' . $this->roleKey(), []);

        return array_values(array_unique(array_map('strval', $permissions)));
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions();

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function canSwitchBranch(): bool
    {
        return (bool) config('access.roles.' . $this->roleKey() . '.can_switch_branch', false);
    }

    public function defaultHomeRoute(): string
    {
        $route = config('access.roles.' . $this->roleKey() . '.default_home', '/admin');

        return is_string($route) ? $route : '/admin';
    }

    /**
     * @return array<int, string>
     */
    public static function availableRoles(): array
    {
        return array_keys(config('access.roles', []));
    }
}
