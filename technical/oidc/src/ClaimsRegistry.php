<?php

namespace Technical\Oidc;

use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Contracts\ClaimsProvider;
use Technical\Oidc\Models\Client;

class ClaimsRegistry
{
    /** @var list<ClaimsProvider> */
    private array $providers = [];

    public function register(ClaimsProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /** @return list<ClaimsProvider> */
    public function providers(): array
    {
        return $this->providers;
    }

    /** @return array<string, mixed> */
    public function claimsFor(Authenticatable $user, Client $client): array
    {
        $claims = [];

        foreach ($this->providers as $provider) {
            $claims = array_merge($claims, $provider->claimsFor($user, $client));
        }

        return $claims;
    }
}
