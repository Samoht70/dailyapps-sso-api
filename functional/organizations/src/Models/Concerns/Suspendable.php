<?php

namespace Functional\Organizations\Models\Concerns;

use Functional\Organizations\Enums\OrganizationStatus;
use Functional\Organizations\Exceptions\IllegalOrganizationTransition;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait Suspendable
{
    public function initializeSuspendable(): void
    {
        $this->mergeCasts([
            'status' => OrganizationStatus::class,
            'suspended_at' => 'datetime',
        ]);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isSuspended(): bool
    {
        return ! $this->isActive();
    }

    public function suspend(): static
    {
        if (! $this->kind->canBeSuspended()) {
            throw IllegalOrganizationTransition::operatorCannotBeSuspended();
        }

        return $this->moveTo(OrganizationStatus::Suspended);
    }

    public function activate(): static
    {
        return $this->moveTo(OrganizationStatus::Active);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', OrganizationStatus::Active);
    }

    private function moveTo(OrganizationStatus $target): static
    {
        if (! $this->status->canTransitionTo($target)) {
            throw IllegalOrganizationTransition::between($this->status, $target);
        }

        $this->forceFill([
            'status' => $target,
            'suspended_at' => $target === OrganizationStatus::Suspended ? now() : null,
        ])->save();

        return $this;
    }
}
