<?php

namespace Functional\Users\Tests\Feature;

use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Models\SsoSession;
use Tests\TestCase;

class ProfileEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Correct-Horse-42!';

    #[Test]
    public function it_turns_away_a_caller_without_a_token(): void
    {
        $this->getJson('/me')->assertUnauthorized();
        $this->patchJson('/me')->assertUnauthorized();
        $this->putJson('/me/password')->assertUnauthorized();
    }

    #[Test]
    public function it_answers_the_profile_of_the_caller(): void
    {
        $account = $this->signedIn();

        $this->getJson('/me')->assertOk()->assertJsonPath('data.id', $account->getKey())
            ->assertJsonPath('data.email', $account->email)
            ->assertJsonPath('data.organization.id', $account->organization->getKey());
    }

    #[Test]
    public function it_never_answers_a_password(): void
    {
        $this->signedIn();

        $this->getJson('/me')->assertOk()->assertJsonMissingPath('data.password');
    }

    #[Test]
    public function it_changes_the_name_and_the_address(): void
    {
        $account = $this->signedIn();

        $this->patchJson('/me', ['name' => 'Camille Nouveau'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Camille Nouveau');

        $this->assertSame('Camille Nouveau', $account->fresh()->name);
    }

    #[Test]
    public function it_refuses_an_address_another_account_already_holds(): void
    {
        $this->signedIn();
        $other = User::factory()->active()->create();

        $this->patchJson('/me', ['email' => $other->email])->assertUnprocessable();
    }

    #[Test]
    public function it_changes_the_password_of_the_caller(): void
    {
        $account = $this->signedIn();

        $this->putJson('/me/password', [
            'current_password' => self::PASSWORD,
            'password' => 'Brand-New-Horse-42!',
            'password_confirmation' => 'Brand-New-Horse-42!',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('Brand-New-Horse-42!', $account->fresh()->password));
    }

    #[Test]
    public function it_asks_for_the_current_password(): void
    {
        $this->signedIn();

        $this->putJson('/me/password', [
            'current_password' => 'Wrong-Horse-42!',
            'password' => 'Brand-New-Horse-42!',
            'password_confirmation' => 'Brand-New-Horse-42!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    #[Test]
    public function it_turns_away_a_password_that_is_too_weak(): void
    {
        $this->signedIn();

        $this->putJson('/me/password', [
            'current_password' => self::PASSWORD,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    #[Test]
    public function it_closes_every_session_when_the_password_changes(): void
    {
        $account = $this->signedIn();
        $sessions = SsoSession::factory()->for($account)->count(2)->create();

        $this->putJson('/me/password', [
            'current_password' => self::PASSWORD,
            'password' => 'Brand-New-Horse-42!',
            'password_confirmation' => 'Brand-New-Horse-42!',
        ])->assertNoContent();

        foreach ($sessions as $session) {
            $this->assertFalse($session->fresh()->isAlive());
        }
    }

    #[Test]
    public function it_journals_the_password_change(): void
    {
        $account = $this->signedIn();

        $this->putJson('/me/password', [
            'current_password' => self::PASSWORD,
            'password' => 'Brand-New-Horse-42!',
            'password_confirmation' => 'Brand-New-Horse-42!',
        ]);

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::PasswordChanged->value,
            'actor_id' => $account->getKey(),
        ]);
    }

    private function signedIn(): User
    {
        $account = User::factory()->active()->create(['password' => Hash::make(self::PASSWORD)]);

        Passport::actingAs($account);

        return $account;
    }
}
