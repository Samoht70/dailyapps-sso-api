<?php

namespace Functional\Users\States;

use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\IllegalUserTransition;
use Functional\Users\Models\User;

abstract class UserState
{
    public function __construct(protected readonly User $user) {}

    abstract public function status(): UserStatus;

    abstract public function canAuthenticate(): bool;

    abstract public function appearsInAccesses(): bool;

    abstract public function consumesSeat(): bool;

    public function activate(): User
    {
        throw IllegalUserTransition::between($this->status(), UserStatus::Active);
    }

    public function disable(): User
    {
        throw IllegalUserTransition::between($this->status(), UserStatus::Disabled);
    }

    public function invite(): User
    {
        throw IllegalUserTransition::between($this->status(), UserStatus::Invited);
    }

    protected function moveTo(UserStatus $target): User
    {
        $this->user->forceFill([
            'status' => $target,
            'disabled_at' => $target === UserStatus::Disabled ? now() : null,
        ])->save();

        return $this->user;
    }
}
