<?php

namespace Functional\Catalog\Rest\Controllers;

use Functional\Catalog\Rest\Resources\ApplicationResource;
use Lomkit\Rest\Http\Controllers\Controller;

class ApplicationsController extends Controller
{
    public static $resource = ApplicationResource::class;
}
