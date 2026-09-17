<?php

namespace Functional\Catalog\Rest\Controllers;

use Functional\Catalog\Rest\Resources\ApplicationRoleResource;
use Lomkit\Rest\Http\Controllers\Controller;

class ApplicationRolesController extends Controller
{
    public static $resource = ApplicationRoleResource::class;
}
