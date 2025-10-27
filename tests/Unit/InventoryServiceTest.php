<?php

namespace Tests\Unit;

use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    #[Test]
    public function it_can_add_inventory_quantity()
    {
        $inventory = Inventory::factory()->create([
            'quantity' => 10
        ]);

        $updatedInventory = $this->inventoryService->adjustInventory($inventory->id, 5, 'add');

        $this->assertEquals(15, $updatedInventory->quantity);
    }

    #[Test]
    public function it_can_subtract_inventory_quantity()
    {
        $inventory = Inventory::factory()->create([
            'quantity' => 10
        ]);

        $updatedInventory = $this->inventoryService->adjustInventory($inventory->id, 5, 'subtract');

        $this->assertEquals(5, $updatedInventory->quantity);
    }

    #[Test]
    public function it_prevents_negative_inventory_quantity()
    {
        $inventory = Inventory::factory()->create([
            'quantity' => 10
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->adjustInventory($inventory->id, 15, 'subtract');
    }

    #[Test]
    public function it_identifies_low_stock_items()
    {
        // Create some inventory items with varying quantities
        Inventory::factory()->create(['quantity' => 5, 'name' => 'Low Stock Item 1']);
        Inventory::factory()->create(['quantity' => 15, 'name' => 'Normal Stock Item']);
        Inventory::factory()->create(['quantity' => 3, 'name' => 'Low Stock Item 2']);

        $lowStockItems = $this->inventoryService->getLowStockItems();

        $this->assertCount(2, $lowStockItems);
        $this->assertTrue($lowStockItems->contains('name', 'Low Stock Item 1'));
        $this->assertTrue($lowStockItems->contains('name', 'Low Stock Item 2'));
    }

    #[Test]
    public function it_generates_low_stock_alert()
    {
        // Mock the Log facade to verify warning is logged
        Log::shouldReceive('warning')
           ->once()
           ->with('Low stock alert', \Mockery::type('array'));

        // Create low stock items
        Inventory::factory()->create(['quantity' => 5, 'name' => 'Alert Item 1']);
        Inventory::factory()->create(['quantity' => 3, 'name' => 'Alert Item 2']);

        $alerts = $this->inventoryService->generateLowStockAlert();

        $this->assertCount(2, $alerts);
        $this->assertArrayHasKey('id', $alerts[0]);
        $this->assertArrayHasKey('name', $alerts[0]);
    }

    #[Test]
    public function it_can_perform_bulk_inventory_update()
    {
        $inventory1 = Inventory::factory()->create(['quantity' => 10]);
        $inventory2 = Inventory::factory()->create(['quantity' => 20]);

        $updates = [
            ['id' => $inventory1->id, 'quantity' => 15],
            ['id' => $inventory2->id, 'quantity' => 25]
        ];

        $results = $this->inventoryService->bulkUpdateInventory($updates);

        // Refresh models to get updated quantities
        $inventory1->refresh();
        $inventory2->refresh();

        $this->assertCount(2, $results);
        $this->assertEquals(15, $inventory1->quantity);
        $this->assertEquals(25, $inventory2->quantity);
        
        // Verify all updates were successful
        foreach ($results as $result) {
            $this->assertEquals('success', $result['status']);
        }
    }

    #[Test]
    public function it_handles_partial_failures_in_bulk_update()
    {
        $inventory1 = Inventory::factory()->create(['quantity' => 10]);

        $updates = [
            ['id' => $inventory1->id, 'quantity' => 15],
            ['id' => 9999, 'quantity' => 25]  // Non-existent inventory ID
        ];

        $results = $this->inventoryService->bulkUpdateInventory($updates);

        $this->assertCount(2, $results);
        
        // First update should succeed
        $this->assertEquals('success', $results[0]['status']);
        
        // Second update should fail due to non-existent ID
        $this->assertEquals('failed', $results[1]['status']);
    }
}
