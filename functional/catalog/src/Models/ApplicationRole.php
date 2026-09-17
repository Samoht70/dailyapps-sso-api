<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\ApplicationRoleFactory;
use Functional\Catalog\Rest\Policies\ApplicationRolePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lomkit\Access\Controls\HasControl;

#[Fillable(['application_id', 'key', 'label', 'description'])]
#[UsePolicy(ApplicationRolePolicy::class)]
#[UseFactory(ApplicationRoleFactory::class)]
class ApplicationRole extends Model
{
    use HasControl;
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
