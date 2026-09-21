<?php

namespace Tests\Unit\Services;

use App\Enums\ClientOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Models\StorageStock;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = app(OrderService::class);
    }

    public function test_it_can_create_order(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'available_quantity' => 20,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $order = $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 150000,
                ],
            ],
        );

        $this->assertInstanceOf(ClientOrder::class, $order);

        $this->assertSame(
            ClientOrderStatus::COMPLETED,
            $order->status
        );

        $this->assertEquals(
            750000,
            $order->total_amount
        );

        $this->assertDatabaseHas('client_order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 150000,
            'total_price' => 750000,
        ]);
    }

    public function test_it_defaults_unit_price_to_product_sale_price_when_not_given(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create([
            'sale_price' => 200000,
        ]);

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $order = $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                ],
            ],
        );

        $this->assertDatabaseHas('client_order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 200000,
            'total_price' => 600000,
        ]);
    }

    public function test_it_allocates_stock_using_fifo(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $provider = Provider::factory()->create();

        $oldBatch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'purchased_at' => now()->subDays(20),
        ]);

        $newBatch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'purchased_at' => now()->subDays(10),
        ]);

        $oldBatchItem = BatchItem::factory()->create([
            'batch_id' => $oldBatch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100000,
        ]);

        $newBatchItem = BatchItem::factory()->create([
            'batch_id' => $newBatch->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'available_quantity' => 20,
            'unit_cost' => 110000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);

        $order = $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 15,
                    'unit_price' => 150000,
                ],
            ],
        );

        $orderItem = $order->items->first();

        $allocations = $orderItem->allocations
            ->sortBy('id')
            ->values();

        $this->assertCount(2, $allocations);

        $this->assertSame(
            $oldBatch->id,
            $allocations[0]->batch_id
        );

        $this->assertSame(
            10,
            $allocations[0]->quantity
        );

        $this->assertEquals(
            100000,
            $allocations[0]->unit_cost
        );

        $this->assertSame(
            $newBatch->id,
            $allocations[1]->batch_id
        );

        $this->assertSame(
            5,
            $allocations[1]->quantity
        );

        $this->assertEquals(
            110000,
            $allocations[1]->unit_cost
        );

        $this->assertDatabaseHas('batch_items', [
            'id' => $oldBatchItem->id,
            'available_quantity' => 0,
        ]);

        $this->assertDatabaseHas('batch_items', [
            'id' => $newBatchItem->id,
            'available_quantity' => 15,
        ]);
    }

    public function test_it_decreases_storage_stock(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'available_quantity' => 20,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 7,
                    'unit_price' => 150000,
                ],
            ],
        );

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 13,
        ]);
    }

    public function test_it_creates_sale_movements(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $order = $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 150000,
                ],
            ],
        );

        $this->assertDatabaseHas('stock_movements', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'type' => StockMovementType::SALE->value,
            'quantity' => 4,
            'reference_type' => $order->getMorphClass(),
            'reference_id' => $order->id,
        ]);
    }

    public function test_it_rejects_order_when_stock_is_insufficient(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 5,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 150000,
                ],
            ],
        );
    }

    public function test_it_rolls_back_when_stock_is_insufficient(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 5,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        try {
            $this->orderService->create(
                clientId: $client->id,
                storageId: $storage->id,
                items: [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10,
                        'unit_price' => 150000,
                    ],
                ],
            );
        } catch (\RuntimeException) {
            // Expected exception.
        }

        $this->assertDatabaseCount('client_orders', 0);
        $this->assertDatabaseCount('client_order_items', 0);
        $this->assertDatabaseCount('client_order_allocations', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('batch_items', [
            'id' => $batch->items()->first()->id,
            'available_quantity' => 5,
        ]);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_it_rejects_empty_order(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [],
        );
    }

    public function test_it_rejects_invalid_quantity(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'unit_price' => 150000,
                ],
            ],
        );
    }

    public function test_it_rejects_negative_unit_price(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->orderService->create(
            clientId: $client->id,
            storageId: $storage->id,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => -100,
                ],
            ],
        );
    }
}
