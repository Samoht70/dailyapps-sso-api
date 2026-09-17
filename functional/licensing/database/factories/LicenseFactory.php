<?php

namespace Functional\Licensing\Database\Factories;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<License> */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'application_id' => Application::factory()->published(),
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'seats' => 10,
        ];
    }

    public function valid(): static
    {
        return $this->state([
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'starts_on' => now()->subYears(2)->toDateString(),
            'ends_on' => now()->subDay()->toDateString(),
        ]);
    }

    public function perpetual(): static
    {
        return $this->state([
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => null,
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(['seats' => 1]);
    }
}
