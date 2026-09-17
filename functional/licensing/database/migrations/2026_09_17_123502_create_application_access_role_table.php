<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_access_role', function (Blueprint $table) {
            $table->foreignUuid('application_access_id')->constrained();
            $table->foreignUuid('application_role_id')->constrained();

            $table->primary(['application_access_id', 'application_role_id'], 'application_access_role_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_access_role');
    }
};
