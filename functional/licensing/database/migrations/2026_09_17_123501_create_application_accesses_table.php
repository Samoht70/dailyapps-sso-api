<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('application_id')->constrained();
            $table->foreignUuid('granted_by_id')->nullable()->constrained('users');
            $table->dateTime('granted_at');
            $table->timestamps();

            $table->unique(['user_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_accesses');
    }
};
