<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_categories_with_provider_and_parent_names(): void
    {
        $provider = Provider::factory()->create(['name' => 'Acme']);
        $parent = Category::factory()->create(['provider_id' => $provider->id, 'name' => 'Electronics']);
        Category::factory()->create(['provider_id' => $provider->id, 'parent_id' => $parent->id, 'name' => 'Peripherals']);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Peripherals', 'provider_name' => 'Acme', 'parent_name' => 'Electronics']);
    }

    public function test_it_creates_a_category(): void
    {
        $provider = Provider::factory()->create();

        $response = $this->postJson('/api/categories', [
            'provider_id' => $provider->id,
            'name' => 'Office Supplies',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'provider_id' => $provider->id,
            'name' => 'Office Supplies',
        ]);
    }

    public function test_it_rejects_a_category_for_an_unknown_provider(): void
    {
        $response = $this->postJson('/api/categories', [
            'provider_id' => 999,
            'name' => 'Ghost Category',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_updates_a_category(): void
    {
        $provider = Provider::factory()->create();
        $category = Category::factory()->create(['provider_id' => $provider->id, 'name' => 'Old Name']);

        $response = $this->patchJson("/api/categories/{$category->id}", [
            'provider_id' => $provider->id,
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'New Name');
    }

    public function test_it_rejects_a_category_being_its_own_parent(): void
    {
        $provider = Provider::factory()->create();
        $category = Category::factory()->create(['provider_id' => $provider->id]);

        $response = $this->patchJson("/api/categories/{$category->id}", [
            'provider_id' => $provider->id,
            'parent_id' => $category->id,
            'name' => $category->name,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
    }

    public function test_it_soft_deletes_a_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        $this->getJson('/api/categories')->assertJsonCount(0);
    }
}
