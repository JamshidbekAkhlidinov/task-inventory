<?php

namespace App\Services;

use App\Enums\ProviderRefundStatus;
use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\ProviderRefund;
use App\Models\ProviderRefundItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ProviderRefundService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Return purchased products from a single batch back to its provider.
     *
     * @param array<int, array{
     *     product_id: int,
     *     quantity: int
     * }> $items
     */
    public function create(Batch $batch, array $items): ProviderRefund
    {
        if (empty($items)) {
            throw new InvalidArgumentException(
                'Provider refund must contain at least one item.'
            );
        }

        return DB::transaction(function () use ($batch, $items) {
            $refund = ProviderRefund::query()->create([
                'batch_id' => $batch->id,
                'status' => ProviderRefundStatus::COMPLETED,
                'refunded_at' => now(),
            ]);

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException(
                        'Refund item quantity must be greater than zero.'
                    );
                }

                $batchItem = BatchItem::query()
                    ->where('batch_id', $batch->id)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $batchItem) {
                    throw new RuntimeException(
                        "Batch #{$batch->id} has no item for product #{$item['product_id']}."
                    );
                }

                if ($batchItem->available_quantity < $quantity) {
                    throw new RuntimeException(
                        "Cannot refund {$quantity} units of product #{$item['product_id']}. Only {$batchItem->available_quantity} units are available in this batch."
                    );
                }

                ProviderRefundItem::query()->create([
                    'refund_id' => $refund->id,
                    'batch_item_id' => $batchItem->id,
                    'quantity' => $quantity,
                ]);

                $batchItem->decrement('available_quantity', $quantity);

                $this->inventoryService->decrease(
                    storage: $batch->storage,
                    product: $batchItem->product,
                    quantity: $quantity,
                    batch: $batch,
                    type: StockMovementType::PROVIDER_REFUND,
                    reference: $refund,
                );
            }

            return $refund->load('items.batchItem.product');
        });
    }
}
