<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorageStockRequest;
use App\Http\Resources\StorageStockResource;
use App\Models\Product;
use App\Models\Storage;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class StorageStockController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Current or historical stock. Pass `storage_id` to scope to one
     * storage, or omit it to see every storage. When a `date` is given,
     * quantities are reconstructed from the stock_movements ledger
     * instead of the current storage_stocks snapshot.
     */
    public function index(StorageStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $storage = isset($data['storage_id']) ? Storage::find($data['storage_id']) : null;
        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;

        if (! empty($data['date'])) {
            $date = new \DateTime($data['date']);

            $stock = $storage
                ? $this->inventoryService->historicalStock($storage, $date, $product)
                : $this->inventoryService->allHistoricalStock($date, $product);

            return response()->json([
                'storage_id' => $storage?->id,
                'date' => $data['date'],
                'stock' => $stock->values(),
            ]);
        }

        if ($storage) {
            $stocks = $this->inventoryService->currentStock($storage);

            if ($product) {
                $stocks = $stocks->where('product_id', $product->id)->values();
            }

            return StorageStockResource::collection($stocks)->response();
        }

        $stocks = $this->inventoryService->allCurrentStock();

        if ($product) {
            $stocks = $stocks->where('product_id', $product->id)->values();
        }

        return response()->json($stocks->map(fn ($stock) => [
            'storage_id' => $stock->storage_id,
            'storage_name' => $stock->storage?->name,
            'product_id' => $stock->product_id,
            'product_name' => $stock->product?->name,
            'quantity' => $stock->quantity,
        ])->values());
    }
}
