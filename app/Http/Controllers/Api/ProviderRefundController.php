<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderRefundRequest;
use App\Http\Resources\ProviderRefundResource;
use App\Models\Batch;
use App\Services\ProviderRefundService;
use Illuminate\Http\JsonResponse;

class ProviderRefundController extends Controller
{
    public function __construct(
        private readonly ProviderRefundService $providerRefundService,
    ) {}

    public function store(StoreProviderRefundRequest $request): JsonResponse
    {
        $data = $request->validated();

        $refund = $this->providerRefundService->create(
            batch: Batch::findOrFail($data['batch_id']),
            items: $data['items'],
        );

        return (new ProviderRefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }
}
