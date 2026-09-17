<?php

namespace Functional\Organizations\Tests\Feature;

use Functional\Organizations\Models\Organization;
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
}
