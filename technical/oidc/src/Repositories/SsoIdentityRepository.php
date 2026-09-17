<?php

namespace Technical\Oidc\Repositories;

use Functional\Users\Models\User;
use OpenIDConnect\Interfaces\IdentityEntityInterface;
use OpenIDConnect\Interfaces\IdentityRepositoryInterface;
use Technical\Oidc\Entities\SsoIdentityEntity;

class SsoIdentityRepository implements IdentityRepositoryInterface
{
    public function getByIdentifier(string $identifier): IdentityEntityInterface
    {
        $entity = new SsoIdentityEntity;
        $entity->setIdentifier($identifier);
        $entity->setAccount(User::query()->find($identifier));

        return $entity;
    }
}
