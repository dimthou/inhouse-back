<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Requests\StockAdjustRequest;
use App\Modules\Inventory\Requests\StockInRequest;
use App\Modules\Inventory\Requests\StockOutRequest;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function stockIn(StockInRequest $request): JsonResponse
    {
        $movement = $this->inventoryService->stockIn($request->user(), $request->validated());

        return response()->json([
            'message' => 'Stock in processed successfully.',
            'data' => $movement,
        ], 201);
    }

    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $movement = $this->inventoryService->stockOut($request->user(), $request->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Stock out processed successfully.',
            'data' => $movement,
        ]);
    }

    public function adjust(StockAdjustRequest $request): JsonResponse
    {
        $movement = $this->inventoryService->adjust($request->user(), $request->validated());

        return response()->json([
            'message' => 'Stock adjusted successfully.',
            'data' => $movement,
        ]);
    }
}
