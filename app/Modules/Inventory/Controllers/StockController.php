<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Tenant\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'string'],
            'product_id' => ['nullable', 'string'],
            'low_stock_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $tenantId = $this->tenantContext->tenantId();

        $query = Stock::query()
            ->with(['product', 'warehouse'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (string) $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (string) $request->input('product_id'));
        }

        if ($request->boolean('low_stock_only')) {
            $query->whereHas('product', function ($productQuery) {
                $productQuery->whereColumn('stocks.quantity', '<=', 'products.min_stock_level');
            });
        }

        $stocks = $query->orderBy('updated_at', 'desc')
            ->paginate((int) $request->input('per_page', 50));

        return response()->json($stocks);
    }
}
