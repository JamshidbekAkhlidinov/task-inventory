<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Provider;
use App\Models\Storage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Create a purchase batch with its items.
     *
     * @param array<int, array{
     *     product_id: int,
     *     quantity: int,
     *     unit_cost: float|int|string
     * }> $items
     */
    public function create(
        Provider $provider,
        Storage $storage,
        array $items,
        ?string $reference = null,
        ?\DateTimeInterface $purchasedAt = null,
    ): Batch {
        if (empty($items)) {
            throw new InvalidArgumentException(
                'Purchase must contain at least one item.'
            );
        }

        return DB::transaction(function () use (
            $provider,
            $storage,
            $items,
            $reference,
            $purchasedAt,
        ) {
            $batch = Batch::query()->create([
                'provider_id' => $provider->id,
                'storage_id' => $storage->id,
                'purchased_at' => $purchasedAt ?? now(),
                'reference' => $reference,
            ]);

            foreach ($items as $item) {
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException(
                        'Purchase item quantity must be greater than zero.'
                    );
                }

                $unitCost = (float) $item['unit_cost'];

                if ($unitCost < 0) {
                    throw new InvalidArgumentException(
                        'Purchase item unit cost cannot be negative.'
                    );
                }

                $batchItem = BatchItem::query()->create([
                    'batch_id' => $batch->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'available_quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ]);

                $product = $batchItem->product;

                $this->inventoryService->increase(
                    storage: $storage,
                    product: $product,
                    quantity: $quantity,
                    batch: $batch,
                    type: StockMovementType::PURCHASE,
                    reference: $batch,
                );
            }

            return $batch->load('items');
        });
    }
}
