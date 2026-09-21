<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Storage;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageStockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_current_stock_for_a_storage(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();

        app(InventoryService::class)->increase($storage, $product, 12);

        $response = $this->getJson("/api/storage/stock?storage_id={$storage->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'product_id' => $product->id,
            'quantity' => 12,
        ]);
    }

    public function test_it_reconstructs_historical_stock_from_movements(): void
    {
        $storage = Storage::factory()->create();
        $product = Product::factory()->create();
        $inventory = app(InventoryService::class);

        $base = now();

        $this->travelTo($base->copy()->subDays(10));
        $inventory->increase($storage, $product, 20);

        $this->travelTo($base->copy()->subDays(5));
        $inventory->decrease($storage, $product, 8);

        $this->travelTo($base->copy());
        $inventory->increase($storage, $product, 3);

        $asOf = $base->copy()->subDays(7)->toDateString();

        $response = $this->getJson("/api/storage/stock?storage_id={$storage->id}&date={$asOf}");

        $response->assertOk();
        $response->assertJsonPath('stock.0.quantity', 20);

        $this->travelBack();
    }
}
