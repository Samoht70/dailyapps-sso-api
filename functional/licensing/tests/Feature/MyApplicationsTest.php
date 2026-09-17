<?php

namespace Functional\Licensing\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();
    }

    #[Test]
    public function it_turns_away_a_caller_without_a_token(): void
    {
        $this->getJson('/me/applications')->assertUnauthorized();
    }

    #[Test]
    public function it_answers_exactly_the_applications_the_caller_can_reach(): void
    {
        $reachable = $this->application('leaves', licensed: true, granted: true);
        $this->application('expenses', licensed: true, granted: false);
        $this->application('payroll', licensed: false, granted: true);

        $slugs = $this->callAsAccount()->assertOk()->json('data.*.slug');

        $this->assertSame([$reachable->slug], $slugs);
    }

    #[Test]
    public function it_answers_an_empty_list_rather_than_an_error_when_nothing_is_granted(): void
    {
        $this->application('leaves', licensed: true, granted: false);

        $this->callAsAccount()->assertOk()->assertExactJson(['data' => []]);
    }

    #[Test]
    public function it_drops_an_application_whose_licence_has_expired(): void
    {
        $application = Application::factory()->published()->create(['slug' => 'expenses']);
        License::factory()->for($this->organization)->for($application)->expired()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        $this->callAsAccount()->assertOk()->assertExactJson(['data' => []]);
    }

    #[Test]
    public function it_drops_an_application_that_left_the_catalog(): void
    {
        $application = $this->application('leaves', licensed: true, granted: true);
        $application->retire();

        $this->callAsAccount()->assertOk()->assertExactJson(['data' => []]);
    }

    #[Test]
    public function it_answers_nothing_to_an_account_of_a_suspended_organization(): void
    {
        $this->application('leaves', licensed: true, granted: true);
        $this->organization->suspend();

        $this->callAsAccount()->assertOk()->assertExactJson(['data' => []]);
    }

    #[Test]
    public function it_answers_nothing_to_a_disabled_account(): void
    {
        $this->application('leaves', licensed: true, granted: true);
        $this->account->state()->disable();

        $this->callAsAccount()->assertOk()->assertExactJson(['data' => []]);
    }

    #[Test]
    public function it_carries_what_a_portal_needs_to_show_a_tile(): void
    {
        $this->application('leaves', licensed: true, granted: true);

        $this->callAsAccount()->assertOk()->assertJsonStructure([
            'data' => [['slug', 'name', 'logo_url', 'home_url']],
        ]);
    }

    #[Test]
    public function it_reflects_a_freshly_granted_access_on_the_very_next_call(): void
    {
        $application = $this->application('leaves', licensed: true, granted: false);

        $this->callAsAccount()->assertExactJson(['data' => []]);

        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        $this->assertSame(['leaves'], $this->callAsAccount()->json('data.*.slug'));
    }

    #[Test]
    public function it_reflects_a_revoked_access_on_the_very_next_call(): void
    {
        $application = $this->application('leaves', licensed: true, granted: true);

        $this->assertSame(['leaves'], $this->callAsAccount()->json('data.*.slug'));

        ApplicationAccess::query()
            ->where('user_id', $this->account->getKey())
            ->where('application_id', $application->getKey())
            ->delete();

        $this->callAsAccount()->assertExactJson(['data' => []]);
    }

    #[Test]
    public function it_never_answers_the_applications_of_another_account(): void
    {
        $this->application('leaves', licensed: true, granted: true);
        $stranger = User::factory()->active()->create();

        Passport::actingAs($stranger);

        $this->getJson('/me/applications')->assertOk()->assertExactJson(['data' => []]);
    }

    private function callAsAccount(): TestResponse
    {
        Passport::actingAs($this->account->fresh());

        return $this->getJson('/me/applications');
    }

    private function application(string $slug, bool $licensed, bool $granted): Application
    {
        $application = Application::factory()->published()->create(['slug' => $slug]);

        if ($licensed) {
            License::factory()->for($this->organization)->for($application)->valid()->create();
        }

        if ($granted) {
            ApplicationAccess::factory()->for($this->account)->for($application)->create();
        }

        return $application;
    }
}
