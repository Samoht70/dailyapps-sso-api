<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_session_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sso_session_id')->constrained();
            $table->foreignUuid('application_id')->constrained();
            $table->dateTime('first_seen_at');
            $table->dateTime('logout_pushed_at')->nullable();

            $table->unique(['sso_session_id', 'application_id'], 'sso_session_participants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_session_participants');
    }
};
