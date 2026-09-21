<?php

namespace Tests\Feature\Api;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchProfitApiTest extends TestCase
{
    use RefreshDatabase;

    private function createSoldBatch(): Batch
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

        return $batch;
    }

    public function test_it_returns_profit_for_a_single_batch(): void
    {
        $batch = $this->createSoldBatch();

        $response = $this->getJson("/api/batches/{$batch->id}/profit");

        $response->assertOk();
        $response->assertJson([
            'batch_id' => $batch->id,
            'quantity_sold' => 10,
            'revenue' => 1500,
            'cost' => 1000,
            'profit' => 500,
        ]);
    }

    public function test_it_returns_profit_for_all_batches(): void
    {
        $this->createSoldBatch();
        $this->createSoldBatch();

        $response = $this->getJson('/api/batches/profit');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
