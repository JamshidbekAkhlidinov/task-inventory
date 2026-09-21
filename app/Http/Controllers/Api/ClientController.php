<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * List all clients.
     */
    public function index(): AnonymousResourceCollection
    {
        return ClientResource::collection(
            Client::query()->orderBy('name')->get()
        );
    }

    /**
     * Every order this client has placed, with what was bought and what
     * was later refunded per item (net of refunds, never the current
     * product price).
     */
    public function orders(Client $client): JsonResponse
    {
        $orders = $client->orders()
            ->with(['items.product', 'items.allocations.refundItems'])
            ->orderByDesc('ordered_at')
            ->get()
            ->map(function (ClientOrder $order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status->value,
                    'total_amount' => (float) $order->total_amount,
                    'ordered_at' => $order->ordered_at?->toIso8601String(),
                    'items' => $order->items->map(function (ClientOrderItem $item) {
                        $refundedQuantity = $item->allocations
                            ->flatMap(fn ($allocation) => $allocation->refundItems)
                            ->sum('quantity');

                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product?->name ?? "Product #{$item->product_id}",
                            'quantity' => $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'total_price' => (float) $item->total_price,
                            'refunded_quantity' => $refundedQuantity,
                            'kept_quantity' => $item->quantity - $refundedQuantity,
                        ];
                    }),
                ];
            });

        return response()->json(['data' => $orders]);
    }

    /**
     * Create a client.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::query()->create($request->validated());

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a client.
     */
    public function update(StoreClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return (new ClientResource($client))->response();
    }

    /**
     * Soft delete a client.
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(null, 204);
    }
}
