<?php

namespace Functional\Licensing\Rest\Resources;

use Functional\Licensing\Models\License;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Permissions\Rest\ControlsTheQuery;

class LicenseResource extends Resource
{
    use ControlsTheQuery;

    public static $model = License::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id', 'organization_id', 'application_id', 'starts_on', 'ends_on',
            'seats', 'created_at', 'updated_at',
        ];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
