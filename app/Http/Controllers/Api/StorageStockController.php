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
     * Current or historical stock for a storage. When a `date` is
     * given, quantities are reconstructed from the stock_movements
     * ledger instead of the current storage_stocks snapshot.
     */
    public function index(StorageStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $storage = Storage::findOrFail($data['storage_id']);
        $product = isset($data['product_id']) ? Product::find($data['product_id']) : null;

        if (! empty($data['date'])) {
            $stock = $this->inventoryService->historicalStock(
                storage: $storage,
                date: new \DateTime($data['date']),
                product: $product,
            );

            return response()->json([
                'storage_id' => $storage->id,
                'date' => $data['date'],
                'stock' => $stock->values(),
            ]);
        }

        $stocks = $this->inventoryService->currentStock($storage);

        if ($product) {
            $stocks = $stocks->where('product_id', $product->id)->values();
        }

        return StorageStockResource::collection($stocks)->response();
    }
}
