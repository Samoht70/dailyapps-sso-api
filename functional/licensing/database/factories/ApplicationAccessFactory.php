<?php

namespace Functional\Licensing\Database\Factories;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationAccess> */
class ApplicationAccessFactory extends Factory
{
    protected $model = ApplicationAccess::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'application_id' => Application::factory()->published(),
            'granted_by_id' => null,
            'granted_at' => now(),
        ];
    }
}
