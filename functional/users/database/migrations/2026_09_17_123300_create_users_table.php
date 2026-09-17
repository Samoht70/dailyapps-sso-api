<?php

use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained();
            $table->string('name', 160);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('status', 32)->default(UserStatus::Invited->value);
            $table->string('organization_role', 32)->default(OrganizationRole::Member->value);
            $table->dateTime('disabled_at')->nullable();
            $table->dateTime('last_authenticated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
