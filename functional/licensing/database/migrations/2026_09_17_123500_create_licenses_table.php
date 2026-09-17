<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->foreignUuid('application_id')->constrained();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('seats');
            $table->timestamps();

            $table->unique(['organization_id', 'application_id']);
        });

        DB::statement('alter table licenses add constraint licenses_seats_at_least_one check (seats >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
