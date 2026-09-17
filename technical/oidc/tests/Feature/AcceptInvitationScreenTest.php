<?php

namespace Technical\Oidc\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Actions\InviteUser;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\Invitation;
use Functional\Users\Models\User;
use Functional\Users\Notifications\UserInvited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Livewire\AcceptInvitation;
use Tests\TestCase;

class AcceptInvitationScreenTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Brand-New-Horse-42!';

    private Organization $organization;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->organization = Organization::factory()->client()->create();
        $this->token = $this->inviteAndCaptureToken('camille@acme.test');
    }

    #[Test]
    public function it_serves_the_invitation_screen_for_a_pending_invitation(): void
    {
        $this->withoutVite()
            ->get("/invitations/{$this->token}")
            ->assertOk()
            ->assertSeeLivewire(AcceptInvitation::class);
    }

    #[Test]
    public function it_answers_gone_for_an_expired_invitation(): void
    {
        Invitation::query()->where('email', 'camille@acme.test')->update(['expires_at' => now()->subDay()]);

        $this->withoutVite()->get("/invitations/{$this->token}")->assertStatus(410);
    }

    #[Test]
    public function it_answers_gone_for_an_invitation_already_spent(): void
    {
        Invitation::query()->where('email', 'camille@acme.test')->update(['accepted_at' => now()]);

        $this->withoutVite()->get("/invitations/{$this->token}")->assertStatus(410);
    }

    #[Test]
    public function it_answers_gone_for_a_token_nobody_issued(): void
    {
        $this->withoutVite()->get('/invitations/'.Invitation::freshToken())->assertStatus(410);
    }

    #[Test]
    public function it_activates_the_account_and_opens_the_session_in_the_same_move(): void
    {
        Livewire::test(AcceptInvitation::class, ['token' => $this->token])
            ->set('password', self::PASSWORD)
            ->set('password_confirmation', self::PASSWORD)
            ->call('activate')
            ->assertHasNoErrors()
            ->assertRedirect(route('account'));

        $account = User::query()->where('email', 'camille@acme.test')->sole();

        $this->assertSame(UserStatus::Active, $account->status);
        $this->assertTrue(Auth::check());
        $this->assertTrue($account->is(Auth::user()));
    }

    #[Test]
    public function it_turns_away_a_password_that_is_too_weak(): void
    {
        Livewire::test(AcceptInvitation::class, ['token' => $this->token])
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('activate')
            ->assertHasErrors('password');

        $this->assertFalse(Auth::check());
    }

    #[Test]
    public function it_journals_the_acceptance(): void
    {
        Livewire::test(AcceptInvitation::class, ['token' => $this->token])
            ->set('password', self::PASSWORD)
            ->set('password_confirmation', self::PASSWORD)
            ->call('activate');

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::InvitationAccepted->value,
        ]);
    }

    #[Test]
    public function it_spends_the_invitation_on_its_first_use(): void
    {
        $this->accept();
        Auth::logout();

        $this->withoutVite()->get("/invitations/{$this->token}")->assertStatus(410);
    }

    private function accept(): void
    {
        Livewire::test(AcceptInvitation::class, ['token' => $this->token])
            ->set('password', self::PASSWORD)
            ->set('password_confirmation', self::PASSWORD)
            ->call('activate');
    }

    private function inviteAndCaptureToken(string $email): string
    {
        $token = null;

        Notification::assertNothingSent();

        app(InviteUser::class)($this->organization, $email, 'Camille Acme');

        Notification::assertSentTo(
            User::query()->where('email', $email)->sole(),
            UserInvited::class,
            function ($notification) use (&$token): bool {
                $token = (new \ReflectionProperty($notification, 'token'))->getValue($notification);

                return true;
            },
        );

        return $token;
    }
}
