<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\ClaimsRegistry;
use Technical\Oidc\Contracts\ClaimsProvider;
use Technical\Oidc\Models\Client;
use Technical\Oidc\Models\SsoSession;
use Technical\Oidc\Models\SsoSessionParticipant;
use Tests\TestCase;

class SsoSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_session_that_is_alive(): void
    {
        $session = SsoSession::factory()->create();

        $this->assertTrue($session->isAlive());
        $this->assertSame(1, SsoSession::query()->alive()->count());
    }

    #[Test]
    public function it_closes_a_session_when_it_is_revoked(): void
    {
        $session = SsoSession::factory()->create();

        $session->revoke();

        $this->assertFalse($session->isAlive());
        $this->assertNotNull($session->revoked_at);
        $this->assertSame(0, SsoSession::query()->alive()->count());
    }

    #[Test]
    public function it_treats_a_session_past_its_absolute_expiry_as_closed(): void
    {
        $session = SsoSession::factory()->expired()->create();

        $this->assertFalse($session->isAlive());
        $this->assertSame(0, SsoSession::query()->alive()->count());
    }

    #[Test]
    public function it_pushes_back_the_moment_the_session_was_last_seen(): void
    {
        $session = SsoSession::factory()->create(['last_seen_at' => now()->subHour()]);

        $session->touchLastSeen();

        $this->assertTrue($session->last_seen_at->isAfter(now()->subMinute()));
    }

    #[Test]
    public function it_carries_the_session_id_as_a_uuid(): void
    {
        $session = SsoSession::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $session->getKey(),
        );
    }

    #[Test]
    public function it_reaches_the_sessions_of_a_user(): void
    {
        $user = User::factory()->active()->create();
        SsoSession::factory()->for($user)->count(2)->create();

        $this->assertSame(2, $user->ssoSessions()->count());
    }

    #[Test]
    public function it_records_each_application_that_enters_the_session(): void
    {
        $session = SsoSession::factory()->create();
        $leaves = Application::factory()->published()->create();
        $expenses = Application::factory()->published()->create();

        $session->admit($leaves);
        $session->admit($expenses);

        $this->assertSame(2, $session->participants()->count());
        $this->assertSame(2, $session->applications()->count());
    }

    #[Test]
    public function it_records_an_application_once_however_often_it_comes_back(): void
    {
        $session = SsoSession::factory()->create();
        $application = Application::factory()->published()->create();

        $first = $session->admit($application);
        $second = $session->admit($application);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, $session->participants()->count());
    }

    #[Test]
    public function it_knows_which_participants_still_await_the_logout_push(): void
    {
        $participant = SsoSessionParticipant::factory()->create();

        $this->assertTrue($participant->awaitsLogoutPush());

        $participant->markLogoutPushed();

        $this->assertFalse($participant->fresh()->awaitsLogoutPush());
        $this->assertNotNull($participant->logout_pushed_at);
    }

    #[Test]
    public function it_merges_the_claims_of_every_registered_provider(): void
    {
        $registry = app(ClaimsRegistry::class);
        $registry->register(new class implements ClaimsProvider
        {
            public function claimsFor(Authenticatable $user, Client $client): array
            {
                return ['organization' => 'acme'];
            }
        });
        $registry->register(new class implements ClaimsProvider
        {
            public function claimsFor(Authenticatable $user, Client $client): array
            {
                return ['roles' => ['manager']];
            }
        });

        $claims = $registry->claimsFor(User::factory()->active()->create(), Client::factory()->create());

        $this->assertSame(['organization' => 'acme', 'roles' => ['manager']], $claims);
    }

    #[Test]
    public function it_holds_the_claims_registry_as_a_single_instance(): void
    {
        $this->assertSame(app(ClaimsRegistry::class), app(ClaimsRegistry::class));
    }

    #[Test]
    public function it_serves_the_layer_translations_under_its_own_namespace(): void
    {
        $this->assertNotSame('oidc::auth.failed', __('oidc::auth.failed'));
    }
}
