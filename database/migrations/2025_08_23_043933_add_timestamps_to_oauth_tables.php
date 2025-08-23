<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Schema::table('oauth_refresh_tokens', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
