<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');
        });

        if (Schema::hasTable('tenants')) {
            $defaultTenantId = (string) Str::ulid();

            DB::table('tenants')->insert([
                'id' => $defaultTenantId,
                'name' => 'Default Tenant',
                'subscription_plan' => 'basic',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->whereNull('tenant_id')->update([
                'tenant_id' => $defaultTenantId,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
