<?php

use Functional\Organizations\Enums\OrganizationKind;
use Functional\Organizations\Enums\OrganizationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 160)->unique();
            $table->string('kind', 32);
            $table->string('status', 32)->default(OrganizationStatus::Active->value);
            $table->dateTime('suspended_at')->nullable();
            $table->timestamps();

            $table->string('operator_singleton', 32)
                ->nullable()
                ->virtualAs(sprintf(
                    "case when kind = '%s' then '%s' end",
                    OrganizationKind::Operator->value,
                    OrganizationKind::Operator->value,
                ));

            $table->unique('operator_singleton');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
