<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Actions\RevokeLicense;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class RefreshTokenRevalidationTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    private Application $leaves;

    private License $license;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();

        $this->leaves = Application::factory()->published()->create([
            'slug' => 'leaves',
            'oauth_client_id' => Client::factory()->asPublic()->create([
                'redirect_uris' => ['https://leaves.dailyapps.test/callback'],
            ])->getKey(),
        ]);

        $this->license = License::factory()->for($this->organization)->for($this->leaves)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($this->leaves)->create();
    }

    #[Test]
    public function it_refreshes_a_token_while_all_three_still_hold(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        $this->refresh($refreshToken)->assertOk()->assertJsonStructure(['access_token', 'refresh_token']);
    }

    #[Test]
    public function it_refuses_to_refresh_for_a_disabled_account(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        $this->account->state()->disable();

        $this->assertInvalidGrant($this->refresh($refreshToken));
    }

    #[Test]
    public function it_refuses_to_refresh_for_a_suspended_organization(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        $this->organization->suspend();

        $this->assertInvalidGrant($this->refresh($refreshToken));
    }

    #[Test]
    public function it_refuses_to_refresh_once_the_licence_has_been_revoked(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        app(RevokeLicense::class)($this->license);

        $this->assertInvalidGrant($this->refresh($refreshToken));
    }

    #[Test]
    public function it_refuses_to_refresh_once_the_licence_has_expired(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        $this->license->forceFill(['ends_on' => now()->subDay()])->save();

        $this->assertInvalidGrant($this->refresh($refreshToken));
    }

    #[Test]
    public function it_refuses_to_refresh_once_the_access_has_been_taken_away(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        ApplicationAccess::query()
            ->where('user_id', $this->account->getKey())
            ->where('application_id', $this->leaves->getKey())
            ->delete();

        $this->assertInvalidGrant($this->refresh($refreshToken));
    }

    #[Test]
    public function it_refuses_to_refresh_once_the_application_has_left_the_catalog(): void
    {
        $refreshToken = $this->firstTokens()['refresh_token'];

        $this->leaves->retire();

        $this->assertInvalidGrant($this->refresh($refreshToken));
    }

    #[Test]
    public function it_keeps_the_session_on_the_refreshed_token(): void
    {
        $tokens = $this->firstTokens();

        $refreshed = $this->refresh($tokens['refresh_token'])->assertOk()->json();

        $this->assertSame(
            $this->sessionIdOf($tokens['id_token']),
            $this->sessionIdOf($refreshed['id_token']),
        );
    }

    private function assertInvalidGrant(TestResponse $response): void
    {
        $response->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    private function refresh(string $refreshToken): TestResponse
    {
        return $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->leaves->oauth_client_id,
            'refresh_token' => $refreshToken,
            'scope' => 'openid profile email applications',
        ]);
    }

    /** @return array<string, mixed> */
    private function firstTokens(): array
    {
        $session = SsoSession::factory()->for($this->account)->create();
        session()->put(SsoSession::SESSION_KEY, $session->getKey());

        $location = $this->actingAs($this->account)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $this->leaves->oauth_client_id,
                'redirect_uri' => 'https://leaves.dailyapps.test/callback',
                'response_type' => 'code',
                'scope' => 'openid profile email applications',
                'state' => 'state-value',
                'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
                'code_challenge_method' => 'S256',
            ]))
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $parameters);

        $tokens = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->leaves->oauth_client_id,
            'redirect_uri' => 'https://leaves.dailyapps.test/callback',
            'code' => $parameters['code'],
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json();

        $this->app['auth']->forgetGuards();

        return $tokens;
    }

    private function sessionIdOf(string $idToken): ?string
    {
        [, $payload] = explode('.', $idToken);

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true)['sid'] ?? null;
    }
}
