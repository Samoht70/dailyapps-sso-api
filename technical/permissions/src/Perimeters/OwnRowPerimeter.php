<?php

namespace Technical\Permissions\Perimeters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lomkit\Access\Perimeters\Perimeter;

class OwnRowPerimeter extends Perimeter
{
    /**
     * Anyone reads the row that is about them, whatever their role.
     *
     * @param  list<string>  $methods
     */
    public static function reading(array $methods = ['view'], string $key = 'id'): static
    {
        return static::new()
            ->allowed(fn (Model $user, string $method): bool => in_array($method, $methods, true))
            ->should(fn (Model $user, Model $model): bool => $model->{$key} === $user->getKey())
            ->query(fn (Builder $query, Model $user): Builder => $query->where($key, $user->getKey()));
    }
}
