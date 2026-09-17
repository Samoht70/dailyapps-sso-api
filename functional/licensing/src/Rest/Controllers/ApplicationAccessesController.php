<?php

namespace Functional\Licensing\Rest\Controllers;

use Functional\Licensing\Rest\Resources\ApplicationAccessResource;
use Lomkit\Rest\Http\Controllers\Controller;

class ApplicationAccessesController extends Controller
{
    public static $resource = ApplicationAccessResource::class;
}
