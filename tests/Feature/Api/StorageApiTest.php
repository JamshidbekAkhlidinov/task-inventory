<?php

namespace Tests\Feature\Api;

use App\Models\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_storages(): void
    {
        Storage::factory()->count(2)->create();

        $response = $this->getJson('/api/storages');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_it_creates_a_storage(): void
    {
        $response = $this->postJson('/api/storages', [
            'name' => 'North Warehouse',
            'address' => 'Bukhara',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('name', 'North Warehouse');

        $this->assertDatabaseHas('storages', [
            'name' => 'North Warehouse',
        ]);
    }

    public function test_it_requires_a_name(): void
    {
        $response = $this->postJson('/api/storages', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_it_updates_a_storage(): void
    {
        $storage = Storage::factory()->create(['name' => 'Old Name', 'is_active' => true]);

        $response = $this->patchJson("/api/storages/{$storage->id}", [
            'name' => 'New Name',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'New Name');
        $response->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('storages', [
            'id' => $storage->id,
            'name' => 'New Name',
            'is_active' => false,
        ]);
    }

    public function test_it_soft_deletes_a_storage(): void
    {
        $storage = Storage::factory()->create();

        $response = $this->deleteJson("/api/storages/{$storage->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('storages', ['id' => $storage->id]);

        $this->getJson('/api/storages')->assertJsonCount(0);
    }
}
