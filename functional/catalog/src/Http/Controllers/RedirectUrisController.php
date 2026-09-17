<?php

namespace Functional\Catalog\Http\Controllers;

use Functional\Catalog\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Technical\Permissions\Enums\Permission;

class RedirectUrisController
{
    /**
     * The return addresses live on the Passport client, which is what actually
     * enforces them. Copying them onto `applications` would only guarantee that
     * one day the two lists disagree.
     */
    public function show(Request $request, Application $application): JsonResponse
    {
        abort_unless($request->user()->can(Permission::DeclareApplication->value), 403);

        return response()->json(['data' => ['redirect_uris' => $application->redirect_uris]]);
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        abort_unless($request->user()->can(Permission::DeclareApplication->value), 403);

        $validated = $request->validate([
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'string', 'url'],
        ]);

        $application->oauthClient->forceFill($validated)->save();

        return response()->json(['data' => ['redirect_uris' => $application->oauthClient->redirect_uris]]);
    }
}
