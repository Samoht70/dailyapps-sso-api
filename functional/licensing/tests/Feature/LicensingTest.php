<?php

namespace Functional\Licensing\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LicensingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_holds_a_licence_running_today_as_valid(): void
    {
        $license = License::factory()->valid()->create();

        $this->assertTrue($license->isValid());
        $this->assertSame(1, License::query()->valid()->count());
    }

    #[Test]
    public function it_holds_a_licence_without_an_end_date_as_valid(): void
    {
        $license = License::factory()->perpetual()->create();

        $this->assertTrue($license->isValid());
    }

    #[Test]
    public function it_drops_a_licence_whose_end_date_has_passed(): void
    {
        $license = License::factory()->expired()->create();

        $this->assertFalse($license->isValid());
        $this->assertSame(0, License::query()->valid()->count());
    }

    #[Test]
    public function it_drops_a_licence_that_has_not_started_yet(): void
    {
        $license = License::factory()->create([
            'starts_on' => now()->addWeek()->toDateString(),
        ]);

        $this->assertFalse($license->isValid());
    }

    #[Test]
    public function it_drops_a_licence_held_by_a_suspended_organization(): void
    {
        $organization = Organization::factory()->client()->create();
        $license = License::factory()->for($organization)->valid()->create();

        $organization->suspend();

        $this->assertFalse($license->isValid());
    }

    #[Test]
    public function it_drops_a_licence_on_an_application_out_of_the_catalog(): void
    {
        $application = Application::factory()->published()->create();
        $license = License::factory()->for($application)->valid()->create();

        $application->retire();

        $this->assertFalse($license->isValid());
    }

    #[Test]
    public function it_drops_a_licence_on_an_application_still_in_draft(): void
    {
        $license = License::factory()
            ->for(Application::factory()->draft())
            ->valid()
            ->create();

        $this->assertFalse($license->isValid());
    }

    #[Test]
    public function it_holds_one_licence_per_organization_and_application(): void
    {
        $license = License::factory()->create();

        $this->expectException(QueryException::class);

        License::factory()->create([
            'organization_id' => $license->organization_id,
            'application_id' => $license->application_id,
        ]);
    }

    #[Test]
    public function it_refuses_a_licence_without_a_single_seat(): void
    {
        $this->expectException(QueryException::class);

        License::factory()->create(['seats' => 0]);
    }

    #[Test]
    public function it_counts_the_seats_the_organization_occupies(): void
    {
        $organization = Organization::factory()->client()->create();
        $application = Application::factory()->published()->create();
        $license = License::factory()->for($organization)->for($application)->create(['seats' => 3]);

        ApplicationAccess::factory()
            ->for(User::factory()->for($organization)->active())
            ->for($application)
            ->create();
        ApplicationAccess::factory()
            ->for(User::factory()->for($organization)->invited())
            ->for($application)
            ->create();

        $this->assertSame(2, $license->occupiedSeats());
        $this->assertTrue($license->hasFreeSeat());
    }

    #[Test]
    public function it_frees_the_seat_of_a_disabled_account(): void
    {
        $organization = Organization::factory()->client()->create();
        $application = Application::factory()->published()->create();
        $license = License::factory()->for($organization)->for($application)->create(['seats' => 1]);

        ApplicationAccess::factory()
            ->for(User::factory()->for($organization)->disabled())
            ->for($application)
            ->create();

        $this->assertSame(0, $license->occupiedSeats());
        $this->assertTrue($license->hasFreeSeat());
    }

    #[Test]
    public function it_counts_no_seat_for_another_organization(): void
    {
        $application = Application::factory()->published()->create();
        $license = License::factory()
            ->for(Organization::factory()->client())
            ->for($application)
            ->create(['seats' => 1]);

        ApplicationAccess::factory()
            ->for(User::factory()->active())
            ->for($application)
            ->create();

        $this->assertSame(0, $license->occupiedSeats());
    }

    #[Test]
    public function it_sees_a_full_licence_as_having_no_free_seat(): void
    {
        $organization = Organization::factory()->client()->create();
        $application = Application::factory()->published()->create();
        $license = License::factory()->for($organization)->for($application)->exhausted()->create();

        ApplicationAccess::factory()
            ->for(User::factory()->for($organization)->active())
            ->for($application)
            ->create();

        $this->assertFalse($license->hasFreeSeat());
    }

    #[Test]
    public function it_grants_an_access_only_once_per_user_and_application(): void
    {
        $access = ApplicationAccess::factory()->create();

        $this->expectException(QueryException::class);

        ApplicationAccess::factory()->create([
            'user_id' => $access->user_id,
            'application_id' => $access->application_id,
        ]);
    }

    #[Test]
    public function it_remembers_who_granted_an_access(): void
    {
        $granter = User::factory()->admin()->create();

        $access = ApplicationAccess::factory()->create(['granted_by_id' => $granter->getKey()]);

        $this->assertTrue($granter->is($access->grantedBy));
    }

    #[Test]
    public function it_carries_the_application_roles_an_access_was_given(): void
    {
        $application = Application::factory()->published()->create();
        $access = ApplicationAccess::factory()->for($application)->create();
        $roles = ApplicationRole::factory()->for($application)->count(2)->create();

        $access->roles()->attach($roles->modelKeys());

        $this->assertSame(2, $access->roles()->count());
    }

    #[Test]
    public function it_reaches_a_user_accesses_from_the_user(): void
    {
        $user = User::factory()->active()->create();
        ApplicationAccess::factory()->for($user)->count(2)->create();

        $this->assertSame(2, $user->applicationAccesses()->count());
    }
}
