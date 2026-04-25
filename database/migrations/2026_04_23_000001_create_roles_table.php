<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->json('permissions');
            $table->string('default_home')->default('/admin');
            $table->boolean('can_switch_branch')->default(false);
            $table->timestamps();
        });

        // Seed from config/access.php so the DB matches the current static definition
        $roles = config('access.roles', []);
        $rolePerms = config('access.role_permissions', []);
        $now = now();

        foreach ($roles as $key => $meta) {
            DB::table('roles')->updateOrInsert(
                ['key' => $key],
                [
                    'label'             => $meta['label'] ?? $key,
                    'permissions'       => json_encode($rolePerms[$key] ?? []),
                    'default_home'      => $meta['default_home'] ?? '/admin',
                    'can_switch_branch' => (bool) ($meta['can_switch_branch'] ?? false),
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
