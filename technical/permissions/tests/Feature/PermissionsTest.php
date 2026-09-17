<?php

namespace Technical\Permissions\Tests\Feature;

use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Technical\Permissions\Database\Seeders\PermissionsSeeder;
use Technical\Permissions\Enums\Permission;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_declares_every_permission_the_api_governs(): void
    {
        $this->seed(PermissionsSeeder::class);

        $this->assertSame(count(Permission::cases()), SpatiePermission::query()->count());

        foreach (Permission::cases() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission->value, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function it_declares_them_once_however_often_the_seeder_runs(): void
    {
        $this->seed(PermissionsSeeder::class);
        $this->seed(PermissionsSeeder::class);

        $this->assertSame(count(Permission::cases()), SpatiePermission::query()->count());
    }

    #[Test]
    public function it_attaches_a_permission_to_a_uuid_account(): void
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->admin()->create();

        $user->givePermissionTo(Permission::SuspendOrganization->value);

        $this->assertTrue($user->fresh()->can(Permission::SuspendOrganization->value));
        $this->assertFalse($user->fresh()->can(Permission::AttachLicense->value));
    }

    #[Test]
    public function it_refuses_a_permission_nobody_granted(): void
    {
        $this->seed(PermissionsSeeder::class);

        $this->assertFalse(User::factory()->create()->can(Permission::GrantApplicationAccess->value));
    }
}
