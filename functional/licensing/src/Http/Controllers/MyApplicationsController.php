<?php

namespace Functional\Licensing\Http\Controllers;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyApplicationsController
{
    /**
     * Not a CRUD resource: the answer crosses a valid licence, a granted access,
     * the state of the account and the state of the organization. No table
     * projects it, and no answer is cached — a right removed a second ago must
     * be gone from the next call.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $account */
        $account = $request->user();

        if (! $account->appearsInAccesses() || ! $account->organization->isActive()) {
            return response()->json(['data' => []]);
        }

        $applications = Application::query()
            ->published()
            ->whereHas('licenses', fn ($licenses) => $licenses
                ->where('organization_id', $account->organization_id)
                ->valid())
            ->whereHas('accesses', fn ($accesses) => $accesses->where('user_id', $account->getKey()))
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'logo_url', 'home_url']);

        return response()->json([
            'data' => $applications->map(fn (Application $application): array => [
                'slug' => $application->slug,
                'name' => $application->name,
                'logo_url' => $application->logo_url,
                'home_url' => $application->home_url,
            ])->all(),
        ]);
    }
}
