<?php

use App\Services\Sync\EquipmentSyncService;
use App\Services\Sync\ValidationService;
use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\ApiSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->validationService = new ValidationService();
    $this->syncService = new EquipmentSyncService($this->validationService);
    
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

test('sync equipment creates new equipment from API data', function () {
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

    $result = $this->syncService->syncEquipment();

    expect($result['success'])->toBeTrue()
        ->and($result['created'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'])->toBe(0);

    $equipment = Equipment::where('equipment_code', 'EQ001')->first();
    expect($equipment)
        ->not->toBeNull()
        ->and($equipment->equipment_name)->toBe('Motor 1')
        ->and($equipment->plant_id)->toBe($this->plant->id);
});

test('sync equipment updates existing equipment', function () {
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

    $result = $this->syncService->syncEquipment();

    expect($result['success'])->toBeTrue()
        ->and($result['created'])->toBe(0)
        ->and($result['updated'])->toBe(1)
        ->and($result['errors'])->toBe(0);

    $equipment->refresh();
    expect($equipment->equipment_name)->toBe('Updated Motor Name');
});

test('sync equipment handles API errors gracefully', function () {
    Http::fake([
        'https://api.example.com/equipment' => Http::response([], 500)
    ]);

    $result = $this->syncService->syncEquipment();

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('API request failed');

    // Check that sync log was created
    $syncLog = ApiSyncLog::where('sync_type', 'equipment')->first();
    expect($syncLog)
        ->not->toBeNull()
        ->and($syncLog->status)->toBe('failed');
});

test('sync equipment validates data before processing', function () {
    $invalidApiData = [
        [
            'plant_code' => '', // Missing plant code
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Motor 1'
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($invalidApiData, 200)
    ]);

    $result = $this->syncService->syncEquipment();

    expect($result['success'])->toBeTrue()
        ->and($result['created'])->toBe(0)
        ->and($result['errors'])->toBe(1);
});

test('sync equipment skips equipment for non-existent plants', function () {
    $apiData = [
        [
            'plant_code' => 'NONEXISTENT',
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Motor 1',
            'equipment_type' => 'Motor'
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $result = $this->syncService->syncEquipment();

    expect($result['success'])->toBeTrue()
        ->and($result['created'])->toBe(0)
        ->and($result['errors'])->toBe(1);

    $equipment = Equipment::where('equipment_code', 'EQ001')->first();
    expect($equipment)->toBeNull();
});

test('sync equipment creates equipment group if not exists', function () {
    $apiData = [
        [
            'plant_code' => 'PLT001',
            'equipment_code' => 'EQ001',
            'equipment_name' => 'Motor 1',
            'equipment_type' => 'Motor',
            'group_code' => 'NEWGRP',
            'group_name' => 'New Group'
        ]
    ];

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $result = $this->syncService->syncEquipment();

    expect($result['success'])->toBeTrue()
        ->and($result['created'])->toBe(1);

    $newGroup = EquipmentGroup::where('group_code', 'NEWGRP')->first();
    expect($newGroup)
        ->not->toBeNull()
        ->and($newGroup->group_name)->toBe('New Group');

    $equipment = Equipment::where('equipment_code', 'EQ001')->first();
    expect($equipment->equipment_group_id)->toBe($newGroup->id);
});

test('sync equipment logs successful operations', function () {
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

    $this->syncService->syncEquipment();

    $syncLog = ApiSyncLog::where('sync_type', 'equipment')
        ->where('status', 'success')
        ->first();

    expect($syncLog)
        ->not->toBeNull()
        ->and($syncLog->records_processed)->toBe(1)
        ->and($syncLog->records_created)->toBe(1)
        ->and($syncLog->records_updated)->toBe(0)
        ->and($syncLog->errors_count)->toBe(0);
});

test('sync equipment handles large datasets efficiently', function () {
    $apiData = [];
    for ($i = 1; $i <= 100; $i++) {
        $apiData[] = [
            'plant_code' => 'PLT001',
            'equipment_code' => 'EQ' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'equipment_name' => 'Equipment ' . $i,
            'equipment_type' => 'Motor',
            'group_code' => 'GRP001'
        ];
    }

    Http::fake([
        'https://api.example.com/equipment' => Http::response($apiData, 200)
    ]);

    $startTime = microtime(true);
    $result = $this->syncService->syncEquipment();
    $endTime = microtime(true);

    expect($result['success'])->toBeTrue()
        ->and($result['created'])->toBe(100)
        ->and($endTime - $startTime)->toBeLessThan(5.0); // Should complete within 5 seconds

    expect(Equipment::count())->toBe(100);
});
