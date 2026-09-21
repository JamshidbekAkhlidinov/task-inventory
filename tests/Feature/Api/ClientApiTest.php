<?php

namespace Tests\Feature\Api;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\Product;
use App\Models\Storage;
use App\Models\StorageStock;
use App\Services\ClientRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_clients(): void
    {
        Client::factory()->count(2)->create();

        $response = $this->getJson('/api/clients');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_it_creates_a_client(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Acme Retail',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('name', 'Acme Retail');

        $this->assertDatabaseHas('clients', [
            'name' => 'Acme Retail',
        ]);
    }

    public function test_it_requires_a_name(): void
    {
        $response = $this->postJson('/api/clients', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_it_updates_a_client(): void
    {
        $client = Client::factory()->create(['name' => 'Old Name']);

        $response = $this->patchJson("/api/clients/{$client->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'New Name');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'New Name',
        ]);
    }

    public function test_it_soft_deletes_a_client(): void
    {
        $client = Client::factory()->create();

        $response = $this->deleteJson("/api/clients/{$client->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        $this->getJson('/api/clients')->assertJsonCount(0);
    }

    public function test_it_shows_a_clients_order_history_net_of_refunds(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create(['name' => 'Wireless Mouse']);
        $batch = Batch::factory()->create(['storage_id' => $storage->id]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'available_quantity' => 0,
            'unit_cost' => 100000,
        ]);

        StorageStock::factory()->create([
            'storage_id' => $storage->id,
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 150000,
            'total_price' => 750000,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
            'unit_cost' => 100000,
        ]);

        app(ClientRefundService::class)->create($order, [
            ['order_item_id' => $orderItem->id, 'quantity' => 2],
        ]);

        $response = $this->getJson("/api/clients/{$client->id}/orders");

        $response->assertOk();
        $response->assertJsonPath('data.0.items.0.product_name', 'Wireless Mouse');
        $response->assertJsonPath('data.0.items.0.quantity', 5);
        $response->assertJsonPath('data.0.items.0.refunded_quantity', 2);
        $response->assertJsonPath('data.0.items.0.kept_quantity', 3);
    }
}
