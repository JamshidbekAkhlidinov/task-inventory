<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\ClientOrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * Create a client order. The client only sends the product and
     * quantity; FIFO batch allocation and unit cost are resolved
     * entirely on the backend.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $items = collect($data['products'])
            ->map(fn (array $product) => [
                'product_id' => $product['id'],
                'quantity' => $product['qty'],
            ])
            ->all();

        $order = $this->orderService->create(
            clientId: $data['client_id'],
            storageId: $data['storage_id'],
            items: $items,
        );

        return (new ClientOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
