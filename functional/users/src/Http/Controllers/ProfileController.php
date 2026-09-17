<?php

namespace Functional\Users\Http\Controllers;

use Functional\Users\Events\PasswordChanged;
use Functional\Users\Http\Requests\UpdatePasswordRequest;
use Functional\Users\Http\Requests\UpdateProfileRequest;
use Functional\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController
{
    /**
     * The `/account` screen serves the person in front of a browser; these serve
     * an application acting in their name with an access token.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->profile($request->user())]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $account */
        $account = $request->user();

        $account->update($request->validated());

        return response()->json(['data' => $this->profile($account->fresh())]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $account */
        $account = $request->user();

        $account->forceFill(['password' => Hash::make($request->validated('password'))])->save();

        PasswordChanged::dispatch($account);

        return response()->json(status: 204);
    }

    /** @return array<string, mixed> */
    private function profile(User $account): array
    {
        return [
            'id' => $account->getKey(),
            'name' => $account->name,
            'email' => $account->email,
            'status' => $account->status->value,
            'organization_role' => $account->organization_role->value,
            'organization' => [
                'id' => $account->organization->getKey(),
                'name' => $account->organization->name,
            ],
        ];
    }
}
