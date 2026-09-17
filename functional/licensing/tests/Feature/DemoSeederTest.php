<?php

namespace Functional\Licensing\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Database\Seeders\LicensingSeeder;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LicensingSeeder::class);
    }

    #[Test]
    public function it_places_the_two_client_organizations_of_the_quickstart(): void
    {
        $this->assertNotNull(Organization::query()->where('name', 'Acme')->first());
        $this->assertNotNull(Organization::query()->where('name', 'Globex')->first());
    }

    #[Test]
    public function it_publishes_two_applications_each_exposing_its_two_roles(): void
    {
        foreach (['leaves', 'expenses'] as $slug) {
            $application = Application::query()->where('slug', $slug)->sole();

            $this->assertTrue($application->isPublished());
            $this->assertEqualsCanonicalizing(
                ['user', 'manager'],
                $application->roles->pluck('key')->all(),
            );
        }
    }

    #[Test]
    public function it_runs_the_leaves_licence_on_two_seats(): void
    {
        $license = $this->license('leaves');

        $this->assertSame(2, $license->seats);
        $this->assertTrue($license->isValid());
    }

    #[Test]
    public function it_leaves_the_expenses_licence_expired(): void
    {
        $this->assertFalse($this->license('expenses')->isValid());
    }

    #[Test]
    public function it_gives_one_acme_user_an_access_and_the_other_none(): void
    {
        $entitled = User::query()->where('email', 'camille@acme.test')->sole();
        $without = User::query()->where('email', 'dominique@acme.test')->sole();

        $this->assertSame(1, $entitled->applicationAccesses()->count());
        $this->assertSame(0, $without->applicationAccesses()->count());
    }

    #[Test]
    public function it_leaves_one_free_seat_on_the_leaves_licence(): void
    {
        $license = $this->license('leaves');

        $this->assertSame(1, $license->occupiedSeats());
        $this->assertTrue($license->hasFreeSeat());
    }

    #[Test]
    public function it_seeds_the_same_set_when_run_again(): void
    {
        $this->seed(LicensingSeeder::class);

        $this->assertSame(2, Organization::query()->count());
        $this->assertSame(2, Application::query()->count());
        $this->assertSame(2, License::query()->count());
        $this->assertSame(2, User::query()->count());
    }

    private function license(string $slug): License
    {
        return License::query()
            ->whereRelation('application', 'slug', $slug)
            ->whereRelation('organization', 'name', 'Acme')
            ->sole();
    }
}
