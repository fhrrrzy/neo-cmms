<?php

use App\Jobs\SyncEquipmentJob;
use App\Services\Sync\EquipmentSyncService;
use App\Services\Sync\ValidationService;
use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\ApiSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    
    // Create test plant and equipment group
    $this->plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);
    
    $this->equipmentGroup = EquipmentGroup::create([
        'group_code' => 'GRP001',
        'group_name' => 'Motors'
    ]);
});

test('sync equipment job can be dispatched', function () {
    SyncEquipmentJob::dispatch();

    Queue::assertPushed(SyncEquipmentJob::class);
});

test('sync equipment job processes successfully with valid data', function () {
    $apiData = [
        [
            'plant_code' => 'PLT001',
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Motor 1',
            'equipment_type' => 'Motor',
            'group_code' => 'GRP001',
            'manufacturer' => 'Siemens',
            'model' => 'Model123',
            'installation_date' => '2023-01-01',
            'is_active' => true
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $job = new SyncEquipmentJob();
    $job->handle();

    // Verify equipment was created
    $equipment = Equipment::where('equipment_code', 'EQ001')->first();
    expect($equipment)
        ->not->toBeNull()
        ->and($equipment->equipment_name)->toBe('Motor 1');

    // Verify sync log was created
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();
    expect($syncLog)->not->toBeNull();
});

test('sync equipment job handles API failures gracefully', function () {
    Http::fake([
        'https://api.example.com/equipment' => Http::response([], 500)
    ]);

    $job = new SyncEquipmentJob();
    
    expect(fn() => $job->handle())->toThrow(Exception::class);

    // Verify error was logged
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'failed')
        ->first();
    expect($syncLog)->not->toBeNull();
});

test('sync equipment job retries on failure', function () {
    $job = new SyncEquipmentJob();
    
    expect($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([60, 300, 900]); // 1 min, 5 min, 15 min
});

test('sync equipment job has correct timeout', function () {
    $job = new SyncEquipmentJob();
    
    expect($job->timeout)->toBe(300); // 5 minutes
});

test('sync equipment job updates existing equipment', function () {
    // Create existing equipment
    $equipment = Equipment::create([
        'plant_id' => $this->plant->id,
        'equipment_group_id' => $this->equipmentGroup->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Old Motor Name',
        'equipment_type' => 'Motor'
    ]);

    $apiData = [
        [
            'plant_code' => 'PLT001',
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Updated Motor Name',
            'equipment_type' => 'Motor',
            'group_code' => 'GRP001',
            'manufacturer' => 'Siemens',
            'model' => 'Model123',
            'installation_date' => '2023-01-01',
            'is_active' => true
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $job = new SyncEquipmentJob();
    $job->handle();

    $equipment->refresh();
    expect($equipment->equipment_name)->toBe('Updated Motor Name');

    // Verify sync log shows update
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();
    expect($syncLog->records_updated)->toBe(1);
});

test('sync equipment job handles partial failures', function () {
    $apiData = [
        [
            'plant_code' => 'PLT001',
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Valid Equipment',
            'equipment_type' => 'Motor',
            'group_code' => 'GRP001'
        ],
        [
            'plant_code' => '', // Invalid - missing plant code
            'equipment_code' => 'EQ002',
            'equipment_name' => 'Invalid Equipment',
            'equipment_type' => 'Motor'
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $job = new SyncEquipmentJob();
    $job->handle();

    // Valid equipment should be created
    $validEquipment = Equipment::where('equipment_code', 'EQ001')->first();
    expect($validEquipment)->not->toBeNull();

    // Invalid equipment should not be created
    $invalidEquipment = Equipment::where('equipment_code', 'EQ002')->first();
    expect($invalidEquipment)->toBeNull();

    // Sync log should show partial success
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();
    expect($syncLog->records_created)->toBe(1)
        ->and($syncLog->errors_count)->toBe(1);
});

test('sync equipment job logs execution time', function () {
    $apiData = [
        [
            'plant_code' => 'PLT001',
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Motor 1',
            'equipment_type' => 'Motor',
            'group_code' => 'GRP001'
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $job = new SyncEquipmentJob();
    $job->handle();

    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();
    
    expect($syncLog->execution_time)
        ->not->toBeNull()
        ->and($syncLog->execution_time)->toBeGreaterThan(0);
});

test('sync equipment job can be run in queue', function () {
    Queue::fake();
    
    dispatch(new SyncEquipmentJob());
    
    Queue::assertPushed(SyncEquipmentJob::class, function ($job) {
        return $job instanceof SyncEquipmentJob;
    });
});

test('sync equipment job handles connection timeouts', function () {
    Http::fake([
        'https://api.example.com/equipment' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        }
    ]);

    $job = new SyncEquipmentJob();
    
    expect(fn() => $job->handle())->toThrow(Exception::class);

    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'failed')
        ->first();
    expect($syncLog->error_message)->toContain('Connection timeout');
});
