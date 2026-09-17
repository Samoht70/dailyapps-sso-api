<?php

namespace Technical\Oidc\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Models\Client;

interface ClaimsProvider
{
    /**
     * The claims this provider contributes to the token minted for that client.
     *
     * @return array<string, mixed>
     */
    public function claimsFor(Authenticatable $user, Client $client): array;
}
