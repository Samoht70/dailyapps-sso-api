<?php

namespace Functional\Catalog\Rest\Resources;

use Functional\Catalog\Models\Application;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource;
use Technical\Permissions\Rest\ControlsTheQuery;

class ApplicationResource extends Resource
{
    use ControlsTheQuery;

    public static $model = Application::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id', 'slug', 'name', 'description', 'logo_url', 'home_url',
            'backchannel_logout_url', 'status', 'created_at', 'updated_at',
        ];
    }

    /** @return array<string, mixed> */
    public function relations(RestRequest $request): array
    {
        return [];
    }
}
