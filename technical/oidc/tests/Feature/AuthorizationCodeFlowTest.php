<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Livewire\Login;
use Technical\Oidc\Models\Client;
use Tests\TestCase;

class AuthorizationCodeFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Correct-Horse-42!';

    private const VERIFIER = 'a-verifier-long-enough-to-be-accepted-by-the-server';

    private Organization $organization;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        $this->account = User::factory()
            ->for($this->organization)
            ->active()
            ->create(['password' => Hash::make(self::PASSWORD)]);
    }

    #[Test]
    public function it_sends_an_unidentified_visitor_to_the_login_screen(): void
    {
        $application = $this->entitledApplication();

        $this->withoutVite()
            ->get($this->authorizeUrl($application))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_keeps_the_page_that_was_asked_for_in_the_session(): void
    {
        $application = $this->entitledApplication();

        $this->withoutVite()->get($this->authorizeUrl($application));

        $intended = session()->get('url.intended');

        $this->assertStringContainsString('/oauth/authorize', $intended);
        $this->assertStringContainsString($application->oauth_client_id, $intended);
    }

    #[Test]
    public function it_never_carries_the_return_address_in_a_parameter_of_the_login_screen(): void
    {
        $application = $this->entitledApplication();

        $location = $this->withoutVite()
            ->get($this->authorizeUrl($application))
            ->headers->get('Location');

        $this->assertSame(route('login'), $location);
    }

    #[Test]
    public function it_brings_the_visitor_back_to_the_page_it_was_after_once_identified(): void
    {
        $application = $this->entitledApplication();

        $this->withoutVite()->get($this->authorizeUrl($application));

        Livewire::test(Login::class)
            ->set('email', $this->account->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertRedirect(session()->get('url.intended'));
    }

    #[Test]
    public function it_hands_back_an_authorization_code_without_asking_for_consent(): void
    {
        $application = $this->entitledApplication();

        $response = $this->actingAs($this->account)->get($this->authorizeUrl($application));

        $this->assertCodeWasIssued($response, $application);
    }

    #[Test]
    public function it_does_not_ask_a_second_application_to_sign_in_again(): void
    {
        $leaves = $this->entitledApplication('leaves');
        $expenses = $this->entitledApplication('expenses');

        $this->actingAs($this->account);

        $this->assertCodeWasIssued($this->get($this->authorizeUrl($leaves)), $leaves);
        $this->assertCodeWasIssued($this->get($this->authorizeUrl($expenses)), $expenses);
    }

    #[Test]
    public function it_carries_the_state_back_untouched(): void
    {
        $application = $this->entitledApplication();

        $response = $this->actingAs($this->account)
            ->get($this->authorizeUrl($application, state: 'an-opaque-nonce'));

        $this->assertStringContainsString('state=an-opaque-nonce', $response->headers->get('Location'));
    }

    private function assertCodeWasIssued(TestResponse $response, Application $application): void
    {
        $location = $response->assertRedirectContains(
            $application->oauthClient->redirect_uris[0],
        )->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $parameters);

        $this->assertArrayHasKey('code', $parameters);
        $this->assertArrayNotHasKey('error', $parameters);
    }

    private function entitledApplication(string $slug = 'leaves'): Application
    {
        $application = Application::factory()->published()->create([
            'slug' => $slug,
            'oauth_client_id' => Client::factory()->create([
                'redirect_uris' => ["https://{$slug}.dailyapps.test/callback"],
            ])->getKey(),
        ]);

        License::factory()->for($this->organization)->for($application)->valid()->create();
        ApplicationAccess::factory()->for($this->account)->for($application)->create();

        return $application;
    }

    private function authorizeUrl(Application $application, string $state = 'state-value'): string
    {
        return '/oauth/authorize?'.http_build_query([
            'client_id' => $application->oauth_client_id,
            'redirect_uri' => $application->oauthClient->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'openid profile email applications',
            'state' => $state,
            'code_challenge' => self::codeChallenge(),
            'code_challenge_method' => 'S256',
        ]);
    }

    private static function codeChallenge(): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '=');
    }
}
