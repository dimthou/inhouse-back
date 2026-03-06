<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Inventory Permissions
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'description' => 'Can view inventory items'],
            ['name' => 'Create Inventory', 'slug' => 'inventory.create', 'description' => 'Can create inventory items'],
            ['name' => 'Edit Inventory', 'slug' => 'inventory.edit', 'description' => 'Can edit inventory items'],
            ['name' => 'Delete Inventory', 'slug' => 'inventory.delete', 'description' => 'Can delete inventory items'],
            ['name' => 'Adjust Inventory', 'slug' => 'inventory.adjust', 'description' => 'Can adjust inventory quantities'],
            ['name' => 'Stock In', 'slug' => 'stock.in', 'description' => 'Can process stock in operations'],
            ['name' => 'Stock Out', 'slug' => 'stock.out', 'description' => 'Can process stock out operations'],
            ['name' => 'Stock Adjust', 'slug' => 'stock.adjust', 'description' => 'Can adjust stock quantities'],

            // Product Permissions
            ['name' => 'View Products', 'slug' => 'product.view', 'description' => 'Can view products'],
            ['name' => 'Create Products', 'slug' => 'product.create', 'description' => 'Can create products'],
            ['name' => 'Edit Products', 'slug' => 'product.edit', 'description' => 'Can edit products'],
            ['name' => 'Delete Products', 'slug' => 'product.delete', 'description' => 'Can delete products'],

            // Purchase Permissions
            ['name' => 'View Purchases', 'slug' => 'purchase.view', 'description' => 'Can view purchase orders'],
            ['name' => 'Create Purchases', 'slug' => 'purchase.create', 'description' => 'Can create purchase orders'],
            ['name' => 'Edit Purchases', 'slug' => 'purchase.edit', 'description' => 'Can edit purchase orders'],
            ['name' => 'Delete Purchases', 'slug' => 'purchase.delete', 'description' => 'Can delete purchase orders'],
            ['name' => 'Approve Purchases', 'slug' => 'purchase.approve', 'description' => 'Can approve purchase orders'],

            // Sales Permissions
            ['name' => 'View Sales', 'slug' => 'sales.view', 'description' => 'Can view sales orders'],
            ['name' => 'Create Sales', 'slug' => 'sales.create', 'description' => 'Can create sales orders'],
            ['name' => 'Edit Sales', 'slug' => 'sales.edit', 'description' => 'Can edit sales orders'],
            ['name' => 'Delete Sales', 'slug' => 'sales.delete', 'description' => 'Can delete sales orders'],
            ['name' => 'Process Sales', 'slug' => 'sales.process', 'description' => 'Can process sales orders'],

            // Supplier Permissions
            ['name' => 'View Suppliers', 'slug' => 'supplier.view', 'description' => 'Can view suppliers'],
            ['name' => 'Create Suppliers', 'slug' => 'supplier.create', 'description' => 'Can create suppliers'],
            ['name' => 'Edit Suppliers', 'slug' => 'supplier.edit', 'description' => 'Can edit suppliers'],
            ['name' => 'Delete Suppliers', 'slug' => 'supplier.delete', 'description' => 'Can delete suppliers'],

            // Customer Permissions
            ['name' => 'View Customers', 'slug' => 'customer.view', 'description' => 'Can view customers'],
            ['name' => 'Create Customers', 'slug' => 'customer.create', 'description' => 'Can create customers'],
            ['name' => 'Edit Customers', 'slug' => 'customer.edit', 'description' => 'Can edit customers'],
            ['name' => 'Delete Customers', 'slug' => 'customer.delete', 'description' => 'Can delete customers'],

            // Report Permissions
            ['name' => 'View Reports', 'slug' => 'report.view', 'description' => 'Can view reports'],
            ['name' => 'Export Reports', 'slug' => 'report.export', 'description' => 'Can export reports'],

            // User Management Permissions
            ['name' => 'View Users', 'slug' => 'user.view', 'description' => 'Can view users'],
            ['name' => 'Create Users', 'slug' => 'user.create', 'description' => 'Can create users'],
            ['name' => 'Edit Users', 'slug' => 'user.edit', 'description' => 'Can edit users'],
            ['name' => 'Delete Users', 'slug' => 'user.delete', 'description' => 'Can delete users'],
            ['name' => 'Manage Roles', 'slug' => 'role.manage', 'description' => 'Can manage user roles'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Has full access to all features'
            ]
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Can manage inventory, purchases, and sales'
            ]
        );

        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'description' => 'Can view and create basic records'
            ]
        );

        $viewerRole = Role::firstOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Viewer',
                'description' => 'Can only view data'
            ]
        );

        // Assign all permissions to admin
        $adminRole->permissions()->sync(Permission::all());

        // Assign permissions to manager
        $managerPermissions = Permission::whereIn('slug', [
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.adjust',
            'stock.in',
            'stock.out',
            'stock.adjust',
            'product.view',
            'product.create',
            'product.edit',
            'purchase.view',
            'purchase.create',
            'purchase.edit',
            'purchase.approve',
            'sales.view',
            'sales.create',
            'sales.edit',
            'sales.process',
            'supplier.view',
            'supplier.create',
            'supplier.edit',
            'customer.view',
            'customer.create',
            'customer.edit',
            'report.view',
            'report.export',
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPermissions);

        // Assign permissions to staff
        $staffPermissions = Permission::whereIn('slug', [
            'inventory.view',
            'inventory.create',
            'inventory.adjust',
            'stock.in',
            'stock.out',
            'stock.adjust',
            'product.view',
            'purchase.view',
            'purchase.create',
            'sales.view',
            'sales.create',
            'sales.process',
            'supplier.view',
            'customer.view',
            'customer.create',
            'report.view',
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);

        // Assign permissions to viewer
        $viewerPermissions = Permission::whereIn('slug', [
            'inventory.view',
            'product.view',
            'purchase.view',
            'sales.view',
            'supplier.view',
            'customer.view',
            'report.view',
        ])->pluck('id');
        $viewerRole->permissions()->sync($viewerPermissions);
    }
}
