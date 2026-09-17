<?php

namespace Technical\Audit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Audit\Models\SecurityEvent;

/** @extends Factory<SecurityEvent> */
class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => SecurityEventType::AuthenticationSucceeded,
            'actor_id' => null,
            'organization_id' => null,
            'subject_type' => null,
            'subject_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => [],
            'created_at' => now(),
        ];
    }

    public function ofType(SecurityEventType $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function recordedMonthsAgo(int $months): static
    {
        return $this->state(['created_at' => now()->subMonths($months)]);
    }
}
