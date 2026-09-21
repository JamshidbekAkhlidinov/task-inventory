<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStorageRequest;
use App\Http\Resources\StorageResource;
use App\Models\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StorageController extends Controller
{
    /**
     * List all storages.
     */
    public function index(): AnonymousResourceCollection
    {
        return StorageResource::collection(
            Storage::query()->orderBy('name')->get()
        );
    }

    /**
     * Create a storage.
     */
    public function store(StoreStorageRequest $request): JsonResponse
    {
        $storage = Storage::query()->create($request->validated());

        return (new StorageResource($storage))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a storage.
     */
    public function update(StoreStorageRequest $request, Storage $storage): JsonResponse
    {
        $storage->update($request->validated());

        return (new StorageResource($storage))->response();
    }

    /**
     * Soft delete a storage.
     */
    public function destroy(Storage $storage): JsonResponse
    {
        $storage->delete();

        return response()->json(null, 204);
    }
}
