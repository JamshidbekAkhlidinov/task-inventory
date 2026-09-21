<?php

namespace Tests\Unit\Services;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Services\BatchProfitService;
use App\Services\ClientRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchProfitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_profit_for_a_single_batch(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $client = Client::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 0,
            'unit_cost' => 100,
        ]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 150,
            'total_price' => 1500,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 10,
            'unit_cost' => 100,
        ]);

        $profit = app(BatchProfitService::class)->forBatch($batch);

        $this->assertSame($batch->id, $profit['batch_id']);
        $this->assertSame(10, $profit['quantity_sold']);
        $this->assertEquals(1500, $profit['revenue']);
        $this->assertEquals(1000, $profit['cost']);
        $this->assertEquals(500, $profit['profit']);
    }

    public function test_it_uses_the_historical_order_price_not_the_current_product_price(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create([
            'sale_price' => 999,
        ]);
        $client = Client::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 0,
            'unit_cost' => 100,
        ]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 150,
            'total_price' => 750,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
            'unit_cost' => 100,
        ]);

        $profit = app(BatchProfitService::class)->forBatch($batch);

        $this->assertEquals(750, $profit['revenue']);
    }

    public function test_it_reduces_profit_for_refunded_units(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $client = Client::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 0,
            'unit_cost' => 100,
        ]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 150,
            'total_price' => 1500,
        ]);

        $allocation = ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 10,
            'unit_cost' => 100,
        ]);

        app(ClientRefundService::class)->create(
            order: $order,
            items: [
                ['order_item_id' => $orderItem->id, 'quantity' => 4],
            ],
        );

        $profit = app(BatchProfitService::class)->forBatch($batch);

        // 6 net units sold: revenue 6*150=900, cost 6*100=600, profit=300.
        $this->assertSame(6, $profit['quantity_sold']);
        $this->assertEquals(900, $profit['revenue']);
        $this->assertEquals(600, $profit['cost']);
        $this->assertEquals(300, $profit['profit']);
    }

    public function test_it_returns_zeroed_profit_for_a_batch_with_no_sales(): void
    {
        $batch = Batch::factory()->create();

        $profit = app(BatchProfitService::class)->forBatch($batch);

        $this->assertSame(0, $profit['quantity_sold']);
        $this->assertEquals(0, $profit['revenue']);
        $this->assertEquals(0, $profit['profit']);
    }

    public function test_it_aggregates_profit_across_multiple_batches(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $client = Client::factory()->create();

        $batchOne = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        $batchTwo = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batchOne->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 0,
            'unit_cost' => 100,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batchTwo->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 0,
            'unit_cost' => 120,
        ]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 200,
            'total_price' => 2000,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batchOne->id,
            'quantity' => 5,
            'unit_cost' => 100,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batchTwo->id,
            'quantity' => 5,
            'unit_cost' => 120,
        ]);

        $profits = app(BatchProfitService::class)->forAllBatches()->keyBy('batch_id');

        $this->assertEquals(500, $profits[$batchOne->id]['profit']);
        $this->assertEquals(400, $profits[$batchTwo->id]['profit']);
    }
}
