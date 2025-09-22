<?php

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\EquipmentRunningTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('equipment can be created with valid attributes', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'group_code' => 'GRP001',
        'group_name' => 'Motors'
    ]);

    $equipment = Equipment::create([
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Motor 1',
        'equipment_type' => 'Motor',
        'manufacturer' => 'Siemens',
        'model' => 'Model123',
        'installation_date' => '2023-01-01',
        'is_active' => true
    ]);

    expect($equipment)
        ->toBeInstanceOf(Equipment::class)
        ->and($equipment->equipment_code)->toBe('EQ001')
        ->and($equipment->equipment_name)->toBe('Motor 1')
        ->and($equipment->is_active)->toBeTrue();
});

test('equipment belongs to plant', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    $equipment = Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Equipment 1',
        'equipment_type' => 'Motor'
    ]);

    expect($equipment->plant)
        ->toBeInstanceOf(Plant::class)
        ->and($equipment->plant->id)->toBe($plant->id);
});

test('equipment belongs to equipment group', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    $group = EquipmentGroup::create([
        'group_code' => 'GRP001',
        'group_name' => 'Motors'
    ]);

    $equipment = Equipment::create([
        'plant_id' => $plant->id,
        'equipment_group_id' => $group->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Equipment 1',
        'equipment_type' => 'Motor'
    ]);

    expect($equipment->equipmentGroup)
        ->toBeInstanceOf(EquipmentGroup::class)
        ->and($equipment->equipmentGroup->id)->toBe($group->id);
});

test('equipment can have multiple running times', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    $equipment = Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Equipment 1',
        'equipment_type' => 'Motor'
    ]);

    EquipmentRunningTime::create([
        'equipment_id' => $equipment->id,
        'recorded_date' => '2023-01-01',
        'running_hours' => 8.5,
        'source' => 'manual'
    ]);

    EquipmentRunningTime::create([
        'equipment_id' => $equipment->id,
        'recorded_date' => '2023-01-02',
        'running_hours' => 7.2,
        'source' => 'api'
    ]);

    $equipment->refresh();
    
    expect($equipment->runningTimes)
        ->toBeInstanceOf(Collection::class)
        ->and($equipment->runningTimes)->toHaveCount(2);
});

test('equipment scope active returns only active equipment', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Active Equipment',
        'equipment_type' => 'Motor',
        'is_active' => true
    ]);

    Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ002',
        'equipment_name' => 'Inactive Equipment',
        'equipment_type' => 'Pump',
        'is_active' => false
    ]);

    $activeEquipment = Equipment::active()->get();

    expect($activeEquipment)
        ->toHaveCount(1)
        ->and($activeEquipment->first()->equipment_code)->toBe('EQ001');
});

test('equipment can be found by code', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    $equipment = Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Test Equipment',
        'equipment_type' => 'Motor'
    ]);

    $foundEquipment = Equipment::byCode('EQ001')->first();

    expect($foundEquipment)
        ->not->toBeNull()
        ->and($foundEquipment->id)->toBe($equipment->id);
});

test('equipment can be filtered by plant', function () {
    $plant1 = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Plant 1'
    ]);

    $plant2 = Plant::create([
        'plant_code' => 'PLT002',
        'plant_name' => 'Plant 2'
    ]);

    Equipment::create([
        'plant_id' => $plant1->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Equipment 1',
        'equipment_type' => 'Motor'
    ]);

    Equipment::create([
        'plant_id' => $plant2->id,
        'equipment_code' => 'EQ002',
        'equipment_name' => 'Equipment 2',
        'equipment_type' => 'Pump'
    ]);

    $plant1Equipment = Equipment::byPlant($plant1->id)->get();

    expect($plant1Equipment)
        ->toHaveCount(1)
        ->and($plant1Equipment->first()->equipment_code)->toBe('EQ001');
});

test('equipment requires plant_id and equipment_code', function () {
    expect(fn() => Equipment::create([]))
        ->toThrow(Exception::class);
});

test('equipment_code must be unique per plant', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Equipment 1',
        'equipment_type' => 'Motor'
    ]);

    expect(fn() => Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ001',
        'equipment_name' => 'Equipment 2',
        'equipment_type' => 'Pump'
    ]))->toThrow(Exception::class);
});
