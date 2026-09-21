<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Models\StorageStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Increase product stock in a storage.
     */
    public function increase(
        Storage $storage,
        Product $product,
        int $quantity,
        ?Batch $batch = null,
        StockMovementType $type = StockMovementType::PURCHASE,
        ?Model $reference = null,
    ): StorageStock {
        $this->validateQuantity($quantity);

        return DB::transaction(function () use (
            $storage,
            $product,
            $quantity,
            $batch,
            $type,
            $reference,
        ) {
            $stock = StorageStock::query()->lockForUpdate()->firstOrCreate(
                [
                    'storage_id' => $storage->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => 0,
                ],
            );

            $stock->increment('quantity', $quantity);

            $this->createMovement(
                storage: $storage,
                product: $product,
                quantity: $quantity,
                type: $type,
                batch: $batch,
                reference: $reference,
            );

            return $stock->refresh();
        });
    }

    /**
     * Decrease product stock in a storage. Never allows negative stock.
     */
    public function decrease(
        Storage $storage,
        Product $product,
        int $quantity,
        ?Batch $batch = null,
        StockMovementType $type = StockMovementType::SALE,
        ?Model $reference = null,
    ): StorageStock {
        $this->validateQuantity($quantity);

        return DB::transaction(function () use (
            $storage,
            $product,
            $quantity,
            $batch,
            $type,
            $reference,
        ) {
            $stock = StorageStock::query()
                ->where('storage_id', $storage->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->quantity < $quantity) {
                throw new RuntimeException(
                    "Insufficient stock for product #{$product->id} in storage #{$storage->id}."
                );
            }

            $stock->decrement('quantity', $quantity);

            $this->createMovement(
                storage: $storage,
                product: $product,
                quantity: $quantity,
                type: $type,
                batch: $batch,
                reference: $reference,
            );

            return $stock->refresh();
        });
    }

    /**
     * Create a stock movement record.
     */
    public function createMovement(
        Storage $storage,
        Product $product,
        int $quantity,
        StockMovementType $type,
        ?Batch $batch = null,
        ?Model $reference = null,
    ): StockMovement {
        $this->validateQuantity($quantity);

        return StockMovement::query()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'batch_id' => $batch?->id,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);
    }

    /**
     * Get current stock quantity for a product in a storage.
     */
    public function quantity(Storage $storage, Product $product): int
    {
        return (int) StorageStock::query()
            ->where('storage_id', $storage->id)
            ->where('product_id', $product->id)
            ->value('quantity') ?: 0;
    }

    /**
     * Current stock for every product in a storage.
     *
     * @return Collection<int, StorageStock>
     */
    public function currentStock(Storage $storage): Collection
    {
        return StorageStock::query()
            ->where('storage_id', $storage->id)
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();
    }

    /**
     * Current stock for every product across every storage.
     *
     * @return Collection<int, StorageStock>
     */
    public function allCurrentStock(): Collection
    {
        return StorageStock::query()
            ->where('quantity', '>', 0)
            ->with(['storage', 'product'])
            ->get();
    }

    /**
     * Reconstruct stock quantities for every storage as of a given date,
     * derived from the stock_movements ledger.
     *
     * @return Collection<int, array{storage_id: int, storage_name: ?string, product_id: int, product_name: ?string, quantity: int}>
     */
    public function allHistoricalStock(\DateTimeInterface $date, ?Product $product = null): Collection
    {
        $increasing = [
            StockMovementType::PURCHASE->value,
            StockMovementType::CLIENT_REFUND->value,
        ];

        $query = StockMovement::query()
            ->join('storages', 'storages.id', '=', 'stock_movements.storage_id')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.created_at', '<=', $date)
            ->selectRaw(
                'stock_movements.storage_id as storage_id,'
                .' storages.name as storage_name,'
                .' stock_movements.product_id as product_id,'
                .' products.name as product_name,'
                .' SUM(CASE WHEN stock_movements.type IN (?, ?) THEN stock_movements.quantity ELSE -stock_movements.quantity END) as quantity',
                $increasing
            )
            ->groupBy('stock_movements.storage_id', 'storages.name', 'stock_movements.product_id', 'products.name');

        if ($product) {
            $query->where('stock_movements.product_id', $product->id);
        }

        return $query->get()->map(fn ($row) => [
            'storage_id' => (int) $row->storage_id,
            'storage_name' => $row->storage_name,
            'product_id' => (int) $row->product_id,
            'product_name' => $row->product_name,
            'quantity' => (int) $row->quantity,
        ]);
    }

    /**
     * Reconstruct stock quantities for a storage as of a given date,
     * derived from the stock_movements ledger rather than the current
     * storage_stocks snapshot.
     *
     * @return Collection<int, array{product_id: int, quantity: int}>
     */
    public function historicalStock(Storage $storage, \DateTimeInterface $date, ?Product $product = null): Collection
    {
        $increasing = [
            StockMovementType::PURCHASE->value,
            StockMovementType::CLIENT_REFUND->value,
        ];

        $query = StockMovement::query()
            ->where('storage_id', $storage->id)
            ->where('created_at', '<=', $date)
            ->selectRaw(
                'product_id, SUM(CASE WHEN type IN (?, ?) THEN quantity ELSE -quantity END) as quantity',
                $increasing
            )
            ->groupBy('product_id');

        if ($product) {
            $query->where('product_id', $product->id);
        }

        return $query->get()->map(fn ($row) => [
            'product_id' => (int) $row->product_id,
            'quantity' => (int) $row->quantity,
        ]);
    }

    /**
     * Products that currently have available stock in a storage.
     *
     * @return Collection<int, Product>
     */
    public function availableProducts(Storage $storage): Collection
    {
        return Product::query()
            ->whereHas('storageStocks', function ($query) use ($storage) {
                $query->where('storage_id', $storage->id)
                    ->where('quantity', '>', 0);
            })
            ->with(['storageStocks' => function ($query) use ($storage) {
                $query->where('storage_id', $storage->id);
            }])
            ->get();
    }

    private function validateQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException(
                'Stock quantity must be greater than zero.'
            );
        }
    }
}
