<?php

namespace Functional\Catalog\Database\Factories;

use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Technical\Oidc\Models\Client;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = Str::title(faker()->unique()->words(2));

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => faker()->sentences(2),
            'logo_url' => null,
            'home_url' => faker()->url(),
            'backchannel_logout_url' => null,
            'status' => ApplicationStatus::Draft,
            'oauth_client_id' => Client::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ApplicationStatus::Draft]);
    }

    public function published(): static
    {
        return $this->state(['status' => ApplicationStatus::Published]);
    }

    public function retired(): static
    {
        return $this->state(['status' => ApplicationStatus::Retired]);
    }

    public function withBackchannelLogout(): static
    {
        return $this->state(['backchannel_logout_url' => faker()->url().'/logout']);
    }
}
