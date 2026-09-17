<?php

namespace Functional\Users\Models\Concerns;

use Functional\Users\Enums\UserStatus;
use Functional\Users\States\UserState;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasAccountState
{
    public function initializeHasAccountState(): void
    {
        $this->mergeCasts([
            'status' => UserStatus::class,
            'disabled_at' => 'datetime',
        ]);
    }

    public function state(): UserState
    {
        $state = $this->status->stateClass();

        return new $state($this);
    }

    public function canAuthenticate(): bool
    {
        return $this->state()->canAuthenticate();
    }

    public function appearsInAccesses(): bool
    {
        return $this->state()->appearsInAccesses();
    }

    public function consumesSeat(): bool
    {
        return $this->state()->consumesSeat();
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', UserStatus::Active);
    }

    #[Scope]
    protected function consumingSeat(Builder $query): void
    {
        $query->whereIn('status', [UserStatus::Invited, UserStatus::Active]);
    }
}
