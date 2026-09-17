<?php

namespace Functional\Users\Database\Factories;

use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => faker()->name(),
            'email' => faker()->unique()->email(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'organization_role' => OrganizationRole::Member,
            'disabled_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function invited(): static
    {
        return $this->state([
            'status' => UserStatus::Invited,
            'password' => null,
            'email_verified_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => UserStatus::Active,
            'disabled_at' => null,
        ]);
    }

    public function disabled(): static
    {
        return $this->state([
            'status' => UserStatus::Disabled,
            'disabled_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(['organization_role' => OrganizationRole::Admin]);
    }
}
