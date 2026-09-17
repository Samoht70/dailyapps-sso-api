<?php

namespace Functional\Licensing\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasValidity
{
    public function initializeHasValidity(): void
    {
        $this->mergeCasts([
            'starts_on' => 'date',
            'ends_on' => 'date',
            'seats' => 'integer',
        ]);
    }

    public function isValid(?CarbonInterface $on = null): bool
    {
        return static::query()->whereKey($this->getKey())->valid($on)->exists();
    }

    #[Scope]
    protected function valid(Builder $query, ?CarbonInterface $on = null): void
    {
        $day = ($on ?? now())->toDateString();

        $query->whereDate('starts_on', '<=', $day)
            ->where(fn (Builder $nested) => $nested
                ->whereNull('ends_on')
                ->orWhereDate('ends_on', '>=', $day))
            ->whereHas('organization', fn (Builder $organization) => $organization->active())
            ->whereHas('application', fn (Builder $application) => $application->published());
    }
}
