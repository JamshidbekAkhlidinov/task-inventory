<?php

namespace App\Services;

use App\Enums\ClientOrderStatus;
use App\Enums\StockMovementType;
use App\Models\BatchItem;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\Storage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Create a client order and allocate stock using FIFO.
     *
     * The caller only needs to provide the product and quantity; the
     * unit price is resolved from the product's current sale price
     * when not explicitly given, and the actual unit cost is always
     * resolved internally from the oldest available batches.
     *
     * @param array<int, array{
     *     product_id: int,
     *     quantity: int,
     *     unit_price?: float|int|string|null
     * }> $items
     */
    public function create(
        int $clientId,
        int $storageId,
        array $items,
        ?\DateTimeInterface $orderedAt = null,
    ): ClientOrder {
        if (empty($items)) {
            throw new InvalidArgumentException(
                'Order must contain at least one item.'
            );
        }

        return DB::transaction(function () use (
            $clientId,
            $storageId,
            $items,
            $orderedAt,
        ) {
            $order = ClientOrder::query()->create([
                'client_id' => $clientId,
                'storage_id' => $storageId,
                'status' => ClientOrderStatus::PENDING,
                'total_amount' => 0,
                'ordered_at' => $orderedAt ?? now(),
            ]);

            $totalAmount = 0;

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException(
                        'Order item quantity must be greater than zero.'
                    );
                }

                $orderItem = ClientOrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => 0,
                    'total_price' => 0,
                ]);

                $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                    ? (float) $item['unit_price']
                    : (float) $orderItem->product->sale_price;

                if ($unitPrice < 0) {
                    throw new InvalidArgumentException(
                        'Order item unit price cannot be negative.'
                    );
                }

                $totalPrice = $quantity * $unitPrice;

                $orderItem->update([
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);

                $this->allocateFifo(
                    orderItem: $orderItem,
                    storage: $order->storage,
                    productId: $item['product_id'],
                    quantity: $quantity
                );

                $totalAmount += $totalPrice;
            }

            $order->update([
                'total_amount' => $totalAmount,
                'status' => ClientOrderStatus::COMPLETED,
            ]);

            return $order->load([
                'items.allocations.batch',
            ]);
        });
    }

    /**
     * Allocate stock from the oldest available batches first, locking
     * the candidate batch items so concurrent orders cannot oversell.
     */
    private function allocateFifo(ClientOrderItem $orderItem, Storage $storage, int $productId, int $quantity): void
    {
        $remaining = $quantity;

        $batchItems = BatchItem::query()
            ->where('product_id', $productId)
            ->where('available_quantity', '>', 0)
            ->whereHas('batch', function ($query) use ($storage) {
                $query->where('storage_id', $storage->id);
            })
            ->with('batch')
            ->join('batches', 'batches.id', '=', 'batch_items.batch_id')
            ->orderBy('batches.purchased_at')
            ->select('batch_items.*')
            ->lockForUpdate()
            ->get();

        foreach ($batchItems as $batchItem) {
            if ($remaining <= 0) {
                break;
            }

            $allocatedQuantity = min($remaining, $batchItem->available_quantity);

            ClientOrderAllocation::query()->create([
                'order_item_id' => $orderItem->id,
                'batch_id' => $batchItem->batch_id,
                'quantity' => $allocatedQuantity,
                'unit_cost' => $batchItem->unit_cost,
            ]);

            $batchItem->decrement('available_quantity', $allocatedQuantity);

            $this->inventoryService->decrease(
                storage: $storage,
                product: $orderItem->product,
                quantity: $allocatedQuantity,
                batch: $batchItem->batch,
                type: StockMovementType::SALE,
                reference: $orderItem->order,
            );

            $remaining -= $allocatedQuantity;
        }

        if ($remaining > 0) {
            throw new RuntimeException(
                "Insufficient FIFO stock for product #{$productId}. Missing quantity: {$remaining}."
            );
        }
    }
}
