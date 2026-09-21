<?php

namespace Tests\Feature\Api;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use App\Models\StorageStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_and_allocates_fifo_without_the_client_sending_batch_or_cost(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create(['sale_price' => 200]);
        $provider = Provider::factory()->create();

        $oldBatch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'purchased_at' => now()->subDays(20),
        ]);

        $newBatch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
            'purchased_at' => now()->subDays(5),
        ]);

        BatchItem::factory()->create([
            'batch_id' => $oldBatch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 100,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $newBatch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 10,
            'unit_cost' => 120,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        // Only client_id, storage_id and products (id + qty) are sent.
        $response = $this->postJson('/api/orders', [
            'client_id' => $client->id,
            'storage_id' => $storage->id,
            'products' => [
                ['id' => $product->id, 'qty' => 15],
            ],
        ]);

        $response->assertCreated();
        $this->assertEquals(200, $response->json('items.0.unit_price'));
        $this->assertEquals(3000, $response->json('total_amount'));

        $allocations = $response->json('items.0.allocations');
        $this->assertCount(2, $allocations);
        $this->assertSame($oldBatch->id, $allocations[0]['batch_id']);
        $this->assertSame(10, $allocations[0]['quantity']);
        $this->assertSame($newBatch->id, $allocations[1]['batch_id']);
        $this->assertSame(5, $allocations[1]['quantity']);

        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_it_rolls_back_the_whole_order_when_stock_is_insufficient(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create(['storage_id' => $storage->id]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 5,
            'unit_cost' => 100,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response = $this->postJson('/api/orders', [
            'client_id' => $client->id,
            'storage_id' => $storage->id,
            'products' => [
                ['id' => $product->id, 'qty' => 50],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('client_orders', 0);
        $this->assertDatabaseCount('client_order_allocations', 0);
        $this->assertDatabaseHas('storage_stocks', [
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }
}
