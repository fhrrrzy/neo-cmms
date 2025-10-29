<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Plant;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EquipmentSearchApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test equipment search by equipment number
     */
    public function test_can_search_equipment_by_number(): void
    {
        $region = Region::factory()->create(['name' => 'Test Region']);
        $plant = Plant::factory()->create([
            'regional_id' => $region->id,
            'name' => 'Test Plant',
        ]);

        $equipment = Equipment::factory()->create([
            'equipment_number' => 'EQ12345',
            'equipment_description' => 'Test Equipment',
            'plant_id' => $plant->id,
        ]);

        $response = $this->getJson('/api/equipment/search?query=EQ123');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'equipment_number',
                        'equipment_description',
                        'plant',
                        'station',
                    ],
                ],
                'query',
                'limit',
                'count',
            ])
            ->assertJsonFragment([
                'equipment_number' => 'EQ12345',
            ]);
    }

    /**
     * Test equipment search by UUID
     */
    public function test_can_search_equipment_by_uuid(): void
    {
        $region = Region::factory()->create(['name' => 'Test Region']);
        $plant = Plant::factory()->create([
            'regional_id' => $region->id,
            'name' => 'Test Plant',
        ]);

        $equipment = Equipment::factory()->create([
            'uuid' => 'test-uuid-12345',
            'equipment_number' => 'EQ99999',
            'equipment_description' => 'UUID Test Equipment',
            'plant_id' => $plant->id,
        ]);

        $response = $this->getJson('/api/equipment/search?query=test-uuid');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => 'test-uuid-12345',
            ]);
    }

    /**
     * Test equipment search by description
     */
    public function test_can_search_equipment_by_description(): void
    {
        $region = Region::factory()->create(['name' => 'Test Region']);
        $plant = Plant::factory()->create([
            'regional_id' => $region->id,
            'name' => 'Test Plant',
        ]);

        $equipment = Equipment::factory()->create([
            'equipment_number' => 'EQ88888',
            'equipment_description' => 'Special Motor Equipment',
            'plant_id' => $plant->id,
        ]);

        $response = $this->getJson('/api/equipment/search?query=Special');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'equipment_description' => 'Special Motor Equipment',
            ]);
    }

    /**
     * Test equipment search requires query parameter
     */
    public function test_search_requires_query_parameter(): void
    {
        $response = $this->getJson('/api/equipment/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    /**
     * Test equipment search respects limit parameter
     */
    public function test_search_respects_limit_parameter(): void
    {
        $region = Region::factory()->create(['name' => 'Test Region']);
        $plant = Plant::factory()->create([
            'regional_id' => $region->id,
            'name' => 'Test Plant',
        ]);

        // Create 10 equipment
        for ($i = 1; $i <= 10; $i++) {
            Equipment::factory()->create([
                'equipment_number' => "TEST{$i}",
                'equipment_description' => "Test Equipment {$i}",
                'plant_id' => $plant->id,
            ]);
        }

        $response = $this->getJson('/api/equipment/search?query=TEST&limit=3');

        $response->assertStatus(200)
            ->assertJsonPath('count', 3);
    }

    /**
     * Test equipment search results are cached
     */
    public function test_search_results_are_cached(): void
    {
        Cache::flush();

        $region = Region::factory()->create(['name' => 'Test Region']);
        $plant = Plant::factory()->create([
            'regional_id' => $region->id,
            'name' => 'Test Plant',
        ]);

        $equipment = Equipment::factory()->create([
            'equipment_number' => 'CACHE123',
            'equipment_description' => 'Cache Test Equipment',
            'plant_id' => $plant->id,
        ]);

        // First request - should cache
        $response1 = $this->getJson('/api/equipment/search?query=CACHE&limit=10');
        $response1->assertStatus(200);

        // Verify cache exists
        $cacheKey = 'equipment_search:CACHE:10';
        $this->assertTrue(Cache::has($cacheKey));

        // Second request - should use cache
        $response2 = $this->getJson('/api/equipment/search?query=CACHE&limit=10');
        $response2->assertStatus(200);

        // Verify responses are identical
        $this->assertEquals(
            $response1->json('data'),
            $response2->json('data')
        );
    }

    /**
     * Test equipment search returns empty array when no matches
     */
    public function test_search_returns_empty_when_no_matches(): void
    {
        $response = $this->getJson('/api/equipment/search?query=NONEXISTENT');

        $response->assertStatus(200)
            ->assertJsonPath('count', 0)
            ->assertJsonPath('data', []);
    }
}
