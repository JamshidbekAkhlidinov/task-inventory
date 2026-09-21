<?php

namespace Tests\Unit\Services;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Storage;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = app(InventoryService::class);
    }

    public function test_it_can_increase_stock(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $stock = $this->inventoryService->increase(
            storage: $storage,
            product: $product,
            quantity: 10,
        );

        $this->assertSame(10, $stock->quantity);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'type' => StockMovementType::PURCHASE->value,
        ]);
    }

    public function test_it_can_decrease_stock(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->inventoryService->increase(
            storage: $storage,
            product: $product,
            quantity: 20,
        );

        $stock = $this->inventoryService->decrease(
            storage: $storage,
            product: $product,
            quantity: 7,
        );

        $this->assertSame(13, $stock->quantity);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 13,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 7,
            'type' => StockMovementType::SALE->value,
        ]);
    }

    public function test_it_cannot_decrease_more_than_available_stock(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->inventoryService->increase(
            storage: $storage,
            product: $product,
            quantity: 5,
        );

        $this->expectException(\RuntimeException::class);

        $this->inventoryService->decrease(
            storage: $storage,
            product: $product,
            quantity: 10,
        );
    }

    public function test_it_rejects_zero_quantity(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->inventoryService->increase(
            storage: $storage,
            product: $product,
            quantity: 0,
        );
    }

    public function test_it_rejects_negative_quantity(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->expectException(\RuntimeException::class);

        $this->inventoryService->increase(
            storage: $storage,
            product: $product,
            quantity: -5,
        );
    }

    public function test_it_can_return_current_stock_quantity(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->inventoryService->increase(
            storage: $storage,
            product: $product,
            quantity: 25,
        );

        $quantity = $this->inventoryService->quantity(
            storage: $storage,
            product: $product,
        );

        $this->assertSame(25, $quantity);
    }

    public function test_it_can_create_movement_with_batch(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        $movement = $this->inventoryService->createMovement(
            storage: $storage,
            product: $product,
            quantity: 10,
            type: StockMovementType::PURCHASE,
            batch: $batch,
        );

        $this->assertInstanceOf(
            StockMovement::class,
            $movement
        );

        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'quantity' => 10,
            'type' => StockMovementType::PURCHASE->value,
        ]);
    }

    public function test_it_reconstructs_historical_stock_from_movements(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $base = now();

        $this->travelTo($base->copy()->subDays(10));
        $this->inventoryService->increase($storage, $product, 20);

        $this->travelTo($base->copy()->subDays(5));
        $this->inventoryService->decrease($storage, $product, 5);

        $this->travelTo($base->copy());
        $this->inventoryService->increase($storage, $product, 8);

        $historicalBeforeSecondPurchase = $this->inventoryService->historicalStock(
            $storage,
            $base->copy()->subDays(4),
        );

        $this->assertSame(15, $historicalBeforeSecondPurchase->first()['quantity']);

        $historicalToday = $this->inventoryService->historicalStock($storage, $base->copy());

        $this->assertSame(23, $historicalToday->first()['quantity']);

        $this->travelBack();
    }
}
