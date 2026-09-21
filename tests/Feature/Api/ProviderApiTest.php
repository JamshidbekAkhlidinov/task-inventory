<?php

namespace Tests\Feature\Api;

use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_providers(): void
    {
        Provider::factory()->count(2)->create();

        $response = $this->getJson('/api/providers');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_it_creates_a_provider(): void
    {
        $response = $this->postJson('/api/providers', [
            'name' => 'Acme Supplies',
            'phone' => '+998901112233',
            'email' => 'contact@acme.test',
            'address' => 'Tashkent',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('name', 'Acme Supplies');

        $this->assertDatabaseHas('providers', [
            'name' => 'Acme Supplies',
            'email' => 'contact@acme.test',
        ]);
    }

    public function test_it_requires_a_name(): void
    {
        $response = $this->postJson('/api/providers', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_it_updates_a_provider(): void
    {
        $provider = Provider::factory()->create(['name' => 'Old Name', 'is_active' => true]);

        $response = $this->patchJson("/api/providers/{$provider->id}", [
            'name' => 'New Name',
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'New Name');
        $response->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('providers', [
            'id' => $provider->id,
            'name' => 'New Name',
            'is_active' => false,
        ]);
    }

    public function test_it_soft_deletes_a_provider(): void
    {
        $provider = Provider::factory()->create();

        $response = $this->deleteJson("/api/providers/{$provider->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('providers', ['id' => $provider->id]);
        $this->assertDatabaseCount('providers', 1);

        $this->getJson('/api/providers')->assertJsonCount(0);
    }
}
