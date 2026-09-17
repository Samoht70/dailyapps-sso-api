<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\ApplicationRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['application_id', 'key', 'label', 'description'])]
#[UseFactory(ApplicationRoleFactory::class)]
class ApplicationRole extends Model
{
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
