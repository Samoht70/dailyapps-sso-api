<?php

namespace Technical\Permissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\PermissionRegistrar;
use Technical\Permissions\Enums\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permission::cases() as $permission) {
            $this->command?->line("Déclaration de la permission {$permission->value}");

            SpatiePermission::findOrCreate($permission->value, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->comment(count(Permission::cases()).' permissions de l\'api déclarées.');
    }
}
