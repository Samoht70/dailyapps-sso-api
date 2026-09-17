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

class ScopeIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    private Application $leaves;

    private Application $expenses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create(['name' => 'Acme']);
        $this->account = User::factory()->for($this->organization)->active()->create();

        $this->leaves = $this->application('leaves', 'manager');
        $this->expenses = $this->application('expenses', 'user');

        $session = SsoSession::factory()->for($this->account)->create();
        session()->put(SsoSession::SESSION_KEY, $session->getKey());
    }

    #[Test]
    public function it_answers_userinfo_with_the_roles_of_the_calling_application_alone(): void
    {
        $claims = $this->userInfoFor($this->leaves);

        $this->assertSame(['manager'], $claims['roles']);
    }

    #[Test]
    public function it_never_leaks_the_roles_held_on_another_application(): void
    {
        $this->assertSame(['manager'], $this->userInfoFor($this->leaves)['roles']);
        $this->assertSame(['user'], $this->userInfoFor($this->expenses)['roles']);
    }

    #[Test]
    public function it_names_only_the_caller_as_the_subject(): void
    {
        $stranger = User::factory()->for($this->organization)->active()->create();

        $claims = $this->userInfoFor($this->leaves);

        $this->assertSame($this->account->getKey(), $claims['sub']);
        $this->assertNotSame($stranger->getKey(), $claims['sub']);
    }

    #[Test]
    public function it_withholds_the_roles_from_a_token_that_was_not_granted_the_applications_scope(): void
    {
        $claims = $this->userInfoFor($this->leaves, scope: 'openid profile');

        $this->assertArrayNotHasKey('roles', $claims);
        $this->assertArrayHasKey('organization', $claims);
    }

    #[Test]
    public function it_withholds_the_organization_from_a_token_that_was_not_granted_the_profile_scope(): void
    {
        $claims = $this->userInfoFor($this->leaves, scope: 'openid');

        $this->assertArrayNotHasKey('organization', $claims);
        $this->assertArrayNotHasKey('roles', $claims);
    }

    #[Test]
    public function it_turns_away_userinfo_without_a_token(): void
    {
        $this->getJson('/oauth/userinfo')->assertUnauthorized();
    }

    #[Test]
    public function it_scopes_the_reachable_applications_to_the_caller(): void
    {
        $token = $this->accessTokenFor($this->leaves);

        $slugs = $this->asApplication($token)->getJson('/me/applications')->assertOk()->json('data.*.slug');

        $this->assertEqualsCanonicalizing(['leaves', 'expenses'], $slugs);
    }

    /** @return array<string, mixed> */
    private function userInfoFor(Application $application, string $scope = 'openid profile email applications'): array
    {
        $token = $this->accessTokenFor($application, $scope);

        return $this->asApplication($token)
            ->getJson('/oauth/userinfo')
            ->assertOk()
            ->json();
    }

    private function accessTokenFor(Application $application, string $scope = 'openid profile email applications'): string
    {
        $location = $this->actingAs($this->account)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $application->oauth_client_id,
                'redirect_uri' => $application->oauthClient->redirect_uris[0],
                'response_type' => 'code',
                'scope' => $scope,
                'state' => 'state-value',
                'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
                'code_challenge_method' => 'S256',
            ]))
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $parameters);

        return $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'code' => $parameters['code'],
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json('access_token');
    }

    /**
     * The browser session opened to reach /oauth/authorize would otherwise still
     * answer for the caller, and the request would never be read as the
     * server-to-server call it is.
     */
    private function asApplication(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    private function application(string $slug, string $roleKey): Application
    {
        $application = Application::factory()->published()->create([
            'slug' => $slug,
            'oauth_client_id' => Client::factory()->asPublic()->create([
                'redirect_uris' => ["https://{$slug}.dailyapps.test/callback"],
            ])->getKey(),
        ]);

        $role = ApplicationRole::factory()->for($application)->create(['key' => $roleKey]);

        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create()
            ->roles()->attach($role->getKey());

        return $application;
    }
}
