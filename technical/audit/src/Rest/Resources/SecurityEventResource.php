<?php

namespace Technical\Audit\Rest\Resources;

use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Audit\Models\SecurityEvent;
use Technical\Permissions\Rest\ControlsTheQuery;

class SecurityEventResource extends Resource
{
    use ControlsTheQuery;

    public static $model = SecurityEvent::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id', 'type', 'actor_id', 'organization_id', 'subject_type',
            'subject_id', 'ip_address', 'user_agent', 'payload', 'created_at',
        ];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
