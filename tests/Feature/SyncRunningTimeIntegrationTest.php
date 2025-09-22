<?php

use App\Services\Sync\RunningTimeSyncService;
use App\Jobs\SyncRunningTimeJob;
use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\EquipmentRunningTime;
use App\Models\ApiSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('complete running time synchronization workflow', function () {
    // Arrange: Setup test data
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Manufacturing Plant'
    ]);

    $group = EquipmentGroup::create([
        'name' => 'Electric Motors'
    ]);

    $equipment1 = Equipment::create([
        'equipment_number' => 'EQ001',
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_description' => 'Motor 1',
        'company_code' => 'COMP01'
    ]);

    $equipment2 = Equipment::create([
        'equipment_number' => 'EQ002',
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_description' => 'Motor 2',
        'company_code' => 'COMP01'
    ]);

    // Mock API response with running time data
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'DATE' => '2023-09-21',
            'RECDV' => 8.5, // Running hours
            'CNTRR' => 1450.2, // Cumulative hours
            'TEMP' => 75.2, // Temperature
            'VIB' => 2.1, // Vibration
            'ENERGY' => 145.8, // Energy consumption
            'STATUS' => 'normal' // Maintenance status
        ],
        [
            'EQUNR' => 'EQ002', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'DATE' => '2023-09-21',
            'RECDV' => 7.8, // Running hours
            'CNTRR' => 1320.5, // Cumulative hours
            'TEMP' => 68.5, // Temperature
            'VIB' => 1.9, // Vibration
            'ENERGY' => 132.4, // Energy consumption
            'STATUS' => 'normal' // Maintenance status
        ],
        [
            'EQUNR' => 'EQ001', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'DATE' => '2023-09-22',
            'RECDV' => 9.2, // Running hours
            'CNTRR' => 1460.1, // Cumulative hours
            'TEMP' => 78.1, // Temperature
            'VIB' => 2.3, // Vibration
            'ENERGY' => 156.2, // Energy consumption
            'STATUS' => 'warning' // Maintenance status
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/running-time' => Http::response(['data' => $apiData], 200)
    ]);

    // Act: Execute synchronization job
    $syncService = App::make(RunningTimeSyncService::class);
    $job = new SyncRunningTimeJob();
    $job->handle($syncService);

    // Assert: Verify results
    expect(EquipmentRunningTime::count())->toBe(3);

    // Verify running time records were created correctly
    $runningTime1 = EquipmentRunningTime::where('equipment_id', $equipment1->id)
        ->where('date', '2023-09-21')
        ->first();

    expect($runningTime1)
        ->not->toBeNull()
        ->and($runningTime1->running_hours)->toBe(8.5)
        ->and($runningTime1->operating_temperature)->toBe(75.2)
        ->and($runningTime1->vibration_level)->toBe(2.1)
        ->and($runningTime1->energy_consumption)->toBe(145.8)
        ->and($runningTime1->maintenance_status)->toBe('normal')
        ->and($runningTime1->source)->toBe('api');

    $runningTime2 = EquipmentRunningTime::where('equipment_id', $equipment2->id)
        ->where('date', '2023-09-21')
        ->first();

    expect($runningTime2)
        ->not->toBeNull()
        ->and($runningTime2->running_hours)->toBe(7.8)
        ->and($runningTime2->maintenance_status)->toBe('normal');

    $runningTime3 = EquipmentRunningTime::where('equipment_id', $equipment1->id)
        ->where('date', '2023-09-22')
        ->first();

    expect($runningTime3)
        ->not->toBeNull()
        ->and($runningTime3->running_hours)->toBe(9.2)
        ->and($runningTime3->maintenance_status)->toBe('warning');

    // Verify sync log was created
    $syncLog = ApiSyncLog::where('sync_type', 'running_time')
        ->where('status', 'success')
        ->first();

    expect($syncLog)
        ->not->toBeNull()
        ->and($syncLog->records_processed)->toBe(3)
        ->and($syncLog->records_created)->toBe(3)
        ->and($syncLog->records_updated)->toBe(0)
        ->and($syncLog->errors_count)->toBe(0)
        ->and($syncLog->execution_time)->toBeGreaterThan(0);
});

test('running time sync handles updates to existing records', function () {
    // Arrange: Create existing data
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'name' => 'Motors'
    ]);

    $equipment = Equipment::create([
        'equipment_number' => 'EQ001',
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_description' => 'Test Motor',
        'company_code' => 'COMP01'
    ]);

    $existingRunningTime = EquipmentRunningTime::create([
        'equipment_id' => $equipment->id,
        'date' => '2023-09-21',
        'running_hours' => 6.0,
        'operating_temperature' => 70.0,
        'source' => 'manual'
    ]);

    // Mock API with updated data
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'DATE' => '2023-09-21',
            'RECDV' => 8.5, // Running hours
            'CNTRR' => 1450.2, // Cumulative hours
            'TEMP' => 75.2, // Temperature
            'VIB' => 2.1, // Vibration
            'ENERGY' => 145.8, // Energy consumption
            'STATUS' => 'normal' // Maintenance status
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/running-time' => Http::response(['data' => $apiData], 200)
    ]);

    // Act
    $syncService = App::make(RunningTimeSyncService::class);
    $job = new SyncRunningTimeJob();
    $job->handle($syncService);

    // Assert
    expect(EquipmentRunningTime::count())->toBe(1); // No new record created

    $existingRunningTime->refresh();
    expect($existingRunningTime->running_hours)->toBe(8.5)
        ->and($existingRunningTime->operating_temperature)->toBe(75.2)
        ->and($existingRunningTime->vibration_level)->toBe(2.1)
        ->and($existingRunningTime->energy_consumption)->toBe(145.8)
        ->and($existingRunningTime->maintenance_status)->toBe('normal')
        ->and($existingRunningTime->source)->toBe('api'); // Updated from manual to api

    // Verify sync log shows update
    $syncLog = ApiSyncLog::where('sync_type', 'running_time')
        ->where('status', 'success')
        ->first();

    expect($syncLog->records_updated)->toBe(1)
        ->and($syncLog->records_created)->toBe(0);
});

test('running time sync handles API errors gracefully', function () {
    // Arrange
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    // Mock API error
    Http::fake([
        'https://api.example.com/v1/running-time' => Http::response([], 500)
    ]);

    // Act & Assert
    expect(function () {
        $syncService = App::make(RunningTimeSyncService::class);
        $job = new SyncRunningTimeJob();
        $job->handle($syncService);
    })->toThrow(\Exception::class);

    // Verify error was logged
    $syncLog = ApiSyncLog::where('sync_type', 'running_time')
        ->where('status', 'failed')
        ->first();

    expect($syncLog)
        ->not->toBeNull()
        ->and($syncLog->error_message)->toContain('API request failed');

    // No running time records should be created
    expect(EquipmentRunningTime::count())->toBe(0);
});

test('running time sync validates data and handles partial failures', function () {
    // Arrange
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'name' => 'Motors'
    ]);

    $equipment = Equipment::create([
        'equipment_number' => 'EQ001',
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_description' => 'Test Motor',
        'company_code' => 'COMP01'
    ]);

    // Mock API with mixed valid/invalid data
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Valid record
            'SWERK' => 'PLT001',
            'DATE' => '2023-09-21',
            'RECDV' => 8.5,
            'CNTRR' => 1450.2
        ],
        [
            'EQUNR' => '', // Invalid: missing equipment number
            'SWERK' => 'PLT001',
            'DATE' => '2023-09-22',
            'RECDV' => 7.2,
            'CNTRR' => 1320.5
        ],
        [
            'EQUNR' => 'EQ002', // Invalid: equipment doesn't exist
            'SWERK' => 'PLT001',
            'DATE' => '2023-09-23',
            'RECDV' => 6.8,
            'CNTRR' => 1280.3
        ],
        [
            'EQUNR' => 'EQ001', // Valid record
            'SWERK' => 'PLT001',
            'DATE' => '2023-09-24',
            'RECDV' => -5.0, // Invalid: negative running hours
            'CNTRR' => 1460.1
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/running-time' => Http::response(['data' => $apiData], 200)
    ]);

    // Act
    $syncService = App::make(RunningTimeSyncService::class);
    $job = new SyncRunningTimeJob();
    $job->handle($syncService);

    // Assert
    expect(EquipmentRunningTime::count())->toBe(1); // Only valid record created

    $validRecord = EquipmentRunningTime::where('date', '2023-09-21')->first();
    expect($validRecord)
        ->not->toBeNull()
        ->and($validRecord->running_hours)->toBe(8.5)
        ->and($validRecord->equipment_id)->toBe($equipment->id);

    // Invalid records should not exist
    expect(EquipmentRunningTime::where('date', '2023-09-22')->first())->toBeNull()
        ->and(EquipmentRunningTime::where('date', '2023-09-23')->first())->toBeNull()
        ->and(EquipmentRunningTime::where('date', '2023-09-24')->first())->toBeNull();

    // Verify sync log shows partial success
    $syncLog = ApiSyncLog::where('sync_type', 'running_time')
        ->where('status', 'success')
        ->first();

    expect($syncLog->records_processed)->toBe(4)
        ->and($syncLog->records_created)->toBe(1)
        ->and($syncLog->errors_count)->toBe(3);
});

test('running time sync preserves data integrity', function () {
    // Arrange
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'name' => 'Motors'
    ]);

    $equipment = Equipment::create([
        'equipment_number' => 'EQ001',
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_description' => 'Test Motor',
        'company_code' => 'COMP01'
    ]);

    // Create existing manual entry
    $manualEntry = EquipmentRunningTime::create([
        'equipment_id' => $equipment->id,
        'date' => '2023-09-21',
        'running_hours' => 6.0,
        'source' => 'manual',
        'notes' => 'Manually recorded by operator'
    ]);

    // Mock API with different data for same date
    $apiData = [
        [
            'EQUNR' => 'EQ001', // Equipment number
            'SWERK' => 'PLT001', // Plant code
            'DATE' => '2023-09-21',
            'RECDV' => 8.5, // Running hours
            'CNTRR' => 1450.2, // Cumulative hours
            'TEMP' => 75.2 // Temperature
        ]
    ];

    Http::fake([
        'https://api.example.com/v1/running-time' => Http::response(['data' => $apiData], 200)
    ]);

    // Act
    $syncService = App::make(RunningTimeSyncService::class);
    $job = new SyncRunningTimeJob();
    $job->handle($syncService);

    // Assert: Manual entry should be updated but preserve manual notes
    expect(EquipmentRunningTime::count())->toBe(1);

    $manualEntry->refresh();
    expect($manualEntry->running_hours)->toBe(8.5) // Updated from API
        ->and($manualEntry->operating_temperature)->toBe(75.2) // Added from API
        ->and($manualEntry->source)->toBe('api') // Updated to api
        ->and($manualEntry->notes)->toBe('Manually recorded by operator'); // Preserved
});

test('running time sync can be queued and processed', function () {
    Queue::fake();

    // Dispatch job
    SyncRunningTimeJob::dispatch();

    // Assert job was queued
    Queue::assertPushed(SyncRunningTimeJob::class);

    // Assert job can be processed
    Queue::assertPushed(SyncRunningTimeJob::class, function ($job) {
        return $job instanceof SyncRunningTimeJob;
    });
});