<?php

namespace Functional\Organizations\Rest\Resources;

use Functional\Organizations\Models\Organization;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Permissions\Rest\ControlsTheQuery;

class OrganizationResource extends Resource
{
    use ControlsTheQuery;

    public static $model = Organization::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return ['id', 'name', 'kind', 'status', 'suspended_at', 'created_at', 'updated_at'];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
