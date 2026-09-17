<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class SessionLifetimeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_a_session_that_is_still_warm_through(): void
    {
        [$account, $session] = $this->signedIn();

        $this->withoutVite()->get('/account')->assertOk();

        $this->assertTrue($session->fresh()->isAlive());
    }

    #[Test]
    public function it_pushes_back_the_last_seen_moment_on_every_request(): void
    {
        [$account, $session] = $this->signedIn(lastSeen: now()->subMinutes(5));

        $this->withoutVite()->get('/account');

        $this->assertTrue($session->fresh()->last_seen_at->isAfter(now()->subMinute()));
    }

    #[Test]
    public function it_closes_a_session_left_quiet_beyond_the_inactivity_window(): void
    {
        $quietFor = config('oidc.session.inactivity_minutes') + 1;
        [$account, $session] = $this->signedIn(lastSeen: now()->subMinutes($quietFor));

        $this->withoutVite()->get('/account')->assertRedirect(route('login'));

        $this->assertFalse($session->fresh()->isAlive());
    }

    #[Test]
    public function it_closes_a_session_past_its_absolute_expiry_however_busy_it_was(): void
    {
        [$account, $session] = $this->signedIn(lastSeen: now(), expiresAt: now()->subMinute());

        $this->withoutVite()->get('/account')->assertRedirect(route('login'));

        $this->assertFalse($session->fresh()->isAlive());
    }

    #[Test]
    public function it_closes_a_session_that_was_revoked_elsewhere(): void
    {
        [$account, $session] = $this->signedIn();
        $session->revoke();

        $this->withoutVite()->get('/account')->assertRedirect(route('login'));
    }

    #[Test]
    public function it_carries_both_limits_in_the_layer_configuration(): void
    {
        $this->assertIsInt(config('oidc.session.absolute_lifetime_minutes'));
        $this->assertIsInt(config('oidc.session.inactivity_minutes'));
    }

    /** @return array{User, SsoSession} */
    private function signedIn(mixed $lastSeen = null, mixed $expiresAt = null): array
    {
        $account = User::factory()->active()->create();
        $session = SsoSession::factory()->for($account)->create(array_filter([
            'last_seen_at' => $lastSeen,
            'expires_at' => $expiresAt,
        ]));

        $this->actingAs($account);
        session()->put(SsoSession::SESSION_KEY, $session->getKey());

        return [$account, $session];
    }
}
