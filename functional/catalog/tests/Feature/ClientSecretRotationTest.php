<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Actions\DeclareApplication;
use Functional\Catalog\Actions\PublishApplication;
use Functional\Catalog\Actions\RevokeClientSecret;
use Functional\Catalog\Actions\RotateClientSecret;
use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Permissions\Database\Seeders\PermissionsSeeder;
use Technical\Permissions\Enums\Permission;
use Tests\TestCase;

class ClientSecretRotationTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();
    }

    #[Test]
    public function it_hands_the_new_secret_over_once_and_keeps_only_a_hash(): void
    {
        $application = $this->connected('leaves');

        $secret = app(RotateClientSecret::class)($application);

        $this->assertNotNull($secret);
        $this->assertTrue(Hash::check($secret, $application->oauthClient->fresh()->getRawOriginal('secret')));
    }

    #[Test]
    public function it_refuses_the_secret_that_was_replaced(): void
    {
        $application = $this->connected('leaves');
        $previous = $application->oauthClient->plainSecret;

        app(RotateClientSecret::class)($application);

        $this->exchange($application, $previous)->assertUnauthorized();
    }

    #[Test]
    public function it_accepts_the_secret_that_replaced_it(): void
    {
        $application = $this->connected('leaves');

        $secret = app(RotateClientSecret::class)($application);

        $this->exchange($application->fresh(), $secret)->assertOk();
    }

    #[Test]
    public function it_leaves_the_other_applications_untouched(): void
    {
        $leaves = $this->connected('leaves');
        $expenses = $this->connected('expenses');
        $expensesSecret = $expenses->oauthClient->plainSecret;

        app(RotateClientSecret::class)($leaves);

        $this->exchange($expenses, $expensesSecret)->assertOk();
    }

    #[Test]
    public function it_turns_a_revoked_client_away_without_touching_the_others(): void
    {
        $leaves = $this->connected('leaves');
        $leavesSecret = $leaves->oauthClient->plainSecret;
        $expenses = $this->connected('expenses');
        $expensesSecret = $expenses->oauthClient->plainSecret;

        app(RevokeClientSecret::class)($leaves);

        $this->exchange($leaves->fresh(), $leavesSecret)->assertUnauthorized();
        $this->exchange($expenses, $expensesSecret)->assertOk();
    }

    #[Test]
    public function it_journals_the_rotation(): void
    {
        $application = $this->connected('leaves');

        app(RotateClientSecret::class)($application);

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::ClientSecretRotated->value,
        ]);
    }

    #[Test]
    public function it_rotates_through_the_endpoint_for_an_operator_holding_the_permission(): void
    {
        $application = $this->connected('leaves');

        Passport::actingAs($this->operator());

        $this->postJson("/applications/{$application->getKey()}/client-secret")
            ->assertOk()
            ->assertJsonStructure(['data' => ['client_secret']]);
    }

    #[Test]
    public function it_refuses_the_rotation_to_a_caller_without_the_permission(): void
    {
        $application = $this->connected('leaves');

        Passport::actingAs(User::factory()->for($this->organization)->admin()->active()->create());

        $this->postJson("/applications/{$application->getKey()}/client-secret")->assertForbidden();
    }

    #[Test]
    public function it_revokes_through_the_endpoint(): void
    {
        $application = $this->connected('leaves');

        Passport::actingAs($this->operator());

        $this->deleteJson("/applications/{$application->getKey()}/client-secret")->assertNoContent();

        $this->assertTrue($application->oauthClient->fresh()->revoked);
    }

    private function connected(string $slug): Application
    {
        $application = app(DeclareApplication::class)(
            $slug,
            ucfirst($slug),
            "https://{$slug}.dailyapps.test",
            ["https://{$slug}.dailyapps.test/callback"],
        );

        app(PublishApplication::class)($application);

        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        return $application;
    }

    private function exchange(Application $application, string $secret): TestResponse
    {
        $this->app['auth']->forgetGuards();

        $location = $this->actingAs($this->account)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $application->oauth_client_id,
                'redirect_uri' => $application->oauthClient->redirect_uris[0],
                'response_type' => 'code',
                'scope' => 'openid profile email applications',
                'state' => 'state-value',
                'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
                'code_challenge_method' => 'S256',
            ]))
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $parameters);

        return $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $application->oauth_client_id,
            'client_secret' => $secret,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'code' => $parameters['code'] ?? '',
            'code_verifier' => self::VERIFIER,
        ]);
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
