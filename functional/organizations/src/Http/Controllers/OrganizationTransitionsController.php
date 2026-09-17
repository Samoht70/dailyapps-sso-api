<?php

namespace Functional\Organizations\Http\Controllers;

use Functional\Organizations\Actions\ActivateOrganization;
use Functional\Organizations\Actions\SuspendOrganization;
use Functional\Organizations\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Technical\Permissions\Enums\Permission;

class OrganizationTransitionsController
{
    /**
     * Named actions rather than a `PATCH status`: an illegal transition is then
     * not something a client can even ask for.
     */
    public function suspend(Request $request, Organization $organization, SuspendOrganization $suspend): JsonResponse
    {
        abort_unless($request->user()->can(Permission::SuspendOrganization->value), 403);

        return response()->json(['data' => ['status' => $suspend($organization, $request->user())->status]]);
    }

    public function activate(Request $request, Organization $organization, ActivateOrganization $activate): JsonResponse
    {
        abort_unless($request->user()->can(Permission::SuspendOrganization->value), 403);

        return response()->json(['data' => ['status' => $activate($organization)->status]]);
    }
}
