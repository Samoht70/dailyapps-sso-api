<?php

namespace Functional\Licensing\Models;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Database\Factories\LicenseFactory;
use Functional\Licensing\Models\Concerns\HasValidity;
use Functional\Licensing\Rest\Policies\LicensePolicy;
use Functional\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

#[Fillable(['organization_id', 'application_id', 'starts_on', 'ends_on', 'seats'])]
#[UsePolicy(LicensePolicy::class)]
#[UseFactory(LicenseFactory::class)]
class License extends Model
{
    use HasControl;
    use HasFactory;
    use HasUuids;
    use HasValidity;

    public function occupiedSeats(): int
    {
        return ApplicationAccess::query()
            ->where('application_id', $this->application_id)
            ->whereHas('user', fn ($user) => $user
                ->where('organization_id', $this->organization_id)
                ->consumingSeat())
            ->count();
    }

    public function hasFreeSeat(): bool
    {
        return $this->occupiedSeats() < $this->seats;
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
