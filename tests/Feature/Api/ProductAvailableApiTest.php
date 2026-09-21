<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Storage;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAvailableApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_lists_products_with_available_stock_in_the_storage(): void
    {
        $storage = Storage::factory()->create();
        $inStock = Product::factory()->create();
        $outOfStock = Product::factory()->create();

        app(InventoryService::class)->increase($storage, $inStock, 5);

        $response = $this->getJson("/api/products/available?storage_id={$storage->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $inStock->id]);
        $response->assertJsonMissing(['id' => $outOfStock->id]);
    }
}
