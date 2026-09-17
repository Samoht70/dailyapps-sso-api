<?php

namespace Technical\Oidc\Database\Factories;

use Functional\Catalog\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Technical\Oidc\Models\SsoSession;
use Technical\Oidc\Models\SsoSessionParticipant;

/** @extends Factory<SsoSessionParticipant> */
class SsoSessionParticipantFactory extends Factory
{
    protected $model = SsoSessionParticipant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'sso_session_id' => SsoSession::factory(),
            'application_id' => Application::factory()->published(),
            'first_seen_at' => now(),
            'logout_pushed_at' => null,
        ];
    }
}
