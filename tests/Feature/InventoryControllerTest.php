<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_can_list_all_inventory_items()
    {
        $inventory1 = Inventory::factory()->create([
            'name' => 'Product 1',
            'sku' => 'SKU-001',
        ]);
        $inventory2 = Inventory::factory()->create([
            'name' => 'Product 2',
            'sku' => 'SKU-002',
        ]);

        $response = $this->getJson('/api/inventory');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'sku',
                            'quantity',
                            'price',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);
    }

    #[Test]
    public function it_can_create_new_inventory_item()
    {
        $inventoryData = [
            'name' => 'New Product',
            'sku' => 'SKU-NEW',
            'quantity' => 50,
            'price' => 19.99,
        ];

        $response = $this->postJson('/api/inventory', $inventoryData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'sku',
                        'quantity',
                        'price',
                        'created_at',
                        'updated_at',
                    ]
                ]);

        $this->assertDatabaseHas('inventories', $inventoryData);
    }

    #[Test]
    public function it_cannot_create_inventory_with_invalid_data()
    {
        $response = $this->postJson('/api/inventory', [
            'name' => '',
            'sku' => '',
            'quantity' => -5,
            'price' => -10.00,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'sku', 'quantity', 'price']);
    }

    #[Test]
    public function it_can_show_specific_inventory_item()
    {
        $inventory = Inventory::factory()->create([
            'name' => 'Test Product',
            'sku' => 'SKU-TEST',
            'quantity' => 100,
            'price' => 29.99,
        ]);

        $response = $this->getJson("/api/inventory/{$inventory->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'sku',
                        'quantity',
                        'price',
                        'created_at',
                        'updated_at',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $inventory->id,
                        'name' => 'Test Product',
                        'sku' => 'SKU-TEST',
                        'quantity' => 100,
                        'price' => 29.99,
                    ]
                ]);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_inventory()
    {
        $response = $this->getJson('/api/inventory/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_can_update_inventory_item()
    {
        $inventory = Inventory::factory()->create([
            'name' => 'Old Name',
            'sku' => 'OLD-SKU',
            'quantity' => 50,
            'price' => 15.00,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'sku' => 'NEW-SKU',
            'quantity' => 75,
            'price' => 25.00,
        ];

        $response = $this->putJson("/api/inventory/{$inventory->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'sku',
                        'quantity',
                        'price',
                        'created_at',
                        'updated_at',
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $inventory->id,
                        'name' => 'Updated Name',
                        'sku' => 'NEW-SKU',
                        'quantity' => 75,
                        'price' => 25.00,
                    ]
                ]);

        $this->assertDatabaseHas('inventories', array_merge(['id' => $inventory->id], $updateData));
    }

    #[Test]
    public function it_can_update_inventory_item_with_price()
    {
        // Arrange: Create an initial inventory item
        $inventory = Inventory::factory()->create([
            'name' => 'Original Product',
            'sku' => 'ORIG-SKU',
            'quantity' => 50,
            'price' => 15.00,
        ]);

        // Prepare update data
        $updateData = [
            'price' => 25.00
        ];

        // Act: Perform the patch request
        $response = $this->patchJson("/api/inventory/{$inventory->id}", $updateData);

        // Assert: Response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'quantity',
                    'price',
                    'created_at',
                    'updated_at',
                ]
            ]);

        // Refresh the model to get updated data
        $updatedInventory = Inventory::findOrFail($inventory->id);

        // Assert: Database and model state
        $this->assertEquals($updateData['price'], $updatedInventory->price);
        $this->assertEquals($inventory->name, $updatedInventory->name);
        $this->assertEquals($inventory->sku, $updatedInventory->sku);
        $this->assertEquals($inventory->quantity, $updatedInventory->quantity);

        // Additional database assertion
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'price' => $updateData['price'],
        ]);
    }

    #[Test]
    public function it_cannot_update_inventory_with_invalid_data()
    {
        $inventory = Inventory::factory()->create();

        $response = $this->putJson("/api/inventory/{$inventory->id}", [
            'name' => '',
            'sku' => '',
            'quantity' => -10,
            'price' => -5.00,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'sku', 'quantity', 'price']);
    }

    #[Test]
    public function it_can_delete_inventory_item()
    {
        $inventory = Inventory::factory()->create();

        $response = $this->deleteJson("/api/inventory/{$inventory->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('inventories', ['id' => $inventory->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_nonexistent_inventory()
    {
        $response = $this->deleteJson('/api/inventory/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_validates_sku_uniqueness()
    {
        // Create first inventory item
        Inventory::factory()->create(['sku' => 'DUPLICATE-SKU']);

        // Try to create second item with same SKU
        $response = $this->postJson('/api/inventory', [
            'name' => 'Second Product',
            'sku' => 'DUPLICATE-SKU',
            'quantity' => 10,
            'price' => 10.00,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['sku']);
    }

    #[Test]
    public function it_validates_quantity_is_positive()
    {
        $response = $this->postJson('/api/inventory', [
            'name' => 'Test Product',
            'sku' => 'SKU-TEST',
            'quantity' => -5,
            'price' => 10.00,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['quantity']);
    }

    #[Test]
    public function it_validates_price_is_positive()
    {
        $response = $this->postJson('/api/inventory', [
            'name' => 'Test Product',
            'sku' => 'SKU-TEST',
            'quantity' => 10,
            'price' => -5.00,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['price']);
    }
}
