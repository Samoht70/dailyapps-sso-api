<?php

namespace Functional\Organizations\Database\Factories;

use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Enums\OrganizationStatus;
use Functional\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => Str::title(faker()->unique()->words(2)),
            'kind' => OrganizationKind::Client,
            'status' => OrganizationStatus::Active,
            'suspended_at' => null,
        ];
    }

    public function operator(): static
    {
        return $this->state(['kind' => OrganizationKind::Operator]);
    }

    public function client(): static
    {
        return $this->state(['kind' => OrganizationKind::Client]);
    }

    public function suspended(): static
    {
        return $this->state([
            'kind' => OrganizationKind::Client,
            'status' => OrganizationStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }
}
