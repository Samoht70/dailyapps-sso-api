<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The scaffolded column stayed a bigint while every model key became a
     * UUID: writing a signed-in session truncated the identifier, the write was
     * refused, and no authenticated session was ever stored.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->foreignUuid('user_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->index()->after('id');
        });
    }
};
