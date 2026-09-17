<?php

namespace Functional\Organizations\Models;

use Functional\Licensing\Models\License;
use Functional\Organizations\Database\Factories\OrganizationFactory;
use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Enums\OrganizationStatus;
use Functional\Organizations\Models\Concerns\Suspendable;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'kind'])]
#[UseFactory(OrganizationFactory::class)]
class Organization extends Model
{
    use HasFactory;
    use HasUuids;
    use Suspendable;

    /** @var array<string, string> */
    protected $attributes = [
        'status' => OrganizationStatus::Active->value,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => OrganizationKind::class,
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<License, $this> */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
