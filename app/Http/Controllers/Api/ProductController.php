<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Storage;
use App\Services\InventoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Products that currently have available stock in a storage.
     */
    public function available(AvailableProductsRequest $request): AnonymousResourceCollection
    {
        $storage = Storage::findOrFail($request->validated('storage_id'));

        return ProductResource::collection(
            $this->inventoryService->availableProducts($storage)
        );
    }
}
