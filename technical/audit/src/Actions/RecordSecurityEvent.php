<?php

namespace Technical\Audit\Actions;

use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Audit\Models\SecurityEvent;

class RecordSecurityEvent
{
    /** @param  array<string, mixed>  $payload */
    public function __invoke(
        SecurityEventType $type,
        ?User $actor = null,
        ?Organization $organization = null,
        ?Model $subject = null,
        array $payload = [],
    ): SecurityEvent {
        $event = new SecurityEvent([
            'type' => $type,
            'actor_id' => $actor?->getKey(),
            'organization_id' => ($organization ?? $actor?->organization)?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload,
        ]);

        if ($subject !== null) {
            $event->subject()->associate($subject);
        }

        $event->save();

        return $event;
    }
}
