<?php

namespace App\Services;

use App\Enums\ClientRefundStatus;
use App\Enums\StockMovementType;
use App\Models\BatchItem;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientRefund;
use App\Models\ClientRefundItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ClientRefundService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Return sold products from a client order, restoring each unit to
     * the exact batch it was originally allocated from (FIFO-aware).
     *
     * @param array<int, array{
     *     order_item_id: int,
     *     quantity: int
     * }> $items
     */
    public function create(ClientOrder $order, array $items): ClientRefund
    {
        if (empty($items)) {
            throw new InvalidArgumentException(
                'Client refund must contain at least one item.'
            );
        }

        return DB::transaction(function () use ($order, $items) {
            $refund = ClientRefund::query()->create([
                'order_id' => $order->id,
                'status' => ClientRefundStatus::COMPLETED,
                'refunded_at' => now(),
            ]);

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException(
                        'Refund item quantity must be greater than zero.'
                    );
                }

                $orderItem = $order->items()
                    ->whereKey($item['order_item_id'])
                    ->first();

                if (! $orderItem) {
                    throw new RuntimeException(
                        "Order item #{$item['order_item_id']} does not belong to order #{$order->id}."
                    );
                }

                $alreadyRefunded = (int) ClientRefundItem::query()
                    ->whereHas('orderAllocation', function ($query) use ($orderItem) {
                        $query->where('order_item_id', $orderItem->id);
                    })
                    ->sum('quantity');

                $remainingRefundable = $orderItem->quantity - $alreadyRefunded;

                if ($quantity > $remainingRefundable) {
                    throw new RuntimeException(
                        "Cannot refund {$quantity} units. Only {$remainingRefundable} units are refundable."
                    );
                }

                $remaining = $quantity;

                $allocations = ClientOrderAllocation::query()
                    ->where('order_item_id', $orderItem->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($allocations as $allocation) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $alreadyRefundedFromAllocation = (int) ClientRefundItem::query()
                        ->where('order_allocation_id', $allocation->id)
                        ->sum('quantity');

                    $availableFromAllocation = $allocation->quantity - $alreadyRefundedFromAllocation;

                    if ($availableFromAllocation <= 0) {
                        continue;
                    }

                    $refundQuantity = min($remaining, $availableFromAllocation);

                    ClientRefundItem::query()->create([
                        'refund_id' => $refund->id,
                        'order_allocation_id' => $allocation->id,
                        'quantity' => $refundQuantity,
                    ]);

                    $batchItem = BatchItem::query()
                        ->where('batch_id', $allocation->batch_id)
                        ->where('product_id', $orderItem->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $batchItem->increment('available_quantity', $refundQuantity);

                    $this->inventoryService->increase(
                        storage: $order->storage,
                        product: $orderItem->product,
                        quantity: $refundQuantity,
                        batch: $allocation->batch,
                        type: StockMovementType::CLIENT_REFUND,
                        reference: $refund,
                    );

                    $remaining -= $refundQuantity;
                }

                if ($remaining > 0) {
                    throw new RuntimeException(
                        "Unable to allocate client refund. Missing quantity: {$remaining}."
                    );
                }
            }

            return $refund->load('items.orderAllocation.batch');
        });
    }
}
