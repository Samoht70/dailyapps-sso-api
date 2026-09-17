<?php

namespace Technical\Permissions\Rest;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Lomkit\Rest\Http\Requests\RestRequest;

trait ControlsTheQuery
{
    /**
     * The `controlled()` macro only exists once the global scope has been
     * applied, which a REST query has not done yet — so the control is asked
     * directly. An unauthenticated caller never reaches here: every REST route
     * sits behind `auth:api`.
     */
    protected function controlled(RestRequest $request, Builder $query): Builder
    {
        $control = $query->getModel()->newControl();

        return $control === null ? $query : $control->queried($query, $request->user());
    }

    public function searchQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($request, $query);
    }

    public function mutateQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($request, $query);
    }

    public function destroyQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($request, $query);
    }
}
