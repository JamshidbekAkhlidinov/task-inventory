<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Storage;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * List all products, including their category.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->with('category')->orderBy('name')->get()
        );
    }

    /**
     * Create a product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a product.
     */
    public function update(StoreProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product->load('category')))->response();
    }

    /**
     * Soft delete a product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

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
