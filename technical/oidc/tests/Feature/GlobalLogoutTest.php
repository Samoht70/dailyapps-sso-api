<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Jobs\PushBackchannelLogout;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class GlobalLogoutTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    private Application $leaves;

    private Application $expenses;

    private SsoSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();

        $this->leaves = $this->connected('leaves');
        $this->expenses = $this->connected('expenses');

        $this->session = SsoSession::factory()->for($this->account)->create();
        session()->put(SsoSession::SESSION_KEY, $this->session->getKey());
        $this->actingAs($this->account);
    }

    #[Test]
    public function it_records_each_application_that_enters_the_session(): void
    {
        $this->authorize($this->leaves);
        $this->authorize($this->expenses);

        $this->assertEqualsCanonicalizing(
            ['leaves', 'expenses'],
            $this->session->applications->pluck('slug')->all(),
        );
    }

    #[Test]
    public function it_closes_the_central_session_when_one_application_signs_out(): void
    {
        $this->authorize($this->leaves);

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertFalse($this->session->fresh()->isAlive());
    }

    #[Test]
    public function it_stops_the_second_application_at_its_next_call(): void
    {
        $this->authorize($this->leaves);
        $onExpenses = $this->tokensFor($this->expenses);

        $this->post('/logout');
        $this->app['auth']->forgetGuards();

        $this->withToken($onExpenses['access_token'])
            ->getJson('/me/applications')
            ->assertUnauthorized();
    }

    #[Test]
    public function it_stops_the_second_application_from_refreshing(): void
    {
        $onExpenses = $this->tokensFor($this->expenses);

        $this->post('/logout');
        $this->app['auth']->forgetGuards();

        $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->expenses->oauth_client_id,
            'refresh_token' => $onExpenses['refresh_token'],
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    #[Test]
    public function it_queues_one_push_per_application_that_entered_the_session(): void
    {
        $this->authorize($this->leaves);
        $this->authorize($this->expenses);

        $this->post('/logout');

        Queue::assertPushed(PushBackchannelLogout::class, 2);
    }

    #[Test]
    public function it_names_the_logout_as_the_reason(): void
    {
        $this->authorize($this->leaves);

        $this->post('/logout');

        Queue::assertPushed(
            PushBackchannelLogout::class,
            fn (PushBackchannelLogout $job): bool => $this->reasonOf($job) === LogoutReason::Logout,
        );
    }

    #[Test]
    public function it_journals_the_end_of_the_session(): void
    {
        $this->authorize($this->leaves);

        $this->post('/logout');

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::SessionEnded->value,
        ]);
    }

    #[Test]
    public function it_pushes_nothing_twice_when_the_logout_is_replayed(): void
    {
        $this->authorize($this->leaves);

        $this->post('/logout');
        $this->post('/logout');

        Queue::assertPushed(PushBackchannelLogout::class, 1);
    }

    private function reasonOf(PushBackchannelLogout $job): LogoutReason
    {
        return (new \ReflectionProperty($job, 'reason'))->getValue($job);
    }

    private function connected(string $slug): Application
    {
        $application = Application::factory()->published()->withBackchannelLogout()->create([
            'slug' => $slug,
            'oauth_client_id' => Client::factory()->asPublic()->create([
                'redirect_uris' => ["https://{$slug}.dailyapps.test/callback"],
            ])->getKey(),
        ]);

        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        return $application;
    }

    private function authorize(Application $application): string
    {
        $location = $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'openid profile email applications',
            'state' => 'state-value',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]))->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $parameters);

        return $parameters['code'];
    }

    /** @return array<string, mixed> */
    private function tokensFor(Application $application): array
    {
        $code = $this->authorize($application);

        $tokens = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'code' => $code,
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json();

        $this->actingAs($this->account);
        session()->put(SsoSession::SESSION_KEY, $this->session->getKey());

        return $tokens;
    }
}
