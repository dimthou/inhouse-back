<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Tenant\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function stockIn(User $user, array $data): StockMovement
    {
        return $this->changeStock($user, $data, 'IN');
    }

    public function stockOut(User $user, array $data): StockMovement
    {
        return $this->changeStock($user, $data, 'OUT');
    }

    public function adjust(User $user, array $data): StockMovement
    {
        return DB::transaction(function () use ($user, $data) {
            $tenantId = $this->tenantContext->tenantId();

            $stock = Stock::query()
                ->where('tenant_id', $tenantId)
                ->where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = Stock::create([
                    'tenant_id' => $tenantId,
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity' => 0,
                ]);
            }

            $before = $stock->quantity;
            $after = (int) $data['new_quantity'];
            $movementQty = abs($after - $before);

            $stock->update(['quantity' => $after]);

            return StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'type' => 'ADJUSTMENT',
                'quantity' => $movementQty,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }

    private function changeStock(User $user, array $data, string $type): StockMovement
    {
        return DB::transaction(function () use ($user, $data, $type) {
            $tenantId = $this->tenantContext->tenantId();

            $stock = Stock::query()
                ->where('tenant_id', $tenantId)
                ->where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                if ($type === 'OUT') {
                    throw new \InvalidArgumentException('Insufficient stock. Stock record not found.');
                }

                $stock = Stock::create([
                    'tenant_id' => $tenantId,
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity' => 0,
                ]);
            }

            $before = $stock->quantity;
            $quantity = (int) $data['quantity'];

            if ($type === 'OUT' && $before < $quantity) {
                throw new \InvalidArgumentException('Insufficient stock for stock out operation.');
            }

            $after = $type === 'IN' ? $before + $quantity : $before - $quantity;

            $stock->update(['quantity' => $after]);

            return StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'type' => $type,
                'quantity' => $quantity,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }
}
