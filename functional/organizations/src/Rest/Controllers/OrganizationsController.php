<?php

namespace Functional\Organizations\Rest\Controllers;

use Functional\Organizations\Rest\Resources\OrganizationResource;
use Lomkit\Rest\Http\Controllers\Controller;

class OrganizationsController extends Controller
{
    public static $resource = OrganizationResource::class;
}
