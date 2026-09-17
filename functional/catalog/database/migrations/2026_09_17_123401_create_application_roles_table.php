<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained();
            $table->string('key', 64);
            $table->string('label', 160);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_roles');
    }
};
