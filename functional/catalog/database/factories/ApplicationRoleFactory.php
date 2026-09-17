<?php

namespace Functional\Catalog\Database\Factories;

use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ApplicationRole> */
class ApplicationRoleFactory extends Factory
{
    protected $model = ApplicationRole::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $label = Str::title(faker()->unique()->words(2));

        return [
            'application_id' => Application::factory(),
            'key' => Str::slug($label),
            'label' => $label,
            'description' => null,
        ];
    }
}
