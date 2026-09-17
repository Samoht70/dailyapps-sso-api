<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Application;
use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Models\Client;
use Technical\Permissions\Database\Seeders\PermissionsSeeder;
use Technical\Permissions\Enums\Permission;
use Tests\TestCase;

class ApplicationLifecycleEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function it_publishes_a_draft_for_an_operator_holding_the_permission(): void
    {
        $application = Application::factory()->draft()->create();

        Passport::actingAs($this->operator());

        $this->postJson("/applications/{$application->getKey()}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Published->value);
    }

    #[Test]
    public function it_retires_a_published_application(): void
    {
        $application = Application::factory()->published()->create();

        Passport::actingAs($this->operator());

        $this->postJson("/applications/{$application->getKey()}/retire")
            ->assertOk()
            ->assertJsonPath('data.status', ApplicationStatus::Retired->value);
    }

    #[Test]
    public function it_answers_an_illegal_transition_with_its_machine_code(): void
    {
        $application = Application::factory()->retired()->create();

        Passport::actingAs($this->operator());

        $this->postJson("/applications/{$application->getKey()}/retire")
            ->assertStatus(409)
            ->assertJsonPath('code', 'illegal_transition');
    }

    #[Test]
    public function it_refuses_the_publication_to_a_caller_without_the_permission(): void
    {
        $application = Application::factory()->draft()->create();

        Passport::actingAs(User::factory()->admin()->active()->create());

        $this->postJson("/applications/{$application->getKey()}/publish")->assertForbidden();
        $this->assertSame(ApplicationStatus::Draft, $application->fresh()->status);
    }

    #[Test]
    public function it_writes_the_return_addresses_on_the_oauth_client(): void
    {
        $application = Application::factory()->published()->create([
            'oauth_client_id' => Client::factory()->create([
                'redirect_uris' => ['https://old.dailyapps.test/callback'],
            ])->getKey(),
        ]);

        Passport::actingAs($this->operator());

        $this->putJson("/applications/{$application->getKey()}/redirect-uris", [
            'redirect_uris' => ['https://new.dailyapps.test/callback'],
        ])->assertOk()->assertJsonPath('data.redirect_uris', ['https://new.dailyapps.test/callback']);

        $this->assertSame(
            ['https://new.dailyapps.test/callback'],
            $application->fresh()->redirect_uris,
        );
    }

    #[Test]
    public function it_refuses_an_empty_list_of_return_addresses(): void
    {
        $application = Application::factory()->published()->create();

        Passport::actingAs($this->operator());

        $this->putJson("/applications/{$application->getKey()}/redirect-uris", ['redirect_uris' => []])
            ->assertUnprocessable();
    }

    #[Test]
    public function it_reads_the_return_addresses_from_the_oauth_client(): void
    {
        $application = Application::factory()->published()->create([
            'oauth_client_id' => Client::factory()->create([
                'redirect_uris' => ['https://leaves.dailyapps.test/callback'],
            ])->getKey(),
        ]);

        Passport::actingAs($this->operator());

        $this->getJson("/applications/{$application->getKey()}/redirect-uris")
            ->assertOk()
            ->assertJsonPath('data.redirect_uris', ['https://leaves.dailyapps.test/callback']);

        $this->assertNotContains('redirect_uris', array_keys($application->getAttributes()));
    }

    #[Test]
    public function it_never_answers_the_return_addresses_as_a_field_of_the_application(): void
    {
        Application::factory()->published()->create();

        Passport::actingAs($this->operator());

        $this->postJson('/applications/search', ['search' => []])
            ->assertOk()
            ->assertJsonMissingPath('data.0.redirect_uris');
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
