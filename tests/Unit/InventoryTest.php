<?php

namespace Tests\Unit;

use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_inventory_item()
    {
        $inventory = Inventory::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'quantity' => 100,
            'price' => 29.99,
        ]);

        $this->assertInstanceOf(Inventory::class, $inventory);
        $this->assertEquals('Test Product', $inventory->name);
        $this->assertEquals('TEST-001', $inventory->sku);
        $this->assertEquals(100, $inventory->quantity);
        $this->assertEquals(29.99, $inventory->price);
        $this->assertTrue($inventory->exists);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $inventory = new Inventory();
        
        $expectedFillable = ['name', 'sku', 'quantity', 'price'];
        $this->assertEquals($expectedFillable, $inventory->getFillable());
    }

    #[Test]
    public function it_can_update_inventory_quantity()
    {
        $inventory = Inventory::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'quantity' => 100,
            'price' => 29.99,
        ]);

        $inventory->update(['quantity' => 75]);

        $this->assertEquals(75, $inventory->fresh()->quantity);
    }

    #[Test]
    public function it_can_update_inventory_price()
    {
        $inventory = Inventory::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'quantity' => 100,
            'price' => 29.99,
        ]);

        $inventory->update(['price' => 34.99]);

        $this->assertEquals(34.99, $inventory->fresh()->price);
    }

    #[Test]
    public function it_can_find_inventory_by_sku()
    {
        Inventory::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'quantity' => 100,
            'price' => 29.99,
        ]);

        $found = Inventory::where('sku', 'TEST-001')->first();

        $this->assertInstanceOf(Inventory::class, $found);
        $this->assertEquals('Test Product', $found->name);
    }
}
