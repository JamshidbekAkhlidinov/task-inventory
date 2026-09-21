<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRefundRequest;
use App\Http\Resources\ClientRefundResource;
use App\Models\ClientOrder;
use App\Services\ClientRefundService;
use Illuminate\Http\JsonResponse;

class ClientRefundController extends Controller
{
    public function __construct(
        private readonly ClientRefundService $clientRefundService,
    ) {}

    /**
     * Return sold units from a client order. Each unit is restored to
     * the exact batch it was originally allocated from.
     */
    public function store(StoreClientRefundRequest $request): JsonResponse
    {
        $data = $request->validated();

        $refund = $this->clientRefundService->create(
            order: ClientOrder::findOrFail($data['order_id']),
            items: $data['items'],
        );

        return (new ClientRefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }
}
