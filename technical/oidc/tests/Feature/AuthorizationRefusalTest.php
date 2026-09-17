<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Enums\RefusalReason;
use Technical\Oidc\Models\Client;
use Tests\TestCase;

class AuthorizationRefusalTest extends TestCase
{
    use RefreshDatabase;

    private const DECLARED_URI = 'https://leaves.dailyapps.test/callback';

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();
        $this->application = Application::factory()->published()->create([
            'slug' => 'leaves',
            'oauth_client_id' => Client::factory()->create([
                'redirect_uris' => [self::DECLARED_URI],
            ])->getKey(),
        ]);
    }

    #[Test]
    public function it_refuses_an_undeclared_return_address_without_ever_redirecting_to_it(): void
    {
        $response = $this->actingAs($this->account)
            ->get($this->authorizeUrl(['redirect_uri' => 'https://attacker.test/callback']));

        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertStringNotContainsString('attacker.test', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function it_refuses_an_authorization_request_that_brings_no_pkce_challenge(): void
    {
        $response = $this->actingAs($this->account)
            ->get($this->authorizeUrl(['code_challenge' => null, 'code_challenge_method' => null]));

        $this->assertStringNotContainsString('code=', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function it_refuses_a_plain_code_challenge_even_from_a_confidential_client(): void
    {
        $response = $this->actingAs($this->account)
            ->get($this->authorizeUrl(['code_challenge_method' => 'plain']));

        $this->assertStringNotContainsString('code=', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function it_tells_a_disabled_account_apart_from_a_missing_licence(): void
    {
        $this->entitle();
        $this->account->state()->disable();

        $this->assertRefusedWith(RefusalReason::AccountUnavailable);
    }

    #[Test]
    public function it_refuses_an_account_of_a_suspended_organization(): void
    {
        $this->entitle();
        $this->organization->suspend();

        $this->assertRefusedWith(RefusalReason::AccountUnavailable);
    }

    #[Test]
    public function it_refuses_when_the_organization_holds_no_valid_licence(): void
    {
        ApplicationAccess::factory()->for($this->account)->for($this->application)->create();

        $this->assertRefusedWith(RefusalReason::NoLicense);
    }

    #[Test]
    public function it_refuses_when_the_licence_has_expired(): void
    {
        License::factory()->for($this->organization)->for($this->application)->expired()->create();
        ApplicationAccess::factory()->for($this->account)->for($this->application)->create();

        $this->assertRefusedWith(RefusalReason::NoLicense);
    }

    #[Test]
    public function it_refuses_when_the_licence_is_valid_but_no_access_was_granted(): void
    {
        License::factory()->for($this->organization)->for($this->application)->valid()->create();

        $this->assertRefusedWith(RefusalReason::NoAccess);
    }

    #[Test]
    public function it_lets_an_entitled_account_through(): void
    {
        $this->entitle();

        $location = $this->actingAs($this->account)->get($this->authorizeUrl())->headers->get('Location');

        $this->assertStringContainsString('code=', $location);
        $this->assertStringNotContainsString('error=', $location);
    }

    private function assertRefusedWith(RefusalReason $reason): void
    {
        $location = $this->actingAs($this->account->fresh())
            ->get($this->authorizeUrl())
            ->assertRedirectContains(self::DECLARED_URI)
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $parameters);

        $this->assertSame('access_denied', $parameters['error'] ?? null);
        $this->assertStringContainsString($reason->value, $parameters['hint'] ?? $parameters['error_description'] ?? '');
        $this->assertArrayNotHasKey('code', $parameters);
    }

    private function entitle(): void
    {
        License::factory()->for($this->organization)->for($this->application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($this->application)->create();
    }

    /** @param array<string, string|null> $overrides */
    private function authorizeUrl(array $overrides = []): string
    {
        $parameters = array_merge([
            'client_id' => $this->application->oauth_client_id,
            'redirect_uri' => self::DECLARED_URI,
            'response_type' => 'code',
            'scope' => 'openid profile email applications',
            'state' => 'state-value',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], $overrides);

        return '/oauth/authorize?'.http_build_query(array_filter(
            $parameters,
            fn (?string $value): bool => $value !== null,
        ));
    }
}
