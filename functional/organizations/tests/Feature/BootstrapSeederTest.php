<?php

namespace Functional\Organizations\Tests\Feature;

use Functional\Organizations\Database\Seeders\OrganizationsSeeder;
use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Technical\Permissions\Enums\Permission;
use Tests\TestCase;

class BootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_raises_the_operator_organization_the_platform_cannot_start_without(): void
    {
        $this->seed(OrganizationsSeeder::class);

        $operator = Organization::query()->where('kind', OrganizationKind::Operator)->sole();

        $this->assertSame('DailyApps', $operator->name);
        $this->assertTrue($operator->isActive());
    }

    #[Test]
    public function it_opens_an_administration_account_able_to_sign_in(): void
    {
        $this->seed(OrganizationsSeeder::class);

        $administrator = User::query()->where('email', 'admin@dailyapps.test')->sole();

        $this->assertSame(UserStatus::Active, $administrator->status);
        $this->assertSame(OrganizationRole::Admin, $administrator->organization_role);
        $this->assertTrue($administrator->canAuthenticate());
    }

    #[Test]
    public function it_gives_the_administration_account_every_permission_of_the_api(): void
    {
        $this->seed(OrganizationsSeeder::class);

        $administrator = User::query()->where('email', 'admin@dailyapps.test')->sole();

        foreach (Permission::cases() as $permission) {
            $this->assertTrue($administrator->can($permission->value), $permission->value);
        }
    }

    #[Test]
    public function it_leaves_the_bootstrap_untouched_when_run_again(): void
    {
        $this->seed(OrganizationsSeeder::class);
        $this->seed(OrganizationsSeeder::class);

        $this->assertSame(1, Organization::query()->count());
        $this->assertSame(1, User::query()->count());
    }
}
