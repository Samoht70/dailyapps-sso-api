<?php

namespace Technical\Audit\Rest\Controllers;

use Lomkit\Rest\Http\Controllers\Controller;
use Technical\Audit\Rest\Resources\SecurityEventResource;

class SecurityEventsController extends Controller
{
    public static $resource = SecurityEventResource::class;
}
