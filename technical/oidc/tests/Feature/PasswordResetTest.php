<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Users\Models\User;
use Functional\Users\Notifications\PasswordResetRequested;
use Illuminate\Auth\Notifications\ResetPassword as LaravelResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Livewire\ForgotPassword;
use Technical\Oidc\Livewire\ResetPassword;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'Brand-New-Horse-42!';

    #[Test]
    public function it_answers_a_known_and_an_unknown_address_the_same_way(): void
    {
        Notification::fake();
        $account = User::factory()->active()->create();

        $known = Livewire::test(ForgotPassword::class)
            ->set('email', $account->email)
            ->call('sendLink');

        $unknown = Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@dailyapps.test')
            ->call('sendLink');

        $known->assertHasNoErrors()->assertSet('sent', true);
        $unknown->assertHasNoErrors()->assertSet('sent', true);
    }

    #[Test]
    public function it_mails_the_link_through_the_layer_notification(): void
    {
        Notification::fake();
        $account = User::factory()->active()->create();

        Livewire::test(ForgotPassword::class)->set('email', $account->email)->call('sendLink');

        Notification::assertSentTo($account, PasswordResetRequested::class);
        Notification::assertNotSentTo($account, LaravelResetPassword::class);
    }

    #[Test]
    public function it_journals_the_request(): void
    {
        Notification::fake();
        $account = User::factory()->active()->create();

        Livewire::test(ForgotPassword::class)->set('email', $account->email)->call('sendLink');

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::PasswordResetRequested->value,
            'actor_id' => $account->getKey(),
        ]);
    }

    #[Test]
    public function it_sets_a_new_password_from_a_fresh_link(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);

        $this->reset($account, $token)->assertHasNoErrors()->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $account->fresh()->password));
    }

    #[Test]
    public function it_journals_the_change(): void
    {
        $account = User::factory()->active()->create();

        $this->reset($account, Password::broker()->createToken($account));

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::PasswordChanged->value,
            'actor_id' => $account->getKey(),
        ]);
    }

    #[Test]
    public function it_spends_the_link_on_its_first_use(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);

        $this->reset($account, $token)->assertHasNoErrors();
        $this->reset($account, $token)->assertHasErrors('email');
    }

    #[Test]
    public function it_turns_away_a_link_that_has_expired(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->reset($account, $token)->assertHasErrors('email');
    }

    #[Test]
    public function it_invalidates_the_previous_link_when_a_newer_one_is_asked_for(): void
    {
        $account = User::factory()->active()->create();
        $first = Password::broker()->createToken($account);

        $this->travel(config('auth.passwords.users.throttle') + 1)->seconds();
        Password::broker()->createToken($account);

        $this->reset($account, $first)->assertHasErrors('email');
    }

    #[Test]
    public function it_turns_away_a_password_that_is_too_weak_and_says_why(): void
    {
        $account = User::factory()->active()->create();
        $token = Password::broker()->createToken($account);

        $component = Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $account->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('resetPassword');

        $component->assertHasErrors('password');
        $this->assertNotSame(
            'The password field is invalid.',
            $component->errors()->first('password'),
        );
    }

    #[Test]
    public function it_closes_every_session_of_the_account_when_the_password_changes(): void
    {
        $account = User::factory()->active()->create();
        $sessions = SsoSession::factory()->for($account)->count(2)->create();
        $other = SsoSession::factory()->create();

        $this->reset($account, Password::broker()->createToken($account))->assertHasNoErrors();

        foreach ($sessions as $session) {
            $this->assertFalse($session->fresh()->isAlive());
        }

        $this->assertTrue($other->fresh()->isAlive());
    }

    private function reset(User $account, string $token): Testable
    {
        return Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $account->email)
            ->set('password', self::NEW_PASSWORD)
            ->set('password_confirmation', self::NEW_PASSWORD)
            ->call('resetPassword');
    }
}
