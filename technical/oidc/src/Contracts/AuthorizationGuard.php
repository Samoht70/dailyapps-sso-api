<?php

namespace Technical\Oidc\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Technical\Oidc\Enums\RefusalReason;
use Technical\Oidc\Models\Client;

interface AuthorizationGuard
{
    /**
     * The reason this user may not enter that client, or null to let them in.
     */
    public function refuse(Authenticatable $user, Client $client): ?RefusalReason;
}
