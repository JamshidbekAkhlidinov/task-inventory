<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_products_with_category_name(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        Product::factory()->create(['category_id' => $category->id, 'name' => 'Mouse']);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Mouse', 'category_name' => 'Electronics']);
    }

    public function test_it_creates_a_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Keyboard',
            'sale_price' => 250000,
            'is_active' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('products', [
            'category_id' => $category->id,
            'name' => 'Keyboard',
            'sale_price' => 250000,
        ]);
    }

    public function test_it_rejects_a_negative_sale_price(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Keyboard',
            'sale_price' => -10,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_updates_a_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'sale_price' => 100]);

        $response = $this->patchJson("/api/products/{$product->id}", [
            'category_id' => $category->id,
            'name' => $product->name,
            'sale_price' => 200,
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertEquals(200, $response->json('sale_price'));
        $response->assertJsonPath('is_active', false);
    }

    public function test_it_soft_deletes_a_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->getJson('/api/products')->assertJsonCount(0);
    }
}
