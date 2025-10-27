<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Repositories\InventoryRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
            $request->input('per_page', 5),
            array_filter($filters)
        );

        return InventoryResource::collection($inventoryPaginator);
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
        $inventory->update($request->validated());
        $inventory->refresh();
        return new InventoryResource($inventory);
    }

    /**
     * Partially update the specified resource in storage.
     */
    public function patch(InventoryRequest $request, Inventory $inventory): InventoryResource
    {
        $inventory->fill($request->validated());
        $inventory->save();
        $inventory->refresh();
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
        $lowStockItems = $this->inventoryRepository->getLowStockItems();
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

        $results = $this->inventoryRepository->bulkUpdate($validatedData['updates']);

        return response()->json([
            'message' => 'Bulk inventory update processed',
            'results' => $results
        ]);
    }
}
