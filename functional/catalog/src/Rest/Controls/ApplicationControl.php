<?php

namespace Functional\Catalog\Rest\Controls;

use Functional\Catalog\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;
use Technical\Permissions\Enums\Permission;
use Technical\Permissions\Perimeters\OperatorPerimeter;

class ApplicationControl extends Control
{
    protected string $model = Application::class;

    /** @return array<Perimeter> */
    protected function perimeters(): array
    {
        return [
            OperatorPerimeter::requiring([
                'view' => Permission::DeclareApplication,
                'create' => Permission::DeclareApplication,
                'update' => Permission::DeclareApplication,
                'delete' => Permission::DeclareApplication,
            ]),
            Perimeter::new()
                ->allowed(fn (Model $user, string $method): bool => $method === 'view')
                ->should(fn (Model $user, Model $model): bool => $model->isPublished())
                ->query(fn (Builder $query): Builder => $query->published()),
        ];
    }
}
