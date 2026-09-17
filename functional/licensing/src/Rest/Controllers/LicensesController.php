<?php

namespace Functional\Licensing\Rest\Controllers;

use Functional\Licensing\Rest\Resources\LicenseResource;
use Lomkit\Rest\Http\Controllers\Controller;

class LicensesController extends Controller
{
    public static $resource = LicenseResource::class;
}
