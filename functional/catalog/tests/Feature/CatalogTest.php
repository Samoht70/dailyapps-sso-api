<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Exceptions\IllegalApplicationTransition;
use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_new_application_as_a_draft(): void
    {
        $application = Application::factory()->create();

        $this->assertSame(ApplicationStatus::Draft, $application->status);
        $this->assertFalse($application->isPublished());
    }

    #[Test]
    public function it_publishes_a_draft_application(): void
    {
        $application = Application::factory()->draft()->create();

        $application->publish();

        $this->assertTrue($application->fresh()->isPublished());
    }

    #[Test]
    public function it_retires_a_published_application(): void
    {
        $application = Application::factory()->published()->create();

        $application->retire();

        $this->assertSame(ApplicationStatus::Retired, $application->fresh()->status);
    }

    #[Test]
    public function it_puts_a_retired_application_back_in_the_catalog(): void
    {
        $application = Application::factory()->retired()->create();

        $application->publish();

        $this->assertTrue($application->fresh()->isPublished());
    }

    #[Test]
    public function it_never_sends_a_retired_application_back_to_draft(): void
    {
        $application = Application::factory()->retired()->create();

        $this->expectException(IllegalApplicationTransition::class);

        $application->retire();
    }

    #[Test]
    public function it_refuses_to_publish_an_application_already_in_the_catalog(): void
    {
        $application = Application::factory()->published()->create();

        $this->expectException(IllegalApplicationTransition::class);

        $application->publish();
    }

    #[Test]
    public function it_scopes_to_the_applications_in_the_catalog(): void
    {
        Application::factory()->published()->count(2)->create();
        Application::factory()->draft()->create();
        Application::factory()->retired()->create();

        $this->assertSame(2, Application::query()->published()->count());
    }

    #[Test]
    public function it_keeps_an_application_slug_unique(): void
    {
        Application::factory()->create(['slug' => 'leaves']);

        $this->expectException(QueryException::class);

        Application::factory()->create(['slug' => 'leaves']);
    }

    #[Test]
    public function it_carries_the_roles_an_application_exposes(): void
    {
        $application = Application::factory()->create();
        ApplicationRole::factory()->for($application)->create(['key' => 'user']);
        ApplicationRole::factory()->for($application)->create(['key' => 'manager']);

        $this->assertSame(2, $application->roles()->count());
        $this->assertTrue($application->is($application->roles->first()->application));
    }

    #[Test]
    public function it_lets_two_applications_expose_the_same_role_key(): void
    {
        ApplicationRole::factory()->for(Application::factory())->create(['key' => 'manager']);
        ApplicationRole::factory()->for(Application::factory())->create(['key' => 'manager']);

        $this->assertSame(2, ApplicationRole::query()->where('key', 'manager')->count());
    }

    #[Test]
    public function it_refuses_the_same_role_key_twice_on_one_application(): void
    {
        $application = Application::factory()->create();
        ApplicationRole::factory()->for($application)->create(['key' => 'manager']);

        $this->expectException(QueryException::class);

        ApplicationRole::factory()->for($application)->create(['key' => 'manager']);
    }

    #[Test]
    public function it_binds_an_application_to_its_oauth_client(): void
    {
        $application = Application::factory()->create();

        $this->assertNotNull($application->oauthClient);
        $this->assertTrue($application->is($application->oauthClient->application));
    }

    #[Test]
    public function it_knows_which_applications_can_be_told_to_close_a_session(): void
    {
        $silent = Application::factory()->create();
        $reachable = Application::factory()->withBackchannelLogout()->create();

        $this->assertFalse($silent->receivesLogoutPush());
        $this->assertTrue($reachable->receivesLogoutPush());
    }
}
