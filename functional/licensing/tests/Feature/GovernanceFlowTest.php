<?php

namespace Functional\Licensing\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Actions\AttachLicense;
use Functional\Licensing\Actions\GrantApplicationAccess;
use Functional\Licensing\Actions\RevokeApplicationAccess;
use Functional\Licensing\Actions\RevokeLicense;
use Functional\Licensing\Exceptions\RoleFromAnotherApplication;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Actions\ActivateOrganization;
use Functional\Organizations\Actions\SuspendOrganization;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Tests\TestCase;

class GovernanceFlowTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $account;

    private Application $leaves;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();
        $this->leaves = Application::factory()->published()->create(['slug' => 'leaves']);
    }

    #[Test]
    public function it_opens_an_application_to_an_account_as_soon_as_the_access_is_granted(): void
    {
        $this->attachLicense();

        $this->assertSame([], $this->reachableApplications());

        app(GrantApplicationAccess::class)($this->account, $this->leaves);

        $this->assertSame(['leaves'], $this->reachableApplications());
    }

    #[Test]
    public function it_closes_the_application_as_soon_as_the_access_is_revoked(): void
    {
        $this->attachLicense();
        $access = app(GrantApplicationAccess::class)($this->account, $this->leaves);

        app(RevokeApplicationAccess::class)($access);

        $this->assertSame([], $this->reachableApplications());
    }

    #[Test]
    public function it_closes_the_application_when_the_licence_is_revoked_without_touching_the_access(): void
    {
        $license = $this->attachLicense();
        app(GrantApplicationAccess::class)($this->account, $this->leaves);

        app(RevokeLicense::class)($license);

        $this->assertSame([], $this->reachableApplications());
        $this->assertSame(1, ApplicationAccess::query()->count());
    }

    #[Test]
    public function it_closes_every_application_when_the_organization_is_suspended(): void
    {
        $this->attachLicense();
        app(GrantApplicationAccess::class)($this->account, $this->leaves);

        app(SuspendOrganization::class)($this->organization);

        $this->assertSame([], $this->reachableApplications());
    }

    #[Test]
    public function it_reopens_them_when_the_organization_comes_back_without_granting_anything_anew(): void
    {
        $this->attachLicense();
        app(GrantApplicationAccess::class)($this->account, $this->leaves);
        app(SuspendOrganization::class)($this->organization);

        app(ActivateOrganization::class)($this->organization);

        $this->assertSame(['leaves'], $this->reachableApplications());
    }

    #[Test]
    public function it_refuses_a_role_that_belongs_to_another_application(): void
    {
        $this->attachLicense();
        $elsewhere = ApplicationRole::factory()
            ->for(Application::factory()->published())
            ->create(['key' => 'manager']);

        $this->expectException(RoleFromAnotherApplication::class);

        app(GrantApplicationAccess::class)($this->account, $this->leaves, roleKeys: [$elsewhere->key]);
    }

    #[Test]
    public function it_attaches_the_roles_of_the_application_being_granted(): void
    {
        $this->attachLicense();
        ApplicationRole::factory()->for($this->leaves)->create(['key' => 'manager']);

        $access = app(GrantApplicationAccess::class)($this->account, $this->leaves, roleKeys: ['manager']);

        $this->assertSame(['manager'], $access->roles->pluck('key')->all());
    }

    #[Test]
    public function it_journals_every_governance_write(): void
    {
        $administrator = User::factory()->for($this->organization)->admin()->active()->create();

        $license = $this->attachLicense($administrator);
        $access = app(GrantApplicationAccess::class)($this->account, $this->leaves, $administrator);
        app(RevokeApplicationAccess::class)($access, $administrator);
        app(RevokeLicense::class)($license, $administrator);
        app(SuspendOrganization::class)($this->organization, $administrator);

        foreach ([
            SecurityEventType::LicenseAttached,
            SecurityEventType::AccessGranted,
            SecurityEventType::AccessRevoked,
            SecurityEventType::LicenseRevoked,
            SecurityEventType::OrganizationSuspended,
        ] as $type) {
            $this->assertDatabaseHas('security_events', [
                'type' => $type->value,
                'actor_id' => $administrator->getKey(),
            ]);
        }
    }

    #[Test]
    public function it_renews_a_licence_by_moving_its_end_date_rather_than_opening_a_second(): void
    {
        $first = $this->attachLicense();
        $renewed = $this->attachLicense(endsOn: now()->addYears(2)->toDateString());

        $this->assertTrue($first->is($renewed));
        $this->assertSame(1, License::query()->count());
    }

    /** @return list<string> */
    private function reachableApplications(): array
    {
        Passport::actingAs($this->account->fresh());

        return $this->getJson('/me/applications')->assertOk()->json('data.*.slug');
    }

    private function attachLicense(?User $by = null, ?string $endsOn = null): License
    {
        return app(AttachLicense::class)(
            $this->organization,
            $this->leaves,
            now()->subMonth()->toDateString(),
            $endsOn ?? now()->addYear()->toDateString(),
            5,
            $by,
        );
    }
}
