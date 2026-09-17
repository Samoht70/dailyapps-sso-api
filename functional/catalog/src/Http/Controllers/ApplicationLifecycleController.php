<?php

namespace Functional\Catalog\Http\Controllers;

use Functional\Catalog\Actions\PublishApplication;
use Functional\Catalog\Actions\RetireApplication;
use Functional\Catalog\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Technical\Permissions\Enums\Permission;

class ApplicationLifecycleController
{
    public function publish(Request $request, Application $application, PublishApplication $publish): JsonResponse
    {
        abort_unless($request->user()->can(Permission::PublishApplication->value), 403);

        return response()->json(['data' => ['status' => $publish($application, $request->user())->status]]);
    }

    public function retire(Request $request, Application $application, RetireApplication $retire): JsonResponse
    {
        abort_unless($request->user()->can(Permission::RetireApplication->value), 403);

        return response()->json(['data' => ['status' => $retire($application, $request->user())->status]]);
    }
}
