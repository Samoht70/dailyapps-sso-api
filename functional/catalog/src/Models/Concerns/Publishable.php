<?php

namespace Functional\Catalog\Models\Concerns;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Exceptions\IllegalApplicationTransition;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts(['status' => ApplicationStatus::class]);
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    public function publish(): static
    {
        return $this->moveTo(ApplicationStatus::Published);
    }

    public function retire(): static
    {
        return $this->moveTo(ApplicationStatus::Retired);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ApplicationStatus::Published);
    }

    private function moveTo(ApplicationStatus $target): static
    {
        if (! $this->status->canTransitionTo($target)) {
            throw IllegalApplicationTransition::between($this->status, $target);
        }

        $this->forceFill(['status' => $target])->save();

        return $this;
    }
}
