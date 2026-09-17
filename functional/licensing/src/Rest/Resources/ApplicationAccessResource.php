<?php

namespace Functional\Licensing\Rest\Resources;

use Functional\Licensing\Models\ApplicationAccess;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Permissions\Rest\ControlsTheQuery;

class ApplicationAccessResource extends Resource
{
    use ControlsTheQuery;

    public static $model = ApplicationAccess::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id', 'user_id', 'application_id', 'granted_by_id', 'granted_at',
            'created_at', 'updated_at',
        ];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
