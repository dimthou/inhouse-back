<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * @var InventoryService
     */
    protected $inventoryService;

    /**
     * InventoryController constructor.
     *
     * @param InventoryService $inventoryService
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return InventoryResource::collection(Inventory::all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param InventoryRequest $request
     * @return InventoryResource
     */
    public function store(InventoryRequest $request): InventoryResource
    {
        $inventory = Inventory::create($request->validated());
        return new InventoryResource($inventory);
    }

    /**
     * Display the specified resource.
     *
     * @param Inventory $inventory
     * @return InventoryResource
     */
    public function show(Inventory $inventory): InventoryResource
    {
        return new InventoryResource($inventory);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventory $inventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param InventoryRequest $request
     * @param Inventory $inventory
     * @return InventoryResource
     */
    public function update(InventoryRequest $request, Inventory $inventory): InventoryResource
    {
        $inventory->update($request->validated());
        return new InventoryResource($inventory);
    }

    /**
     * Partially update the specified resource in storage.
     *
     * @param InventoryRequest $request
     * @param Inventory $inventory
     * @return InventoryResource
     */
    public function patch(InventoryRequest $request, Inventory $inventory): InventoryResource
    {
        $inventory->fill($request->validated());
        $inventory->save();

        return new InventoryResource($inventory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Inventory $inventory
     * @return JsonResponse
     */
    public function destroy(Inventory $inventory): JsonResponse
    {
        $inventory->delete();
        return response()->json(null, 204);
    }

    /**
     * Adjust inventory quantity.
     *
     * @param int $inventoryId
     * @param Request $request
     * @return InventoryResource
     */
    public function adjustQuantity(int $inventoryId, Request $request): InventoryResource
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:add,subtract'
        ]);

        $inventory = $this->inventoryService->adjustInventory(
            $inventoryId, 
            $validatedData['quantity'], 
            $validatedData['type']
        );

        return new InventoryResource($inventory);
    }

    /**
     * Get low stock items.
     *
     * @return AnonymousResourceCollection
     */
    public function lowStockItems(): AnonymousResourceCollection
    {
        $lowStockItems = $this->inventoryService->getLowStockItems();
        return InventoryResource::collection($lowStockItems);
    }

    /**
     * Bulk update inventory quantities.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:inventories,id',
            'updates.*.quantity' => 'required|integer|min:0'
        ]);

        $results = $this->inventoryService->bulkUpdateInventory($validatedData['updates']);

        return response()->json([
            'message' => 'Bulk inventory update processed',
            'results' => $results
        ]);
    }
}
