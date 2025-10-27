<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function authenticated_user_can_list_inventory_items()
    {
        // Create some inventory items
        $inventoryItems = Inventory::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'sku', 'quantity', 'price', 
                        'created_at', 'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function authenticated_user_can_create_inventory_item()
    {
        $inventoryData = [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'quantity' => 50,
            'price' => 99.99
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/inventory', $inventoryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'sku', 'quantity', 'price', 
                    'created_at', 'updated_at'
                ]
            ])
            ->assertJson([
                'data' => $inventoryData
            ]);

        $this->assertDatabaseHas('inventories', $inventoryData);
    }

    /** @test */
    public function authenticated_user_can_update_inventory_item()
    {
        $inventory = Inventory::factory()->create();

        $updateData = [
            'name' => 'Updated Product',
            'sku' => $inventory->sku,
            'quantity' => 75,
            'price' => 129.99
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/inventory/{$inventory->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'sku', 'quantity', 'price', 
                    'created_at', 'updated_at'
                ]
            ])
            ->assertJson([
                'data' => $updateData
            ]);

        $this->assertDatabaseHas('inventories', $updateData);
    }

    /** @test */
    public function authenticated_user_can_adjust_inventory_quantity()
    {
        $inventory = Inventory::factory()->create(['quantity' => 50]);

        $adjustmentData = [
            'quantity' => 10,
            'type' => 'add'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/inventory/{$inventory->id}/adjust", $adjustmentData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'sku', 'quantity', 'price', 
                    'created_at', 'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'quantity' => 60
                ]
            ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 60
        ]);
    }

    /** @test */
    public function authenticated_user_can_get_low_stock_items()
    {
        // Create some inventory items with low stock
        Inventory::factory()->create(['quantity' => 5, 'name' => 'Low Stock Item 1']);
        Inventory::factory()->create(['quantity' => 3, 'name' => 'Low Stock Item 2']);
        Inventory::factory()->create(['quantity' => 15, 'name' => 'Normal Stock Item']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory/low-stock');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'sku', 'quantity', 'price', 
                        'created_at', 'updated_at'
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function authenticated_user_can_perform_bulk_inventory_update()
    {
        $inventory1 = Inventory::factory()->create(['quantity' => 50]);
        $inventory2 = Inventory::factory()->create(['quantity' => 75]);

        $bulkUpdateData = [
            'updates' => [
                ['id' => $inventory1->id, 'quantity' => 60],
                ['id' => $inventory2->id, 'quantity' => 85]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/inventory/bulk-update', $bulkUpdateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'results' => [
                    '*' => ['id', 'status']
                ]
            ])
            ->assertJson([
                'message' => 'Bulk inventory update processed',
                'results' => [
                    ['id' => $inventory1->id, 'status' => 'success'],
                    ['id' => $inventory2->id, 'status' => 'success']
                ]
            ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory1->id,
            'quantity' => 60
        ]);
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory2->id,
            'quantity' => 85
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_inventory_endpoints()
    {
        $inventory = Inventory::factory()->create();

        // Test various endpoints
        $endpoints = [
            'get' => '/api/inventory',
            'post' => '/api/inventory',
            'get_single' => "/api/inventory/{$inventory->id}",
            'put' => "/api/inventory/{$inventory->id}",
            'post_adjust' => "/api/inventory/{$inventory->id}/adjust",
            'get_low_stock' => '/api/inventory/low-stock',
            'post_bulk_update' => '/api/inventory/bulk-update'
        ];

        foreach ($endpoints as $method => $endpoint) {
            $response = match($method) {
                'get' => $this->getJson($endpoint),
                'post' => $this->postJson($endpoint),
                'put' => $this->putJson($endpoint),
                default => null
            };

            $response->assertStatus(401);
        }
    }
}
