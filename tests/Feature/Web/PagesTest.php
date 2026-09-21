<?php

namespace Tests\Feature\Web;

use App\Enums\ClientOrderStatus;
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

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders(): void
    {
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_clients_page_renders(): void
    {
        $this->get(route('clients.index'))->assertOk();
    }

    public function test_providers_page_renders(): void
    {
        $this->get(route('providers.index'))->assertOk();
    }

    public function test_categories_page_renders(): void
    {
        $this->get(route('categories.index'))->assertOk();
    }

    public function test_products_page_renders(): void
    {
        $this->get(route('products.index'))->assertOk();
    }

    public function test_storages_page_renders(): void
    {
        $this->get(route('storages.index'))->assertOk();
    }

    public function test_purchase_form_renders(): void
    {
        $this->get(route('purchases.create'))->assertOk();
    }

    public function test_order_form_renders(): void
    {
        $this->get(route('orders.create'))->assertOk();
    }

    public function test_provider_refund_form_lists_batches_with_available_stock(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 5,
        ]);

        $response = $this->get(route('provider-refunds.create'));

        $response->assertOk();
        $response->assertSee($product->name);
    }

    public function test_client_refund_form_lists_refundable_orders(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $batch = Batch::factory()->create(['storage_id' => $storage->id]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
            'status' => ClientOrderStatus::COMPLETED,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
        ]);

        $response = $this->get(route('client-refunds.create'));

        $response->assertOk();
        $response->assertSee($client->name);
    }

    public function test_batch_profit_page_renders(): void
    {
        $this->get(route('batches.profit'))->assertOk();
    }

    public function test_provider_refund_form_renders_when_batchs_provider_is_soft_deleted(): void
    {
        $provider = Provider::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'storage_id' => $storage->id,
        ]);

        BatchItem::factory()->create([
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'available_quantity' => 5,
        ]);

        $provider->delete();

        $this->get(route('provider-refunds.create'))->assertOk();
    }

    public function test_client_refund_form_renders_when_orders_client_is_soft_deleted(): void
    {
        $client = Client::factory()->create();
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $batch = Batch::factory()->create(['storage_id' => $storage->id]);

        $order = ClientOrder::factory()->create([
            'client_id' => $client->id,
            'storage_id' => $storage->id,
            'status' => ClientOrderStatus::COMPLETED,
        ]);

        $orderItem = ClientOrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        ClientOrderAllocation::factory()->create([
            'order_item_id' => $orderItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
        ]);

        $client->delete();

        $this->get(route('client-refunds.create'))->assertOk();
    }
}
