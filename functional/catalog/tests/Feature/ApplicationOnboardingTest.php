<?php

namespace Functional\Catalog\Tests\Feature;

use Functional\Catalog\Actions\DeclareApplication;
use Functional\Catalog\Actions\PublishApplication;
use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Tests\TestCase;

class ApplicationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();
    }

    #[Test]
    public function it_declares_an_application_with_its_own_oauth_client(): void
    {
        $application = $this->declare('payroll');

        $this->assertSame(ApplicationStatus::Draft, $application->status);
        $this->assertNotNull($application->oauthClient);
        $this->assertSame(['https://payroll.dailyapps.test/callback'], $application->redirect_uris);
    }

    #[Test]
    public function it_hands_the_secret_over_once_and_keeps_only_a_hash(): void
    {
        $application = $this->declare('payroll');

        $secret = $application->oauthClient->plainSecret;

        $this->assertNotNull($secret);
        $this->assertNotSame($secret, $application->oauthClient->fresh()->getRawOriginal('secret'));
        $this->assertNull($application->oauthClient->fresh()->plainSecret);
        $this->assertTrue(Hash::check($secret, $application->oauthClient->fresh()->getRawOriginal('secret')));
    }

    #[Test]
    public function it_never_copies_the_return_addresses_onto_the_applications_table(): void
    {
        $application = $this->declare('payroll');

        $columns = array_keys($application->getAttributes());

        $this->assertNotContains('redirect_uris', $columns);
        $this->assertSame(
            $application->oauthClient->redirect_uris,
            $application->redirect_uris,
        );
    }

    #[Test]
    public function it_carries_a_freshly_declared_application_through_a_whole_sign_in(): void
    {
        $application = $this->declare('payroll');
        $secret = $application->oauthClient->plainSecret;

        app(PublishApplication::class)($application);
        $this->entitle($application);

        $token = $this->exchange($application, $secret);

        $this->assertArrayHasKey('access_token', $token);
        $this->assertArrayHasKey('id_token', $token);
        $this->assertArrayHasKey('refresh_token', $token);
    }

    #[Test]
    public function it_asks_nothing_of_the_applications_already_connected(): void
    {
        $leaves = $this->declare('leaves');
        app(PublishApplication::class)($leaves);
        $this->entitle($leaves);

        $payroll = $this->declare('payroll');
        app(PublishApplication::class)($payroll);
        $this->entitle($payroll);

        $this->assertArrayHasKey(
            'access_token',
            $this->exchange($leaves, $leaves->oauthClient->plainSecret),
        );
    }

    #[Test]
    public function it_journals_the_publication(): void
    {
        $application = $this->declare('payroll');

        app(PublishApplication::class)($application);

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::ApplicationPublished->value,
        ]);
    }

    #[Test]
    public function it_refuses_a_sign_in_on_an_application_still_in_draft(): void
    {
        $application = $this->declare('payroll');
        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        $location = $this->actingAs($this->account)
            ->get($this->authorizeUrl($application))
            ->headers->get('Location');

        $this->assertStringContainsString('error=access_denied', $location);
        $this->assertStringNotContainsString('code=', $location);
    }

    private function declare(string $slug): Application
    {
        return app(DeclareApplication::class)(
            $slug,
            ucfirst($slug),
            "https://{$slug}.dailyapps.test",
            ["https://{$slug}.dailyapps.test/callback"],
        );
    }

    private function entitle(Application $application): void
    {
        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();
    }

    /** @return array<string, mixed> */
    private function exchange(Application $application, string $secret): array
    {
        $location = $this->actingAs($this->account)
            ->get($this->authorizeUrl($application))
            ->assertRedirectContains($application->oauthClient->redirect_uris[0])
            ->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY), $parameters);

        return $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $application->oauth_client_id,
            'client_secret' => $secret,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'code' => $parameters['code'],
            'code_verifier' => self::VERIFIER,
        ])->assertOk()->json();
    }

    private function authorizeUrl(Application $application): string
    {
        return '/oauth/authorize?'.http_build_query([
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'openid profile email applications',
            'state' => 'state-value',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);
    }
}
