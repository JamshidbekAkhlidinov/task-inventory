<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderController extends Controller
{
    /**
     * List all providers.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProviderResource::collection(
            Provider::query()->orderBy('name')->get()
        );
    }

    /**
     * Create a provider.
     */
    public function store(StoreProviderRequest $request): JsonResponse
    {
        $provider = Provider::query()->create($request->validated());

        return (new ProviderResource($provider))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a provider.
     */
    public function update(StoreProviderRequest $request, Provider $provider): JsonResponse
    {
        $provider->update($request->validated());

        return (new ProviderResource($provider))->response();
    }

    /**
     * Soft delete a provider.
     */
    public function destroy(Provider $provider): JsonResponse
    {
        $provider->delete();

        return response()->json(null, 204);
    }
}
