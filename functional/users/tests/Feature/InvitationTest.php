<?php

namespace Functional\Users\Tests\Feature;

use Functional\Organizations\Models\Organization;
use Functional\Users\Actions\InviteUser;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\EmailAlreadyAttached;
use Functional\Users\Models\Invitation;
use Functional\Users\Models\User;
use Functional\Users\Notifications\UserInvited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Technical\Audit\Enums\SecurityEventType;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->organization = Organization::factory()->client()->create();
    }

    #[Test]
    public function it_opens_an_invited_account_and_sends_the_invitation(): void
    {
        $invitation = $this->invite('camille@acme.test');

        $account = User::query()->where('email', 'camille@acme.test')->sole();

        $this->assertSame(UserStatus::Invited, $account->status);
        $this->assertNull($account->password);
        $this->assertTrue($invitation->isPending());
        Notification::assertSentTo($account, UserInvited::class);
    }

    #[Test]
    public function it_keeps_only_a_hash_of_the_token(): void
    {
        $invitation = $this->invite('camille@acme.test');

        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertDatabaseMissing('invitations', ['token_hash' => '']);
    }

    #[Test]
    public function it_journals_the_invitation(): void
    {
        $this->invite('camille@acme.test');

        $this->assertDatabaseHas('security_events', [
            'type' => SecurityEventType::InvitationSent->value,
            'organization_id' => $this->organization->getKey(),
        ]);
    }

    #[Test]
    public function it_refuses_an_address_already_attached_to_an_organization(): void
    {
        User::factory()->active()->create(['email' => 'camille@acme.test']);

        $this->expectException(EmailAlreadyAttached::class);

        $this->invite('camille@acme.test');
    }

    #[Test]
    public function it_answers_that_refusal_with_a_machine_code(): void
    {
        User::factory()->active()->create(['email' => 'camille@acme.test']);

        try {
            $this->invite('camille@acme.test');
            $this->fail('The invitation should have been refused.');
        } catch (EmailAlreadyAttached $refusal) {
            $this->assertSame('email_already_attached', $refusal->machineCode());
        }
    }

    #[Test]
    public function it_refuses_to_move_an_invited_account_to_another_organization(): void
    {
        $this->invite('camille@acme.test');

        $this->expectException(EmailAlreadyAttached::class);

        app(InviteUser::class)(
            Organization::factory()->client()->create(),
            'camille@acme.test',
            'Camille',
        );
    }

    #[Test]
    public function it_invalidates_the_previous_invitation_when_a_newer_one_is_sent(): void
    {
        $first = $this->invite('camille@acme.test');
        $second = $this->invite('camille@acme.test');

        $this->assertFalse($first->fresh()->isPending());
        $this->assertTrue($second->isPending());
    }

    #[Test]
    public function it_spends_an_invitation_on_acceptance(): void
    {
        $invitation = $this->invite('camille@acme.test');

        $account = $invitation->accept('Brand-New-Horse-42!');

        $this->assertSame(UserStatus::Active, $account->status);
        $this->assertFalse($invitation->fresh()->isPending());
        $this->assertTrue($account->canAuthenticate());
    }

    #[Test]
    public function it_holds_an_expired_invitation_as_spent(): void
    {
        $invitation = Invitation::factory()->for($this->organization)->expired()->create();

        $this->assertFalse($invitation->isPending());
        $this->assertSame(0, Invitation::query()->pending()->count());
    }

    #[Test]
    public function it_holds_an_accepted_invitation_as_spent(): void
    {
        $invitation = Invitation::factory()->for($this->organization)->accepted()->create();

        $this->assertFalse($invitation->isPending());
        $this->assertSame(0, Invitation::query()->pending()->count());
    }

    private function invite(string $email): Invitation
    {
        return app(InviteUser::class)(
            $this->organization,
            $email,
            'Camille Acme',
            OrganizationRole::Member,
        );
    }
}
