<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    #[Test]
    public function admin_can_access_all_inventory_endpoints()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/inventory');
        $response->assertStatus(200);

        $response = $this->postJson('/api/v1/inventory', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 100,
            'price' => 50.00
        ]);
        $response->assertStatus(201);
    }

    #[Test]
    public function viewer_cannot_create_inventory()
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/v1/inventory', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 100,
            'price' => 50.00
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'You do not have permission to create inventory items.'
        ]);
    }

    #[Test]
    public function viewer_can_view_inventory()
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/inventory');
        $response->assertStatus(200);
    }

    #[Test]
    public function manager_can_edit_and_can_delete_inventory()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Sanctum::actingAs($manager);

        // Create inventory as admin first
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        Sanctum::actingAs($adminUser);
        $createResponse = $this->postJson('/api/v1/inventory', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 100,
            'price' => 50.00
        ]);

        $inventoryId = $createResponse->json('data.id');

        // Switch back to manager
        Sanctum::actingAs($manager);

        // Manager can edit
        $updateResponse = $this->putJson("/api/v1/inventory/{$inventoryId}", [
            'name' => 'Updated Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 150,
            'price' => 60.00
        ]);
        $updateResponse->assertStatus(200);

        // Manager can delete
        $deleteResponse = $this->deleteJson("/api/v1/inventory/{$inventoryId}");
        $deleteResponse->assertStatus(200);
    }

    #[Test]
    public function staff_can_edit_but_not_delete_inventory()
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        // Create inventory as admin first
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        Sanctum::actingAs($adminUser);
        $createResponse = $this->postJson('/api/v1/inventory', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 100,
            'price' => 50.00
        ]);

        $inventoryId = $createResponse->json('data.id');

        // Switch back to staff
        Sanctum::actingAs($staff);

        // Manager can edit
        $updateResponse = $this->putJson("/api/v1/inventory/{$inventoryId}", [
            'name' => 'Updated Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 150,
            'price' => 60.00
        ]);
        $updateResponse->assertStatus(200);

        // Staff cannot delete
        $deleteResponse = $this->deleteJson("/api/v1/inventory/{$inventoryId}");
        $deleteResponse->assertStatus(403);
    }

    #[Test]
    public function user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');
        $user->assignRole('staff');

        $this->assertTrue($user->hasRole('manager'));
        $this->assertTrue($user->hasRole('staff'));
        $this->assertTrue($user->hasAnyRole(['manager', 'admin']));
    }

    #[Test]
    public function role_can_have_permissions_assigned()
    {
        $role = Role::where('slug', 'staff')->first();

        $this->assertTrue($role->hasPermission('inventory.view'));
        $this->assertTrue($role->hasPermission('inventory.create'));
        $this->assertFalse($role->hasPermission('inventory.delete'));
    }

    #[Test]
    public function admin_can_create_new_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'Custom Role',
            'slug' => 'custom-role',
            'description' => 'A custom role for testing',
            'permissions' => ['inventory.view', 'product.view']
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'permissions'
            ]
        ]);

        $this->assertDatabaseHas('roles', [
            'slug' => 'custom-role'
        ]);
    }

    #[Test]
    public function admin_can_assign_role_to_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['manager', 'staff']
        ]);

        $response->assertStatus(200);

        $this->assertTrue($user->fresh()->hasRole('manager'));
        $this->assertTrue($user->fresh()->hasRole('staff'));
    }

    #[Test]
    public function admin_can_delete_critical_system_roles()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $adminRole = Role::where('slug', 'admin')->first();

        $response = $this->deleteJson("/api/v1/roles/{$adminRole->id}");

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Cannot delete critical system roles'
        ]);
    }

    #[Test]
    public function user_permissions_are_aggregated_from_all_roles()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');
        $user->assignRole('viewer');

        // Staff has create permission, viewer doesn't
        $this->assertTrue($user->hasPermission('inventory.create'));
        // Both have view permission
        $this->assertTrue($user->hasPermission('inventory.view'));
    }
}