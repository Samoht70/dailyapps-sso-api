<?php

namespace Functional\Users\Database\Factories;

use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invitation> */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email' => faker()->unique()->email(),
            'organization_id' => Organization::factory(),
            'organization_role' => OrganizationRole::Member,
            'invited_by_id' => null,
            'token_hash' => Invitation::hash(Invitation::freshToken()),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function accepted(): static
    {
        return $this->state(['accepted_at' => now()]);
    }
}
