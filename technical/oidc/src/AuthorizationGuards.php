<?php

namespace Technical\Oidc;

use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Contracts\AuthorizationGuard;
use Technical\Oidc\Enums\RefusalReason;
use Technical\Oidc\Models\Client;

class AuthorizationGuards
{
    /** @var array<int, list<AuthorizationGuard>> */
    private array $guards = [];

    /**
     * Guards run from the lowest priority up, so identity is settled before
     * entitlement — a disabled account is never told it merely lacks a licence.
     */
    public function register(AuthorizationGuard $guard, int $priority = 100): void
    {
        $this->guards[$priority][] = $guard;
    }

    public function firstRefusal(Authenticatable $user, Client $client): ?RefusalReason
    {
        ksort($this->guards);

        foreach ($this->guards as $samePriority) {
            foreach ($samePriority as $guard) {
                if ($reason = $guard->refuse($user, $client)) {
                    return $reason;
                }
            }
        }

        return null;
    }
}
