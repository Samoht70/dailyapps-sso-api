<?php

namespace Functional\Users\Http\Controllers;

use Functional\Users\Actions\DisableUser;
use Functional\Users\Actions\EnableUser;
use Functional\Users\Actions\InviteUser;
use Functional\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTransitionsController
{
    public function disable(Request $request, User $user, DisableUser $disable): JsonResponse
    {
        $this->authorizeOver($request, $user);

        return response()->json(['data' => ['status' => $disable($user, $request->user())->status]]);
    }

    public function enable(Request $request, User $user, EnableUser $enable): JsonResponse
    {
        $this->authorizeOver($request, $user);

        return response()->json(['data' => ['status' => $enable($user, $request->user())->status]]);
    }

    public function resendInvitation(Request $request, User $user, InviteUser $invite): JsonResponse
    {
        $this->authorizeOver($request, $user);

        $invite(
            $user->organization,
            $user->email,
            $user->name,
            $user->organization_role,
            $request->user(),
        );

        return response()->json(status: 202);
    }

    private function authorizeOver(Request $request, User $user): void
    {
        abort_unless($request->user()->can('update', $user), 403);
    }
}
