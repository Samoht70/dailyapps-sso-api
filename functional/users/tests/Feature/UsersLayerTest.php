<?php

namespace Functional\Users\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersLayerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_a_user_built_by_the_layer_factory(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->getKey(), 'email' => $user->email]);
    }

    #[Test]
    public function it_binds_the_layer_user_model_to_the_default_auth_provider(): void
    {
        $this->assertSame(User::class, config('auth.providers.users.model'));
    }

    #[Test]
    public function it_gives_a_user_a_uuid_primary_key(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $user->getKey(),
        );
    }

    #[Test]
    public function it_attaches_every_user_to_exactly_one_organization(): void
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->for($organization)->create();

        $this->assertTrue($organization->is($user->organization));
        $this->assertTrue($user->is($organization->users->first()));
    }

    #[Test]
    public function it_refuses_a_user_without_an_organization(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create(['organization_id' => null]);
    }

    #[Test]
    public function it_keeps_an_email_unique_across_every_organization(): void
    {
        User::factory()->create(['email' => 'shared@dailyapps.test']);

        $this->expectException(QueryException::class);

        User::factory()->create(['email' => 'shared@dailyapps.test']);
    }

    #[Test]
    public function it_leaves_an_invited_account_without_a_password(): void
    {
        $user = User::factory()->invited()->create();

        $this->assertNull($user->password);
        $this->assertSame(UserStatus::Invited, $user->status);
    }

    #[Test]
    public function it_tells_an_organization_administrator_from_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->assertTrue($admin->isOrganizationAdmin());
        $this->assertFalse($member->isOrganizationAdmin());
        $this->assertSame(OrganizationRole::Member, $member->organization_role);
    }
}
