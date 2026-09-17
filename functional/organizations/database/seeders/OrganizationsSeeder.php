<?php

namespace Functional\Organizations\Database\Seeders;

use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Technical\Permissions\Database\Seeders\PermissionsSeeder;
use Technical\Permissions\Enums\Permission;

class OrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

        $this->command?->line('Amorçage de l\'organisation exploitante DailyApps');

        $operator = Organization::query()->firstOrCreate(
            ['kind' => OrganizationKind::Operator],
            ['name' => 'DailyApps'],
        );

        $this->command?->line('Amorçage du compte d\'administration de la plateforme');

        $administrator = User::query()->firstOrCreate(
            ['email' => 'admin@dailyapps.test'],
            [
                'organization_id' => $operator->getKey(),
                'name' => 'Administration DailyApps',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
                'organization_role' => OrganizationRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        $administrator->syncPermissions(Permission::names());

        $this->command?->comment('Organisation exploitante et compte d\'amorçage en place.');
    }
}
