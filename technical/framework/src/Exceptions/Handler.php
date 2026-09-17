<?php

namespace Technical\Framework\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler
{
    /**
     * The contract answers a machine code, never a sentence: an application that
     * has to match on wording breaks the day the wording is improved.
     */
    public static function configure(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $violation, Request $request): ?JsonResponse {
            if (! $violation instanceof BusinessRule || ! $request->expectsJson()) {
                return null;
            }

            return response()->json(
                ['code' => $violation->machineCode(), 'message' => $violation->getMessage()],
                409,
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            return $request->expectsJson()
                ? response()->json(['code' => 'unauthenticated'], 401)
                : null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request): ?JsonResponse {
            return $request->expectsJson()
                ? response()->json(['code' => 'forbidden'], 403)
                : null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request): ?JsonResponse {
            return $request->expectsJson()
                ? response()->json(['code' => 'forbidden'], 403)
                : null;
        });
    }
}
