<?php

namespace Technical\Oidc\Database\Factories;

use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Technical\Oidc\Models\SsoSession;

/** @extends Factory<SsoSession> */
class SsoSessionFactory extends Factory
{
    protected $model = SsoSession::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->active(),
            'laravel_session_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'started_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addHours(8),
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state([
            'started_at' => now()->subHours(9),
            'last_seen_at' => now()->subHours(9),
            'expires_at' => now()->subHour(),
        ]);
    }
}
