<?php

namespace Functional\Organizations\Tests\Feature;

use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Enums\OrganizationStatus;
use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Models\SsoSession;
use Technical\Permissions\Database\Seeders\PermissionsSeeder;
use Technical\Permissions\Enums\Permission;
use Tests\TestCase;

class TransitionEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $acme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->acme = Organization::factory()->client()->create(['name' => 'Acme']);
    }

    #[Test]
    public function it_suspends_an_organization_for_an_operator_holding_the_permission(): void
    {
        Passport::actingAs($this->operator());

        $this->postJson("/organizations/{$this->acme->getKey()}/suspend")
            ->assertOk()
            ->assertJsonPath('data.status', OrganizationStatus::Suspended->value);

        $this->assertTrue($this->acme->fresh()->isSuspended());
    }

    #[Test]
    public function it_revokes_the_live_sessions_of_a_suspended_organization(): void
    {
        $member = User::factory()->for($this->acme)->active()->create();
        $session = SsoSession::factory()->for($member)->create();

        Passport::actingAs($this->operator());
        $this->postJson("/organizations/{$this->acme->getKey()}/suspend")->assertOk();

        $this->assertFalse($session->fresh()->isAlive());
    }

    #[Test]
    public function it_refuses_the_suspension_to_an_administrator_of_the_organization_itself(): void
    {
        Passport::actingAs(User::factory()->for($this->acme)->admin()->active()->create());

        $this->postJson("/organizations/{$this->acme->getKey()}/suspend")->assertForbidden();

        $this->assertTrue($this->acme->fresh()->isActive());
    }

    #[Test]
    public function it_refuses_to_suspend_the_operator_organization(): void
    {
        $operator = $this->operator();

        Passport::actingAs($operator);

        $this->postJson("/organizations/{$operator->organization_id}/suspend")
            ->assertStatus(409)
            ->assertJsonPath('code', 'illegal_transition');
    }

    #[Test]
    public function it_brings_a_suspended_organization_back(): void
    {
        $this->acme->suspend();

        Passport::actingAs($this->operator());

        $this->postJson("/organizations/{$this->acme->getKey()}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', OrganizationStatus::Active->value);
    }

    #[Test]
    public function it_disables_an_account_for_its_organization_administrator(): void
    {
        $administrator = User::factory()->for($this->acme)->admin()->active()->create();
        $member = User::factory()->for($this->acme)->active()->create();

        Passport::actingAs($administrator);

        $this->postJson("/users/{$member->getKey()}/disable")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::Disabled->value);
    }

    #[Test]
    public function it_refuses_to_disable_an_account_of_another_organization(): void
    {
        $administrator = User::factory()->for($this->acme)->admin()->active()->create();
        $stranger = User::factory()->active()->create();

        Passport::actingAs($administrator);

        $this->postJson("/users/{$stranger->getKey()}/disable")->assertForbidden();
        $this->assertSame(UserStatus::Active, $stranger->fresh()->status);
    }

    #[Test]
    public function it_answers_the_last_administrator_refusal_with_its_machine_code(): void
    {
        $administrator = User::factory()->for($this->acme)->admin()->active()->create();

        Passport::actingAs($administrator);

        $this->postJson("/users/{$administrator->getKey()}/disable")
            ->assertStatus(409)
            ->assertJsonPath('code', 'last_admin');
    }

    #[Test]
    public function it_brings_a_disabled_account_back(): void
    {
        $administrator = User::factory()->for($this->acme)->admin()->active()->create();
        $member = User::factory()->for($this->acme)->disabled()->create();

        Passport::actingAs($administrator);

        $this->postJson("/users/{$member->getKey()}/enable")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::Active->value);
    }

    #[Test]
    public function it_turns_away_a_caller_without_a_token(): void
    {
        $this->postJson("/organizations/{$this->acme->getKey()}/suspend")->assertUnauthorized();
    }

    private function operator(): User
    {
        $organization = Organization::query()->firstOrCreate(
            ['kind' => OrganizationKind::Operator],
            ['name' => 'DailyApps'],
        );

        $account = User::factory()->for($organization)->admin()->active()->create();
        $account->syncPermissions(Permission::names());

        return $account->fresh();
    }
}
