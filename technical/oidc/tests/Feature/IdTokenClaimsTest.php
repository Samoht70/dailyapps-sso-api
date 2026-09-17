<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class IdTokenClaimsTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create(['name' => 'Acme']);
        $this->account = User::factory()->for($this->organization)->active()->create();
    }

    #[Test]
    public function it_carries_the_session_id_so_a_logout_can_name_the_session_to_close(): void
    {
        $leaves = $this->application('leaves', ['user', 'manager']);
        $session = $this->openSession();

        $claims = $this->idTokenFor($leaves);

        $this->assertSame($session->getKey(), $claims['sid']);
    }

    #[Test]
    public function it_carries_the_organization_under_the_profile_scope(): void
    {
        $leaves = $this->application('leaves', ['user']);
        $this->openSession();

        $claims = $this->idTokenFor($leaves);

        $this->assertSame($this->organization->getKey(), $claims['organization']['id']);
        $this->assertSame('Acme', $claims['organization']['name']);
        $this->assertSame($this->account->name, $claims['name']);
    }

    #[Test]
    public function it_carries_only_the_roles_held_on_the_calling_application(): void
    {
        $leaves = $this->application('leaves', ['user', 'manager']);
        $expenses = $this->application('expenses', ['user', 'manager']);

        $this->grant($leaves, ['manager']);
        $this->grant($expenses, ['user']);
        $this->openSession();

        $this->assertSame(['manager'], $this->idTokenFor($leaves)['roles']);
        $this->assertSame(['user'], $this->idTokenFor($expenses)['roles']);
    }

    #[Test]
    public function it_answers_with_no_role_when_none_was_attached_to_the_access(): void
    {
        $leaves = $this->application('leaves', ['user']);
        $this->openSession();

        $this->assertSame([], $this->idTokenFor($leaves)['roles']);
    }

    #[Test]
    public function it_names_the_account_as_the_subject(): void
    {
        $leaves = $this->application('leaves', ['user']);
        $this->openSession();

        $this->assertSame($this->account->getKey(), $this->idTokenFor($leaves)['sub']);
    }

    /** @return array<string, mixed> */
    private function idTokenFor(Application $application): array
    {
        $code = $this->authorizationCode($application);

        $token = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'code' => $code,
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json();

        $this->assertArrayHasKey('id_token', $token);

        return $this->decode($token['id_token']);
    }

    private function authorizationCode(Application $application): string
    {
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
            ->assertRedirectContains($application->oauthClient->redirect_uris[0])
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $parameters);

        return $parameters['code'];
    }

    /** @return array<string, mixed> */
    private function decode(string $jwt): array
    {
        [, $payload] = explode('.', $jwt);

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function openSession(): SsoSession
    {
        $session = SsoSession::factory()->for($this->account)->create();

        session()->put(SsoSession::SESSION_KEY, $session->getKey());

        return $session;
    }

    /** @param list<string> $roleKeys */
    private function application(string $slug, array $roleKeys): Application
    {
        $application = Application::factory()->published()->create([
            'slug' => $slug,
            'oauth_client_id' => Client::factory()->asPublic()->create([
                'redirect_uris' => ["https://{$slug}.dailyapps.test/callback"],
            ])->getKey(),
        ]);

        foreach ($roleKeys as $key) {
            ApplicationRole::factory()->for($application)->create(['key' => $key]);
        }

        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        return $application;
    }

    /** @param list<string> $roleKeys */
    private function grant(Application $application, array $roleKeys): void
    {
        $access = ApplicationAccess::query()
            ->where('user_id', $this->account->getKey())
            ->where('application_id', $application->getKey())
            ->sole();

        $access->roles()->sync(
            ApplicationRole::query()
                ->where('application_id', $application->getKey())
                ->whereIn('key', $roleKeys)
                ->pluck('id'),
        );
    }
}
