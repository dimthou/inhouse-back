<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function authenticated_user_can_update_inventory_item()
    {
        // Create an initial inventory item
        $inventory = Inventory::factory()->create([
            'name' => 'Original Product',
            'sku' => 'ORIG-SKU',
            'quantity' => 50,
            'price' => 19.99
        ]);

        // Prepare update data with changes to multiple fields
        $updateData = [
            'name' => 'Updated Product Name',
            'sku' => $inventory->sku, // Unique SKU
            'quantity' => 75,
            'price' => 29.99
        ];

        // Perform the update
        $response = $this->actingAs($this->user)
            ->putJson("/api/inventory/{$inventory->id}", $updateData);

        // Debug: Print full response details
        $responseContent = $response->json();
        $responseStatus = $response->status();


        // Assert response structure and status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'sku', 'quantity', 'price', 
                    'created_at', 'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Updated Product Name',
                    'sku' => $inventory->sku,
                    'quantity' => 75,
                    'price' => 29.99
                ]
            ]);

        // Verify database update
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'name' => 'Updated Product Name',
            'sku' => $inventory->sku,
            'quantity' => 75,
            'price' => 29.99
        ]);

        // Verify original record is updated, not a new record created
        $this->assertDatabaseCount('inventories', 1);
    }

    #[Test]
    public function authenticated_user_cannot_update_inventory_with_invalid_data()
    {
        // Create an initial inventory item
        $inventory = Inventory::factory()->create();

        // Prepare invalid update data
        $invalidUpdateData = [
            'name' => '', // Empty name
            'sku' => '', // Empty SKU
            'quantity' => -10, // Negative quantity
            'price' => -5.00 // Negative price
        ];

        // Attempt to update with invalid data
        $response = $this->actingAs($this->user)
            ->putJson("/api/inventory/{$inventory->id}", $invalidUpdateData);

        // Assert validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 
                'sku', 
                'quantity', 
                'price'
            ]);

        // Verify database remains unchanged
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'name' => $inventory->name,
            'sku' => $inventory->sku,
            'quantity' => $inventory->quantity,
            'price' => $inventory->price
        ]);
    }

    #[Test]
    public function authenticated_user_cannot_update_inventory_with_duplicate_sku()
    {
        // Create two inventory items
        $existingInventory = Inventory::factory()->create([
            'sku' => 'EXISTING-SKU'
        ]);
        $inventoryToUpdate = Inventory::factory()->create();

        // Attempt to update with a duplicate SKU
        $updateData = [
            'name' => $inventoryToUpdate->name,
            'sku' => 'EXISTING-SKU', // Duplicate SKU
            'quantity' => $inventoryToUpdate->quantity,
            'price' => $inventoryToUpdate->price
        ];

        // Perform the update
        $response = $this->actingAs($this->user)
            ->putJson("/api/inventory/{$inventoryToUpdate->id}", $updateData);

        // Assert validation error for duplicate SKU
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);

        // Verify database remains unchanged
        $this->assertDatabaseHas('inventories', [
            'id' => $inventoryToUpdate->id,
            'sku' => $inventoryToUpdate->sku
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function authenticated_user_can_partially_update_inventory_item()
    {
        // Create an initial inventory item
        $inventory = Inventory::factory()->create([
            'name' => 'Original Product',
            'sku' => 'ORIG-SKU',
            'quantity' => 50,
            'price' => 19.99
        ]);

        // Prepare partial update data
        $partialUpdateData = [
            'price' => 29.99 // Only update price
        ];

        // Perform the partial update
        $response = $this->actingAs($this->user)
            ->patchJson("/api/inventory/{$inventory->id}", $partialUpdateData);

        // Assert response structure and status
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'sku', 'quantity', 'price', 
                    'created_at', 'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Original Product',
                    'sku' => 'ORIG-SKU',
                    'quantity' => 50,
                    'price' => 29.99
                ]
            ]);

        // Verify database update
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'name' => 'Original Product',
            'sku' => 'ORIG-SKU',
            'quantity' => 50,
            'price' => 29.99
        ]);
    }

    #[Test]
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
                'get_single' => $this->getJson($endpoint),
                'post_adjust' => $this->postJson($endpoint),
                'get_low_stock' => $this->getJson($endpoint),
                'post_bulk_update' => $this->postJson($endpoint),
                default => $this->getJson($endpoint)
            };

            // Ensure response is not null before asserting
            $this->assertNotNull($response, "Response for endpoint {$endpoint} is null");
            
            $response->assertStatus(401);
        }
    }
}
