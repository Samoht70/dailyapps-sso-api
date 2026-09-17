<?php

namespace Technical\Oidc;

use OpenIDConnect\ClaimExtractor as OpenIdConnectClaimExtractor;

class ClaimExtractor extends OpenIdConnectClaimExtractor
{
    /**
     * The package seals `profile`, `email`, `address` and `phone` so nobody
     * redefines them. This product has to widen `profile` with `organization`,
     * which the contract places there, so the seal is lifted.
     *
     * @return string[]
     */
    public function getProtectedClaims(): array
    {
        return [];
    }
}
