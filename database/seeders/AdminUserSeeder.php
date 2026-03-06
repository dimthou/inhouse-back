<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['name' => env('ADMIN_TENANT_NAME', 'INHouse Admin Tenant')],
            [
                'subscription_plan' => 'enterprise',
                'is_active' => true,
            ]
        );

        Warehouse::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Main Warehouse',
            ],
            [
                'is_active' => true,
            ]
        );

        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@inhouse.local')],
            [
                'tenant_id' => $tenant->id,
                'name' => env('ADMIN_NAME', 'INHouse Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Admin@123456')),
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Has full access to all features',
            ]
        );

        $user->assignRole($adminRole);
    }
}
