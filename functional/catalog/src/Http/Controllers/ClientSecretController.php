<?php

namespace Functional\Catalog\Http\Controllers;

use Functional\Catalog\Actions\RevokeClientSecret;
use Functional\Catalog\Actions\RotateClientSecret;
use Functional\Catalog\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Technical\Permissions\Enums\Permission;

class ClientSecretController
{
    /**
     * The secret rides in this answer and nowhere else — the database holds a
     * hash, so losing it means rotating again.
     */
    public function rotate(Request $request, Application $application, RotateClientSecret $rotate): JsonResponse
    {
        abort_unless($request->user()->can(Permission::RotateClientSecret->value), 403);

        return response()->json(['data' => ['client_secret' => $rotate($application, $request->user())]]);
    }

    public function revoke(Request $request, Application $application, RevokeClientSecret $revoke): JsonResponse
    {
        abort_unless($request->user()->can(Permission::RotateClientSecret->value), 403);

        $revoke($application, $request->user());

        return response()->json(status: 204);
    }
}
