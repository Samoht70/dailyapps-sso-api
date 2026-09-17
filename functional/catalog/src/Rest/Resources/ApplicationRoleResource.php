<?php

namespace Functional\Catalog\Rest\Resources;

use Functional\Catalog\Models\ApplicationRole;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Permissions\Rest\ControlsTheQuery;

class ApplicationRoleResource extends Resource
{
    use ControlsTheQuery;

    public static $model = ApplicationRole::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return ['id', 'application_id', 'key', 'label', 'description', 'created_at', 'updated_at'];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
