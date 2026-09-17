<?php

namespace Functional\Organizations\Models;

use Functional\Organizations\Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(OrganizationFactory::class)]
class Organization extends Model
{
    use HasFactory;
    use HasUuids;
}
