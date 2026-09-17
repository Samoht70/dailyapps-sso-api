<?php

namespace Functional\Users\Tests\Unit;

use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\IllegalUserTransition;
use Functional\Users\Models\User;
use Functional\Users\States\ActiveState;
use Functional\Users\States\DisabledState;
use Functional\Users\States\InvitedState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserStateTest extends TestCase
{
    #[Test]
    #[DataProvider('statuses')]
    public function it_resolves_a_status_to_its_state_without_touching_the_database(
        UserStatus $status,
        string $expected,
    ): void {
        $this->assertInstanceOf($expected, $this->account($status)->state());
    }

    /** @return array<string, array{UserStatus, class-string}> */
    public static function statuses(): array
    {
        return [
            'invited' => [UserStatus::Invited, InvitedState::class],
            'active' => [UserStatus::Active, ActiveState::class],
            'disabled' => [UserStatus::Disabled, DisabledState::class],
        ];
    }

    #[Test]
    public function it_lets_an_active_account_authenticate_and_no_other(): void
    {
        $this->assertTrue($this->account(UserStatus::Active)->canAuthenticate());
        $this->assertFalse($this->account(UserStatus::Invited)->canAuthenticate());
        $this->assertFalse($this->account(UserStatus::Disabled)->canAuthenticate());
    }

    #[Test]
    public function it_shows_an_active_account_in_the_accesses_and_no_other(): void
    {
        $this->assertTrue($this->account(UserStatus::Active)->appearsInAccesses());
        $this->assertFalse($this->account(UserStatus::Invited)->appearsInAccesses());
        $this->assertFalse($this->account(UserStatus::Disabled)->appearsInAccesses());
    }

    #[Test]
    public function it_holds_a_seat_from_the_invitation_and_frees_it_once_disabled(): void
    {
        $this->assertTrue($this->account(UserStatus::Invited)->consumesSeat());
        $this->assertTrue($this->account(UserStatus::Active)->consumesSeat());
        $this->assertFalse($this->account(UserStatus::Disabled)->consumesSeat());
    }

    #[Test]
    public function it_never_turns_a_disabled_account_back_into_an_invitation(): void
    {
        $this->expectException(IllegalUserTransition::class);

        $this->account(UserStatus::Disabled)->state()->invite();
    }

    #[Test]
    public function it_refuses_to_disable_an_account_that_never_activated(): void
    {
        $this->expectException(IllegalUserTransition::class);

        $this->account(UserStatus::Invited)->state()->disable();
    }

    private function account(UserStatus $status): User
    {
        return (new User)->forceFill(['status' => $status]);
    }
}
