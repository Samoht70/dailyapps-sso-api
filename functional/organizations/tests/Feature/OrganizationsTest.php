<?php

namespace Functional\Organizations\Tests\Feature;

use Functional\Organizations\Enums\OrganizationStatus;
use Functional\Organizations\Exceptions\IllegalOrganizationTransition;
use Functional\Organizations\Models\Organization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrganizationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_an_organization_a_uuid_primary_key(): void
    {
        $organization = Organization::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $organization->getKey(),
        );
    }

    #[Test]
    public function it_keeps_uuids_time_ordered(): void
    {
        $first = Organization::factory()->create()->getKey();
        $second = Organization::factory()->create()->getKey();

        $this->assertLessThan($second, $first);
    }

    #[Test]
    public function it_opens_a_new_organization_as_active(): void
    {
        $organization = Organization::factory()->create();

        $this->assertSame(OrganizationStatus::Active, $organization->status);
        $this->assertTrue($organization->isActive());
        $this->assertNull($organization->suspended_at);
    }

    #[Test]
    public function it_suspends_a_client_organization_and_stamps_the_moment(): void
    {
        $organization = Organization::factory()->client()->create();

        $organization->suspend();

        $this->assertTrue($organization->isSuspended());
        $this->assertNotNull($organization->suspended_at);
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->getKey(),
            'status' => OrganizationStatus::Suspended->value,
        ]);
    }

    #[Test]
    public function it_clears_the_suspension_stamp_when_an_organization_comes_back(): void
    {
        $organization = Organization::factory()->suspended()->create();

        $organization->activate();

        $this->assertTrue($organization->isActive());
        $this->assertNull($organization->suspended_at);
    }

    #[Test]
    public function it_refuses_to_suspend_the_operator_organization(): void
    {
        $operator = Organization::factory()->operator()->create();

        $this->expectException(IllegalOrganizationTransition::class);

        $operator->suspend();
    }

    #[Test]
    public function it_refuses_a_transition_that_does_not_move_the_status(): void
    {
        $organization = Organization::factory()->client()->create();

        $this->expectException(IllegalOrganizationTransition::class);

        $organization->activate();
    }

    #[Test]
    public function it_holds_exactly_one_operator_organization(): void
    {
        Organization::factory()->operator()->create();

        $this->expectException(QueryException::class);

        Organization::factory()->operator()->create();
    }

    #[Test]
    public function it_admits_any_number_of_client_organizations(): void
    {
        Organization::factory()->client()->count(3)->create();

        $this->assertSame(3, Organization::query()->count());
    }

    #[Test]
    public function it_scopes_to_the_organizations_still_active(): void
    {
        Organization::factory()->client()->count(2)->create();
        Organization::factory()->suspended()->create();

        $this->assertSame(2, Organization::query()->active()->count());
    }
}
