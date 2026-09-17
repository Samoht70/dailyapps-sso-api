<?php

namespace Functional\Users\Rest\Resources;

use Functional\Users\Models\User;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Permissions\Rest\ControlsTheQuery;

class UserResource extends Resource
{
    use ControlsTheQuery;

    public static $model = User::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id', 'organization_id', 'name', 'email', 'status',
            'organization_role', 'disabled_at', 'last_authenticated_at',
            'created_at', 'updated_at',
        ];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
