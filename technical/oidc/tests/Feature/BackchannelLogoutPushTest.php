<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Actions\DisableUser;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Jobs\PushBackchannelLogout;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Technical\Oidc\Models\SsoSessionParticipant;
use Tests\TestCase;

class BackchannelLogoutPushTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $account;

    private SsoSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->client()->create();
        User::factory()->for($this->organization)->admin()->active()->create();
        $this->account = User::factory()->for($this->organization)->active()->create();
        $this->session = SsoSession::factory()->for($this->account)->create();
    }

    #[Test]
    public function it_pushes_to_every_application_that_entered_the_session(): void
    {
        Queue::fake();
        $this->session->admit($this->application('leaves'));
        $this->session->admit($this->application('expenses'));

        app(DisableUser::class)($this->account);

        Queue::assertPushed(PushBackchannelLogout::class, 2);
    }

    #[Test]
    public function it_names_the_disabled_account_as_the_reason(): void
    {
        Queue::fake();
        $this->session->admit($this->application('leaves'));

        app(DisableUser::class)($this->account);

        Queue::assertPushed(
            PushBackchannelLogout::class,
            fn (PushBackchannelLogout $job): bool => (new \ReflectionProperty($job, 'reason'))
                ->getValue($job) === LogoutReason::UserDisabled,
        );
    }

    #[Test]
    public function it_posts_a_signed_jwt_to_the_declared_address(): void
    {
        Http::fake();
        $participant = $this->session->admit($this->application('leaves'));

        $this->push($participant);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://leaves.dailyapps.test/backchannel-logout'
                && $request->hasHeader('Content-Type', 'application/jwt')
                && substr_count($request->body(), '.') === 2;
        });
    }

    #[Test]
    public function it_carries_the_session_the_subject_and_the_event_in_the_token(): void
    {
        Http::fake();
        $participant = $this->session->admit($this->application('leaves'));

        $this->push($participant);

        $claims = $this->claimsOfPushedToken();

        $this->assertSame($this->session->getKey(), $claims['sid']);
        $this->assertSame($this->account->getKey(), $claims['sub']);
        $this->assertSame(LogoutReason::UserDisabled->value, $claims['reason']);
        $this->assertArrayHasKey('http://schemas.openid.net/event/backchannel-logout', $claims['events']);
        $this->assertArrayHasKey('jti', $claims);
    }

    #[Test]
    public function it_addresses_the_token_to_the_client_that_must_read_it(): void
    {
        Http::fake();
        $application = $this->application('leaves');
        $participant = $this->session->admit($application);

        $this->push($participant);

        $this->assertSame([$application->oauth_client_id], (array) $this->claimsOfPushedToken()['aud']);
    }

    #[Test]
    public function it_stamps_the_participant_once_the_push_went_through(): void
    {
        Http::fake();
        $participant = $this->session->admit($this->application('leaves'));

        $this->push($participant);

        $this->assertFalse($participant->fresh()->awaitsLogoutPush());
    }

    #[Test]
    public function it_refuses_the_access_token_of_the_disabled_account_straight_away(): void
    {
        Queue::fake();
        $this->session->admit($this->application('leaves'));
        $token = $this->tokenOnSession();

        app(DisableUser::class)($this->account);

        $this->assertTrue((bool) Passport::token()->newQuery()->whereKey($token)->value('revoked'));
    }

    private function push(SsoSessionParticipant $participant): void
    {
        app()->call([new PushBackchannelLogout($participant, LogoutReason::UserDisabled), 'handle']);
    }

    /** @return array<string, mixed> */
    private function claimsOfPushedToken(): array
    {
        $body = '';
        Http::assertSent(function (Request $request) use (&$body): bool {
            $body = $request->body();

            return true;
        });

        [, $payload] = explode('.', $body);

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function tokenOnSession(): string
    {
        $token = Passport::token()->newQuery()->create([
            'id' => $identifier = bin2hex(random_bytes(40)),
            'user_id' => $this->account->getKey(),
            'client_id' => $this->application('leaves')->oauth_client_id,
            'sso_session_id' => $this->session->getKey(),
            'scopes' => [],
            'revoked' => false,
            'expires_at' => now()->addMinutes(15),
        ]);

        return $token->getKey() ?: $identifier;
    }

    private function application(string $slug): Application
    {
        $application = Application::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => ucfirst($slug),
                'home_url' => "https://{$slug}.dailyapps.test",
                'backchannel_logout_url' => "https://{$slug}.dailyapps.test/backchannel-logout",
                'status' => ApplicationStatus::Published,
                'oauth_client_id' => Client::factory()->asPublic()->create([
                    'redirect_uris' => ["https://{$slug}.dailyapps.test/callback"],
                ])->getKey(),
            ],
        );

        License::query()->firstOrCreate(
            [
                'organization_id' => $this->organization->getKey(),
                'application_id' => $application->getKey(),
            ],
            ['starts_on' => now()->subMonth(), 'ends_on' => now()->addYear(), 'seats' => 10],
        );

        ApplicationAccess::query()->firstOrCreate(
            ['user_id' => $this->account->getKey(), 'application_id' => $application->getKey()],
            ['granted_at' => now()],
        );

        return $application;
    }
}
