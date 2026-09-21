<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\BatchResource;
use App\Models\Provider;
use App\Models\Storage;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * Create a purchase batch. Increases storage stock and records a
     * purchase stock movement for each item, atomically.
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $data = $request->validated();

        $batch = $this->purchaseService->create(
            provider: Provider::findOrFail($data['provider_id']),
            storage: Storage::findOrFail($data['storage_id']),
            items: $data['items'],
            reference: $data['reference'] ?? null,
            purchasedAt: isset($data['purchased_at']) ? new \DateTime($data['purchased_at']) : null,
        );

        return (new BatchResource($batch))
            ->response()
            ->setStatusCode(201);
    }
}
