<?php

namespace Functional\Users\Rest\Controllers;

use Functional\Users\Rest\Resources\UserResource;
use Lomkit\Rest\Http\Controllers\Controller;

class UsersController extends Controller
{
    public static $resource = UserResource::class;
}
