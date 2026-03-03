<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Repositories\InventoryRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;
use Nette\Utils\Json;

class InventoryController extends Controller
{
    /**
     * @var InventoryRepository
     */
    protected $inventoryRepository;

    /**
     * InventoryController constructor.
     *
     * @param InventoryRepository $inventoryRepository
     */
    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    /**
     * Display a paginated list of inventory items.
     * 
     * @group Inventory Management
     * @authenticated
     * 
     * @queryParam name string Filter by inventory name. Example: Product A
     * @queryParam min_quantity integer Filter by minimum quantity. Example: 10
     * @queryParam max_quantity integer Filter by maximum quantity. Example: 100
     * @queryParam min_price numeric Filter by minimum price. Example: 10.50
     * @queryParam max_price numeric Filter by maximum price. Example: 50.00
     * @queryParam sort_by string Sort field (created_at, name, quantity, price). Example: name
     * @queryParam sort_direction string Sort direction (asc, desc). Example: asc
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Extract and validate filter parameters
        $filters = [
            'name' => $request->input('name'),
            'min_quantity' => $request->input('min_quantity'),
            'max_quantity' => $request->input('max_quantity'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc')
        ];

        // Get paginated inventory with optional filters
        $inventoryPaginator = $this->inventoryRepository->getPaginatedInventory(
            $request->input('per_page', default: 150),
            array_filter($filters)
        );

        return InventoryResource::collection($inventoryPaginator);
    }

    /**
     * Store a newly created inventory item.
     * 
     * @group Inventory Management
     * @authenticated
     * 
     * @bodyParam name string required The name of the inventory item. Example: Product A
     * @bodyParam sku string Unique. Example: I-0001
     * @bodyParam quantity integer required The quantity in stock. Example: 100
     * @bodyParam price numeric required The price of the item. Example: 29.99
     */
    public function store(InventoryRequest $request): JsonResponse
    {
        // $inventory = Inventory::create($request->validated());
        // return new InventoryResource($inventory);
        try {
            $inventory = Inventory::create($request->validated());

            // Log the creation
            Log::info('Inventory created', [
                'inventory_id' => $inventory->id,
                'user_id' => Auth::id(),
                'data' => $request->validated()
            ]);

            return response()->json([
                'message' => 'Inventory item created successfully',
                'data' => new InventoryResource($inventory)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create inventory', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to create inventory item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory): InventoryResource
    {
        return new InventoryResource($inventory);
    }

    /**
     * Update the specified inventory item (full update).
     * 
     * @group Inventory Management
     * @authenticated
     * 
     * @urlParam inventory integer required The ID of the inventory item. Example: 1
     * @bodyParam name string required The name of the inventory item. Example: Product A Updated
     * @bodyParam quantity integer required The quantity in stock. Example: 150
     * @bodyParam price numeric required The price of the item. Example: 34.99
     */
    public function update(InventoryRequest $request, Inventory $inventory): JsonResponse
    {
        // $inventory->update($request->validated());
        // $inventory->refresh();
        // return new InventoryResource($inventory);

        try {
            $oldData = $inventory->toArray();

            $inventory->update($request->validated());
            $inventory->refresh();

            // Log the update
            Log::info('Inventory updated', [
                'inventory_id' => $inventory->id,
                'user_id' => Auth::id(),
                'old_data' => $oldData,
                'new_data' => $inventory->toArray()
            ]);

            return response()->json([
                'message' => 'Inventory item updated successfully',
                'data' => new InventoryResource($inventory)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to update inventory item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Partially update the specified inventory item.
     * 
     * @group Inventory Management
     * @authenticated
     * 
     * @urlParam inventory integer required The ID of the inventory item. Example: 1
     * @bodyParam name string Optional name. Example: Product A Modified
     * @bodyParam quantity integer Optional quantity. Example: 200
     * @bodyParam price numeric Optional price. Example: 39.99
     */
    public function patch(InventoryRequest $request, Inventory $inventory): JsonResponse
    {
        // $inventory->fill($request->validated());
        // $inventory->save();
        // $inventory->refresh();
        // return new InventoryResource($inventory);

        try {
            $oldData = $inventory->toArray();

            $inventory->fill($request->validated());
            $inventory->save();
            $inventory->refresh();

            // Log the patch
            Log::info('Inventory patched', [
                'inventory_id' => $inventory->id,
                'user_id' => Auth::id(),
                'changes' => $request->validated()
            ]);

            return response()->json([
                'message' => 'Inventory item updated successfully',
                'data' => new InventoryResource($inventory)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to patch inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to update inventory item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified inventory item.
     * 
     * @group Inventory Management
     * @authenticated
     * 
     * @urlParam inventory integer required The ID of the inventory item. Example: 1
     */
    public function destroy(Inventory $inventory): JsonResponse
    {
        // $inventory->delete();
        // return response()->json(null, 204);
        try {
            $inventoryData = $inventory->toArray();

            $inventory->delete();

            // Log the deletion
            Log::warning('Inventory deleted', [
                'inventory_id' => $inventory->id,
                'user_id' => Auth::id(),
                'deleted_data' => $inventoryData
            ]);

            return response()->json([
                'message' => 'Inventory item deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to delete inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to delete inventory item',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Adjust inventory quantity.
     */
    // public function adjustQuantity(int $inventoryId, Request $request): InventoryResource
    // {
    // $validatedData = $request->validate([
    //     'quantity' => 'required|integer',
    //     'type' => 'required|in:add,subtract'
    // ]);

    // $inventory = Inventory::findOrFail($inventoryId);

    // if ($validatedData['type'] === 'add') {
    //     $inventory->quantity += $validatedData['quantity'];
    // } else {
    //     $inventory->quantity -= $validatedData['quantity'];
    // }

    // $inventory->save();

    // return new InventoryResource($inventory);
    // }
    public function adjustQuantity(Inventory $inventory, Request $request): JsonResponse
    {
        try {
            $oldQuantity = $inventory->quantity;
            $adjustment = $request->validated();

            if ($adjustment['type'] === 'add') {
                $inventory->quantity += $adjustment['quantity'];
            } else {
                // Prevent negative stock
                if ($inventory->quantity < $adjustment['quantity']) {
                    return response()->json([
                        'message' => 'Insufficient stock',
                        'error' => "Cannot subtract {$adjustment['quantity']}. Current stock: {$inventory->quantity}"
                    ], 422);
                }
                $inventory->quantity -= $adjustment['quantity'];
            }

            $inventory->save();

            // Log the adjustment
            Log::info('Inventory quantity adjusted', [
                'inventory_id' => $inventory->id,
                'user_id' => Auth::id(),
                'old_quantity' => $oldQuantity,
                'new_quantity' => $inventory->quantity,
                'adjustment' => $adjustment
            ]);

            return response()->json([
                'message' => 'Inventory quantity adjusted successfully',
                'data' => new InventoryResource($inventory->refresh()),
                'adjustment' => [
                    'type' => $adjustment['type'],
                    'quantity' => $adjustment['quantity'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $inventory->quantity
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to adjust inventory quantity', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to adjust inventory quantity',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get low stock items.
     */
    public function lowStockItems(Request $request): AnonymousResourceCollection
    {
        // $lowStockItems = $this->inventoryRepository->getLowStockItems();
        // return InventoryResource::collection($lowStockItems);

        $threshold = $request->input('threshold');
        $lowStockItems = $this->inventoryRepository->getLowStockItems($threshold);

        return InventoryResource::collection($lowStockItems);
    }

    /**
     * Bulk update inventory quantities.
     * 
     * @group Inventory Management
     * @authenticated
     * 
     * @bodyParam updates array required Array of inventory updates. Example: [{"id": 1, "quantity": 100}, {"id": 2, "quantity": 50}]
     * @bodyParam updates.*.id integer required Inventory ID. Example: 1
     * @bodyParam updates.*.quantity integer required New quantity. Example: 100
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        // $validatedData = $request->validate([
        //     'updates' => 'required|array',
        //     'updates.*.id' => 'required|exists:inventories,id',
        //     'updates.*.quantity' => 'required|integer|min:0'
        // ]);

        // $results = $this->inventoryRepository->bulkUpdate($validatedData['updates']);

        // return response()->json([
        //     'message' => 'Bulk inventory update processed',
        //     'results' => $results
        // ]);
        try {
            $results = $this->inventoryRepository->bulkUpdate($request->validated()['updates']);

            // Log the bulk update
            Log::info('Bulk inventory update', [
                'user_id' => Auth::id(),
                'count' => count($request->validated()['updates']),
                'results' => $results
            ]);

            return response()->json([
                'message' => 'Bulk inventory update processed successfully',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to bulk update inventory', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to process bulk update',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
