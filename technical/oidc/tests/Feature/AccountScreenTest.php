<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Livewire\Account;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class AccountScreenTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Correct-Horse-42!';

    #[Test]
    public function it_keeps_the_profile_screen_behind_authentication(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
    }

    #[Test]
    public function it_serves_the_profile_screen_to_the_account_itself(): void
    {
        $this->actingAs($this->account())
            ->withoutVite()
            ->get('/account')
            ->assertOk()
            ->assertSeeLivewire(Account::class);
    }

    #[Test]
    public function it_lets_the_account_change_its_own_name_and_address(): void
    {
        $account = $this->account();

        Livewire::actingAs($account)
            ->test(Account::class)
            ->set('name', 'Camille Nouveau')
            ->set('email', 'camille.nouveau@acme.test')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('Camille Nouveau', $account->fresh()->name);
        $this->assertSame('camille.nouveau@acme.test', $account->fresh()->email);
    }

    #[Test]
    public function it_refuses_an_address_another_account_already_holds(): void
    {
        $account = $this->account();
        $other = User::factory()->active()->create();

        Livewire::actingAs($account)
            ->test(Account::class)
            ->set('email', $other->email)
            ->call('saveProfile')
            ->assertHasErrors('email');
    }

    #[Test]
    public function it_changes_the_password_without_going_through_an_administrator(): void
    {
        $account = $this->account();

        Livewire::actingAs($account)
            ->test(Account::class)
            ->set('current_password', self::PASSWORD)
            ->set('password', 'Brand-New-Horse-42!')
            ->set('password_confirmation', 'Brand-New-Horse-42!')
            ->call('changePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('Brand-New-Horse-42!', $account->fresh()->password));
    }

    #[Test]
    public function it_asks_for_the_current_password_before_changing_it(): void
    {
        Livewire::actingAs($this->account())
            ->test(Account::class)
            ->set('current_password', 'Wrong-Horse-42!')
            ->set('password', 'Brand-New-Horse-42!')
            ->set('password_confirmation', 'Brand-New-Horse-42!')
            ->call('changePassword')
            ->assertHasErrors('current_password');
    }

    #[Test]
    public function it_turns_away_a_new_password_that_is_too_weak(): void
    {
        Livewire::actingAs($this->account())
            ->test(Account::class)
            ->set('current_password', self::PASSWORD)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('changePassword')
            ->assertHasErrors('password');
    }

    #[Test]
    public function it_closes_the_other_sessions_but_keeps_the_one_in_use(): void
    {
        $account = $this->account();
        $current = SsoSession::factory()->for($account)->create();
        $elsewhere = SsoSession::factory()->for($account)->create();

        session()->put(SsoSession::SESSION_KEY, $current->getKey());

        Livewire::actingAs($account)
            ->test(Account::class)
            ->set('current_password', self::PASSWORD)
            ->set('password', 'Brand-New-Horse-42!')
            ->set('password_confirmation', 'Brand-New-Horse-42!')
            ->call('changePassword')
            ->assertHasNoErrors();

        $this->assertTrue($current->fresh()->isAlive());
        $this->assertFalse($elsewhere->fresh()->isAlive());
    }

    #[Test]
    public function it_journals_the_password_change(): void
    {
        $account = $this->account();

        Livewire::actingAs($account)
            ->test(Account::class)
            ->set('current_password', self::PASSWORD)
            ->set('password', 'Brand-New-Horse-42!')
            ->set('password_confirmation', 'Brand-New-Horse-42!')
            ->call('changePassword');

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::PasswordChanged->value,
            'actor_id' => $account->getKey(),
        ]);
    }

    #[Test]
    public function it_revokes_the_sso_session_on_logout(): void
    {
        $account = $this->account();
        $this->actingAs($account);
        $session = SsoSession::factory()->for($account)->create();
        session()->put(SsoSession::SESSION_KEY, $session->getKey());

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertFalse($session->fresh()->isAlive());
        $this->assertFalse(Auth::check());
    }

    private function account(): User
    {
        return User::factory()->active()->create(['password' => Hash::make(self::PASSWORD)]);
    }
}
