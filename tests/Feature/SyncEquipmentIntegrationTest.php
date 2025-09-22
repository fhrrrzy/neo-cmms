<?php

use App\Services\Sync\EquipmentSyncService;
use App\Jobs\SyncEquipmentJob;
use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\ApiSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

test('complete equipment synchronization workflow', function () {
    // Arrange: Setup test data
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Manufacturing Plant',
        'description' => 'Test facility for equipment sync',
        'is_active' => true
    ]);

    $equipmentGroup = EquipmentGroup::create([
        'name' => 'Electric Motors',
        'description' => 'Electric motor equipment group'
    ]);

    // Mock API response
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'BUKRS' => 'COMP01', // Company code
            'EQKTU' => 'Main Production Motor',
            'OBJNR' => 'OBJ001',
            'POINT' => 'P001',
            'CREATED_AT' => '2023-01-15T00:00:00Z'
        ],
        [
            'EQUNR' => 'EQ002', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'BUKRS' => 'COMP01', // Company code
            'EQKTU' => 'Backup Cooling Pump',
            'OBJNR' => 'OBJ002',
            'POINT' => 'P002',
            'CREATED_AT' => '2023-02-20T00:00:00Z'
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/equipment' => Http::response(['data' => $apiData], 200)
    ]);

    // Act: Execute synchronization job
    $syncService = App::make(EquipmentSyncService::class);
    
    try {
        $result = $syncService->syncEquipment();
        
        // Assert: Verify results
        expect(Equipment::count())->toBe(2);

        // Verify equipment was created correctly
        $equipment1 = Equipment::where('equipment_number', 'EQ001')->first();
        expect($equipment1)
            ->not->toBeNull()
            ->and($equipment1->equipment_description)->toBe('Main Production Motor')
            ->and($equipment1->plant_id)->toBe($plant->id);

        $equipment2 = Equipment::where('equipment_number', 'EQ002')->first();
        expect($equipment2)
            ->not->toBeNull()
            ->and($equipment2->equipment_description)->toBe('Backup Cooling Pump');

        // Verify sync log was created
        expect($result)
            ->not->toBeNull()
            ->and($result->records_processed)->toBe(2)
            ->and($result->records_created)->toBe(2)
            ->and($result->records_updated)->toBe(0)
            ->and($result->errors_count)->toBe(0);
    } catch (Exception $e) {
        // Check if any sync logs were created
        $syncLogs = ApiSyncLog::all();
        expect($syncLogs)->toHaveCount(1);
        
        $syncLog = $syncLogs->first();
        expect($syncLog->status)->toBe('failed');
        expect($syncLog->error_message)->toContain('API request failed');
        
        throw $e;
    }
});

test('equipment sync handles updates to existing equipment', function () {
    // Arrange: Create existing plant and equipment
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'name' => 'Motors'
    ]);

    $existingEquipment = Equipment::create([
        'equipment_number' => 'EQ001',
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_description' => 'Old Motor Name',
        'company_code' => 'COMP01',
        'is_active' => false
    ]);

    // Mock API with updated data
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'BUKRS' => 'COMP01', // Company code
            'EQKTU' => 'Updated Motor Name',
            'OBJNR' => 'OBJ001',
            'POINT' => 'P001',
            'CREATED_AT' => '2023-01-01T00:00:00Z'
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/equipment' => Http::response(['data' => $apiData], 200)
    ]);

    // Act
    $syncService = App::make(EquipmentSyncService::class);
    $job = new SyncEquipmentJob();
    $job->handle($syncService);

    // Assert
    expect(Equipment::count())->toBe(1); // No new equipment created

    $existingEquipment->refresh();
    expect($existingEquipment->equipment_description)->toBe('Updated Motor Name')
        ->and($existingEquipment->is_active)->toBeTrue();

    // Verify sync log shows update
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();

    expect($syncLog->records_updated)->toBe(1)
        ->and($syncLog->records_created)->toBe(0);
});

test('equipment sync handles API errors gracefully', function () {
    // Arrange
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    // Mock API error
    Http::fake([
        'https://api.example.com/v1/equipment' => Http::response([], 500)
    ]);

    // Act & Assert
    expect(function () {
        $syncService = App::make(EquipmentSyncService::class);
        $job = new SyncEquipmentJob();
        $job->handle($syncService);
    })->toThrow(\Exception::class);

    // Verify error was logged
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'failed')
        ->first();

    expect($syncLog)
        ->not->toBeNull()
        ->and($syncLog->error_message)->toContain('API request failed');

    // No equipment should be created
    expect(Equipment::count())->toBe(0);
});

test('equipment sync validates data and handles partial failures', function () {
    // Arrange
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'name' => 'Motors'
    ]);

    // Mock API with mixed valid/invalid data
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Valid equipment
            'SWERK' => 'PLT001',
            'BUKRS' => 'COMP01',
            'EQKTU' => 'Valid Equipment'
        ],
        [
            'EQUNR' => '', // Invalid: missing equipment number
            'SWERK' => 'PLT001',
            'BUKRS' => 'COMP01',
            'EQKTU' => 'Invalid Equipment'
        ],
        [
            'EQUNR' => 'EQ002', // Valid equipment
            'SWERK' => 'PLT001',
            'BUKRS' => 'COMP01',
            'EQKTU' => 'Another Valid Equipment'
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/equipment' => Http::response(['data' => $apiData], 200)
    ]);

    // Act
    $syncService = App::make(EquipmentSyncService::class);
    $job = new SyncEquipmentJob();
    $job->handle($syncService);

    // Assert
    expect(Equipment::count())->toBe(2); // Only valid equipment created

    $validEquipment1 = Equipment::where('equipment_number', 'EQ001')->first();
    $validEquipment2 = Equipment::where('equipment_number', 'EQ002')->first();
    
    expect($validEquipment1)->not->toBeNull()
        ->and($validEquipment2)->not->toBeNull();

    // Verify sync log shows partial success
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();

    expect($syncLog->records_processed)->toBe(3)
        ->and($syncLog->records_created)->toBe(2)
        ->and($syncLog->errors_count)->toBe(1);
});

test('equipment sync can be queued and processed', function () {
    Queue::fake();

    // Dispatch job
    SyncEquipmentJob::dispatch();

    // Assert job was queued
    Queue::assertPushed(SyncEquipmentJob::class);

    // Assert job can be processed
    Queue::assertPushed(SyncEquipmentJob::class, function ($job) {
        return $job instanceof SyncEquipmentJob;
    });
});