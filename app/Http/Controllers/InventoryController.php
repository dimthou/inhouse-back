<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return InventoryResource::collection(Inventory::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InventoryRequest $request): InventoryResource
    {
        $inventory = Inventory::create($request->validated());
        return new InventoryResource($inventory);
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory): InventoryResource
    {
        return new InventoryResource($inventory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryRequest $request, Inventory $inventory): InventoryResource
    {
        // Get validated data
        $validatedData = $request->validated();

        // Update the inventory item
        $updated = $inventory->update($validatedData);

        // Log update result
        Log::info('Inventory Update Result', [
            'update_successful' => $updated,
            'inventory_after_update' => $inventory->toArray()
        ]);

        // Refresh the model to get the latest data
        $inventory->refresh();

        // Ensure we're passing a valid model to the resource
        return new InventoryResource($inventory);
    }

    /**
     * Partially update the specified resource in storage.
     */
    public function patch(InventoryRequest $request, Inventory $inventory): InventoryResource
    {
        // Validate only the provided fields
        $validatedData = $request->validated();

        // Fill only the provided fields
        $inventory->fill($validatedData);
        $inventory->save();

        // Refresh the model to get the latest data
        $inventory->refresh();

        // Return the updated resource
        return new InventoryResource($inventory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory): JsonResponse
    {
        $inventory->delete();
        return response()->json(null, 204);
    }

    /**
     * Adjust inventory quantity.
     */
    public function adjustQuantity(int $inventoryId, Request $request): InventoryResource
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:add,subtract'
        ]);

        $inventory = Inventory::findOrFail($inventoryId);
        
        if ($validatedData['type'] === 'add') {
            $inventory->quantity += $validatedData['quantity'];
        } else {
            $inventory->quantity -= $validatedData['quantity'];
        }

        $inventory->save();

        return new InventoryResource($inventory);
    }

    /**
     * Get low stock items.
     */
    public function lowStockItems(): AnonymousResourceCollection
    {
        $lowStockItems = Inventory::where('quantity', '<=', 10)->get();
        return InventoryResource::collection($lowStockItems);
    }

    /**
     * Bulk update inventory quantities.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:inventories,id',
            'updates.*.quantity' => 'required|integer|min:0'
        ]);

        $results = [];
        foreach ($validatedData['updates'] as $update) {
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

        return response()->json([
            'message' => 'Bulk inventory update processed',
            'results' => $results
        ]);
    }
}
