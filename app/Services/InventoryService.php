<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Constant for low stock threshold
     */
    private const LOW_STOCK_THRESHOLD = 10;

    /**
     * Adjust inventory quantity
     *
     * @param int $inventoryId
     * @param int $quantity
     * @param string $type 'add' or 'subtract'
     * @return Inventory
     * @throws \Exception
     */
    public function adjustInventory(int $inventoryId, int $quantity, string $type = 'add'): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $quantity, $type) {
            $inventory = Inventory::lockForUpdate()->findOrFail($inventoryId);

            if ($type === 'add') {
                $inventory->quantity += $quantity;
            } elseif ($type === 'subtract') {
                if ($inventory->quantity < $quantity) {
                    throw new \Exception("Insufficient stock: Cannot subtract more than available quantity.");
                }
                $inventory->quantity -= $quantity;
            } else {
                throw new \Exception("Invalid adjustment type. Use 'add' or 'subtract'.");
            }

            $inventory->save();

            // Log inventory changes
            Log::info("Inventory adjusted", [
                'inventory_id' => $inventoryId,
                'quantity' => $quantity,
                'type' => $type,
                'new_total' => $inventory->quantity
            ]);

            return $inventory;
        });
    }

    /**
     * Check for low stock items
     *
     * @param int $threshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockItems(int $threshold = self::LOW_STOCK_THRESHOLD)
    {
        return Inventory::where('quantity', '<=', $threshold)->get();
    }

    /**
     * Generate low stock alert
     *
     * @return array
     */
    public function generateLowStockAlert(): array
    {
        $lowStockItems = $this->getLowStockItems();

        if ($lowStockItems->isNotEmpty()) {
            Log::warning('Low stock alert', [
                'low_stock_items' => $lowStockItems->pluck('name', 'id')->toArray()
            ]);
        }

        return $lowStockItems->toArray();
    }

    /**
     * Bulk update inventory quantities
     *
     * @param array $updates
     * @return array
     */
    public function bulkUpdateInventory(array $updates): array
    {
        $results = [];

        DB::transaction(function () use ($updates, &$results) {
            foreach ($updates as $update) {
                try {
                    $inventory = Inventory::findOrFail($update['id']);
                    $inventory->quantity = $update['quantity'];
                    $inventory->save();
                    $results[] = [
                        'id' => $inventory->id,
                        'status' => 'success'
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'id' => $update['id'],
                        'status' => 'failed',
                        'message' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }
}
