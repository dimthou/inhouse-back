<?php

namespace App\Repositories;

use App\Models\Inventory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryRepository
{
    /**
     * Cache key prefix for inventory items
     */
    private const CACHE_PREFIX = 'inventory:';

    /**
     * Get paginated inventory items with caching
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginatedInventory(
        int $perPage = 15, 
        array $filters = []
    ): LengthAwarePaginator {
        $cacheKey = $this->generateCacheKey('paginated', $filters, $perPage);

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($perPage, $filters) {
            $query = Inventory::query();

            // Apply filters
            if (!empty($filters['name'])) {
                $query->where('name', 'LIKE', "%{$filters['name']}%");
            }

            if (isset($filters['min_quantity'])) {
                $query->where('quantity', '>=', $filters['min_quantity']);
            }

            if (isset($filters['max_quantity'])) {
                $query->where('quantity', '<=', $filters['max_quantity']);
            }

            if (isset($filters['min_price'])) {
                $query->where('price', '>=', $filters['min_price']);
            }

            if (isset($filters['max_price'])) {
                $query->where('price', '<=', $filters['max_price']);
            }

            // Apply sorting
            $query->orderBy(
                $filters['sort_by'] ?? 'created_at', 
                $filters['sort_direction'] ?? 'desc'
            );

            return $query->paginate($perPage);
        });
    }

    /**
     * Get low stock inventory items
     *
     * @param int $threshold
     * @return Collection
     */
    public function getLowStockItems(int $threshold = 10): Collection
    {
        $cacheKey = $this->generateCacheKey('low_stock', ['threshold' => $threshold]);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($threshold) {
            return Inventory::where('quantity', '<=', $threshold)
                ->orderBy('quantity', 'asc')
                ->get();
        });
    }

    /**
     * Find inventory item by SKU with caching
     *
     * @param string $sku
     * @return Inventory|null
     */
    public function findBySku(string $sku)
    {
        $cacheKey = $this->generateCacheKey('sku', ['sku' => $sku]);

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($sku) {
            return Inventory::where('sku', $sku)->first();
        });
    }

    /**
     * Bulk update inventory items
     *
     * @param array $updates
     * @return array
     */
    public function bulkUpdate(array $updates): array
    {
        $results = [];

        foreach ($updates as $update) {
            try {
                $inventory = Inventory::findOrFail($update['id']);
                $inventory->update($update);
                $results[] = [
                    'id' => $inventory->id,
                    'status' => 'success'
                ];

                // Invalidate cache for this specific item
                Cache::forget($this->generateCacheKey('sku', ['sku' => $inventory->sku]));
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $update['id'],
                    'status' => 'failed',
                    'message' => $e->getMessage()
                ];
            }
        }

        // Invalidate paginated and list caches
        Cache::forget($this->generateCacheKey('paginated'));
        Cache::forget($this->generateCacheKey('low_stock'));

        return $results;
    }

    /**
     * Generate a cache key based on parameters
     *
     * @param string $type
     * @param array $params
     * @param int|null $perPage
     * @return string
     */
    private function generateCacheKey(
        string $type, 
        array $params = [], 
        ?int $perPage = null
    ): string {
        $key = self::CACHE_PREFIX . $type;
        
        // Sort params to ensure consistent key generation
        ksort($params);
        
        foreach ($params as $k => $v) {
            $key .= ":{$k}=" . (is_array($v) ? md5(json_encode($v)) : $v);
        }
        
        if ($perPage) {
            $key .= ":page={$perPage}";
        }

        return $key;
    }
}
