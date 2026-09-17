<?php

use Functional\Catalog\Enums\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 64)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('home_url');
            $table->string('backchannel_logout_url')->nullable();
            $table->string('status', 32)->default(ApplicationStatus::Draft->value);
            $table->foreignUuid('oauth_client_id')->constrained('oauth_clients');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
