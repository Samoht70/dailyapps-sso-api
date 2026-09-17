<?php

namespace Technical\Oidc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenIDConnect\Laravel\DiscoveryController as OpenIdConnectDiscoveryController;
use OpenIDConnect\Laravel\LaravelCurrentRequestService;

class DiscoveryController extends OpenIdConnectDiscoveryController
{
    /**
     * The package advertises `plain` alongside `S256`, which this server
     * refuses. A discovery document that promises what the server turns away
     * sends every new client into a failure it cannot diagnose.
     */
    public function __invoke(Request $request, LaravelCurrentRequestService $currentRequestService): JsonResponse
    {
        $document = parent::__invoke($request, $currentRequestService)->getData(true);

        $document['code_challenge_methods_supported'] = [config('passport.code_challenge_method')];

        return response()->json($document, 200, [], JSON_PRETTY_PRINT);
    }
}
