<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    #[Test]
    public function it_announces_only_the_challenge_method_the_server_accepts(): void
    {
        $document = $this->getJson('/.well-known/openid-configuration')->assertOk()->json();

        $this->assertSame(['S256'], $document['code_challenge_methods_supported']);
    }

    #[Test]
    public function it_announces_the_endpoints_an_application_needs_to_connect(): void
    {
        $this->getJson('/.well-known/openid-configuration')
            ->assertOk()
            ->assertJsonStructure([
                'issuer',
                'authorization_endpoint',
                'token_endpoint',
                'userinfo_endpoint',
                'jwks_uri',
                'scopes_supported',
                'response_types_supported',
            ]);
    }

    #[Test]
    public function it_announces_the_four_scopes_of_the_contract(): void
    {
        $this->assertSame(
            ['openid', 'profile', 'email', 'applications'],
            $this->getJson('/.well-known/openid-configuration')->json('scopes_supported'),
        );
    }

    #[Test]
    public function it_never_announces_a_grant_the_server_keeps_closed(): void
    {
        $grants = $this->getJson('/.well-known/openid-configuration')->json('grant_types_supported');

        $this->assertNotContains('password', $grants);
        $this->assertNotContains('implicit', $grants);
        $this->assertContains('authorization_code', $grants);
    }

    #[Test]
    public function it_publishes_a_signing_key_that_carries_an_identifier(): void
    {
        $key = $this->getJson('/oauth/jwks')->assertOk()->json('keys.0');

        $this->assertSame('RSA', $key['kty']);
        $this->assertSame('RS256', $key['alg']);
        $this->assertArrayHasKey('kid', $key);
        $this->assertNotEmpty($key['kid']);
    }

    #[Test]
    public function it_stamps_the_id_token_with_the_key_that_signed_it(): void
    {
        $published = $this->getJson('/oauth/jwks')->json('keys.0.kid');

        $this->assertSame($published, $this->headerOfAnIdToken()['kid'] ?? null);
    }

    /** @return array<string, mixed> */
    private function headerOfAnIdToken(): array
    {
        $organization = Organization::factory()->client()->create();
        $account = User::factory()->for($organization)->active()->create();

        $application = Application::factory()->published()->create([
            'slug' => 'leaves',
            'oauth_client_id' => Client::factory()->asPublic()->create([
                'redirect_uris' => ['https://leaves.dailyapps.test/callback'],
            ])->getKey(),
        ]);
        License::factory()->for($organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($account)->for($application)->create();

        $session = SsoSession::factory()->for($account)->create();
        session()->put(SsoSession::SESSION_KEY, $session->getKey());

        $location = $this->actingAs($account)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $application->oauth_client_id,
                'redirect_uri' => 'https://leaves.dailyapps.test/callback',
                'response_type' => 'code',
                'scope' => 'openid profile email applications',
                'state' => 'state-value',
                'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
                'code_challenge_method' => 'S256',
            ]))
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $parameters);

        $idToken = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => 'https://leaves.dailyapps.test/callback',
            'code' => $parameters['code'],
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json('id_token');

        [$header] = explode('.', $idToken);

        return json_decode(base64_decode(strtr($header, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
    }
}
