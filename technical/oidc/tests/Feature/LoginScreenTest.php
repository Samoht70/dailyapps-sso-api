<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Audit\Models\SecurityEvent;
use Technical\Oidc\Actions\ThrottleAuthentication;
use Technical\Oidc\Livewire\Login;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class LoginScreenTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Correct-Horse-42!';

    #[Test]
    public function it_serves_the_login_screen(): void
    {
        $this->withoutVite()
            ->get('/login')
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    }

    #[Test]
    public function it_signs_in_an_active_account_and_sends_it_to_the_page_it_was_after(): void
    {
        $account = $this->account();
        session()->put('url.intended', route('account'));

        Livewire::test(Login::class)
            ->set('email', $account->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('account'));

        $this->assertTrue(Auth::check());
        $this->assertTrue($account->is(Auth::user()));
    }

    #[Test]
    public function it_answers_an_unknown_address_and_a_wrong_password_with_the_same_message(): void
    {
        $account = $this->account();

        $unknown = $this->errorFor('nobody@dailyapps.test', self::PASSWORD);
        $wrong = $this->errorFor($account->email, 'Wrong-Horse-42!');

        $this->assertSame(__('oidc::auth.failed'), $unknown);
        $this->assertSame($unknown, $wrong);
    }

    #[Test]
    public function it_tells_a_disabled_account_that_it_was_cut_off(): void
    {
        $account = $this->account(fn (User $user) => $user->state()->disable());

        $this->assertSame(__('oidc::auth.account_unavailable'), $this->errorFor($account->email, self::PASSWORD));
        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function it_tells_an_account_of_a_suspended_organization_why_it_cannot_enter(): void
    {
        $organization = Organization::factory()->client()->create();
        $account = $this->account(organization: $organization);
        $organization->suspend();

        $this->assertSame(
            __('oidc::auth.organization_suspended'),
            $this->errorFor($account->email, self::PASSWORD),
        );
        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function it_locks_the_account_after_repeated_failures(): void
    {
        $account = $this->account();

        for ($attempt = 0; $attempt < ThrottleAuthentication::MAX_ATTEMPTS_PER_ACCOUNT; $attempt++) {
            $this->errorFor($account->email, 'Wrong-Horse-42!');
        }

        $this->assertStringContainsString(
            __('oidc::auth.throttled', ['seconds' => ThrottleAuthentication::DECAY_SECONDS]),
            $this->errorFor($account->email, self::PASSWORD),
        );
        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function it_journals_a_lockout(): void
    {
        $account = $this->account();

        for ($attempt = 0; $attempt <= ThrottleAuthentication::MAX_ATTEMPTS_PER_ACCOUNT; $attempt++) {
            $this->errorFor($account->email, 'Wrong-Horse-42!');
        }

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::AuthenticationThrottled->value,
        ]);
    }

    #[Test]
    public function it_journals_a_failed_and_a_successful_authentication(): void
    {
        $account = $this->account();

        $this->errorFor($account->email, 'Wrong-Horse-42!');
        $this->signIn($account);

        $this->assertSame(1, SecurityEvent::query()
            ->where('type', SecurityEventType::AuthenticationFailed)->count());
        $this->assertSame(1, SecurityEvent::query()
            ->where('type', SecurityEventType::AuthenticationSucceeded)->count());
    }

    #[Test]
    public function it_opens_an_sso_session_and_stamps_the_last_authentication(): void
    {
        $account = $this->account();

        $this->signIn($account);

        $session = SsoSession::query()->sole();

        $this->assertTrue($account->is($session->user));
        $this->assertTrue($session->isAlive());
        $this->assertSame($session->getKey(), session()->get(SsoSession::SESSION_KEY));
        $this->assertNotNull($account->fresh()->last_authenticated_at);
    }

    #[Test]
    public function it_forgets_the_failed_attempts_once_the_account_gets_in(): void
    {
        $account = $this->account();

        for ($attempt = 0; $attempt < ThrottleAuthentication::MAX_ATTEMPTS_PER_ACCOUNT - 1; $attempt++) {
            $this->errorFor($account->email, 'Wrong-Horse-42!');
        }

        $this->signIn($account);
        Auth::logout();

        $this->assertSame(__('oidc::auth.failed'), $this->errorFor($account->email, 'Wrong-Horse-42!'));
    }

    private function signIn(User $account): void
    {
        Livewire::test(Login::class)
            ->set('email', $account->email)
            ->set('password', self::PASSWORD)
            ->call('authenticate')
            ->assertHasNoErrors();
    }

    private function errorFor(string $email, string $password): ?string
    {
        $component = Livewire::test(Login::class)
            ->set('email', $email)
            ->set('password', $password)
            ->call('authenticate');

        return $component->errors()->first('email');
    }

    private function account(?callable $then = null, ?Organization $organization = null): User
    {
        $account = User::factory()
            ->active()
            ->when($organization, fn ($factory) => $factory->for($organization))
            ->create(['password' => Hash::make(self::PASSWORD)]);

        if ($then !== null) {
            $then($account);
        }

        return $account->fresh();
    }
}
