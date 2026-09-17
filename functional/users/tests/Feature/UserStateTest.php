<?php

namespace Functional\Users\Tests\Feature;

use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\IllegalUserTransition;
use Functional\Users\Models\User;
use Functional\Users\States\ActiveState;
use Functional\Users\States\DisabledState;
use Functional\Users\States\InvitedState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserStateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_each_status_to_its_own_state_class(): void
    {
        $this->assertInstanceOf(InvitedState::class, User::factory()->invited()->create()->state());
        $this->assertInstanceOf(ActiveState::class, User::factory()->active()->create()->state());
        $this->assertInstanceOf(DisabledState::class, User::factory()->disabled()->create()->state());
    }

    /** @return array<string, array{UserStatus, bool, bool, bool}> */
    public static function accountBehaviors(): array
    {
        return [
            'invited' => [UserStatus::Invited, false, false, true],
            'active' => [UserStatus::Active, true, true, true],
            'disabled' => [UserStatus::Disabled, false, false, false],
        ];
    }

    #[Test]
    #[DataProvider('accountBehaviors')]
    public function it_carries_the_behavior_of_its_status(
        UserStatus $status,
        bool $authenticates,
        bool $appears,
        bool $consumesSeat,
    ): void {
        $user = User::factory()->create(['status' => $status]);

        $this->assertSame($authenticates, $user->canAuthenticate());
        $this->assertSame($appears, $user->appearsInAccesses());
        $this->assertSame($consumesSeat, $user->consumesSeat());
    }

    #[Test]
    public function it_activates_an_invited_account(): void
    {
        $user = User::factory()->invited()->create();

        $user->state()->activate();

        $this->assertSame(UserStatus::Active, $user->fresh()->status);
    }

    #[Test]
    public function it_disables_an_active_account_and_stamps_the_moment(): void
    {
        $user = User::factory()->active()->create();

        $user->state()->disable();

        $this->assertSame(UserStatus::Disabled, $user->fresh()->status);
        $this->assertNotNull($user->fresh()->disabled_at);
    }

    #[Test]
    public function it_brings_a_disabled_account_back_and_clears_the_stamp(): void
    {
        $user = User::factory()->disabled()->create();

        $user->state()->activate();

        $this->assertSame(UserStatus::Active, $user->fresh()->status);
        $this->assertNull($user->fresh()->disabled_at);
    }

    #[Test]
    public function it_never_turns_a_disabled_account_back_into_an_invitation(): void
    {
        $user = User::factory()->disabled()->create();

        $this->expectException(IllegalUserTransition::class);

        $user->state()->invite();
    }

    #[Test]
    public function it_refuses_to_disable_an_account_that_never_activated(): void
    {
        $user = User::factory()->invited()->create();

        $this->expectException(IllegalUserTransition::class);

        $user->state()->disable();
    }

    #[Test]
    public function it_refuses_to_activate_an_account_that_is_already_active(): void
    {
        $user = User::factory()->active()->create();

        $this->expectException(IllegalUserTransition::class);

        $user->state()->activate();
    }

    #[Test]
    public function it_counts_invited_and_active_accounts_as_holding_a_seat(): void
    {
        $organization = User::factory()->create()->organization;
        User::factory()->for($organization)->invited()->create();
        User::factory()->for($organization)->disabled()->create();

        $this->assertSame(2, $organization->users()->consumingSeat()->count());
        $this->assertSame(1, $organization->users()->active()->count());
    }
}
