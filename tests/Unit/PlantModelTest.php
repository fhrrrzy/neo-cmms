<?php

use App\Models\Plant;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

uses(RefreshDatabase::class);

test('plant can be created with valid attributes', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Main Manufacturing Plant',
        'description' => 'Primary manufacturing facility',
        'location' => 'Jakarta, Indonesia',
        'is_active' => true
    ]);

    expect($plant)
        ->toBeInstanceOf(Plant::class)
        ->and($plant->plant_code)->toBe('PLT001')
        ->and($plant->plant_name)->toBe('Main Manufacturing Plant')
        ->and($plant->is_active)->toBeTrue();
});

test('plant requires plant_code and plant_name', function () {
    expect(fn() => Plant::create([]))
        ->toThrow(Exception::class);
});

test('plant_code must be unique', function () {
    Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Plant 1'
    ]);

    expect(fn() => Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Plant 2'
    ]))->toThrow(Exception::class);
});

test('plant can have multiple equipment', function () {
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

    Equipment::create([
        'plant_id' => $plant->id,
        'equipment_code' => 'EQ002',
        'equipment_name' => 'Equipment 2',
        'equipment_type' => 'Pump'
    ]);

    $plant->refresh();
    
    expect($plant->equipment)
        ->toBeInstanceOf(Collection::class)
        ->and($plant->equipment)->toHaveCount(2);
});

test('plant scope active returns only active plants', function () {
    Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Active Plant',
        'is_active' => true
    ]);

    Plant::create([
        'plant_code' => 'PLT002',
        'plant_name' => 'Inactive Plant',
        'is_active' => false
    ]);

    $activePlants = Plant::active()->get();

    expect($activePlants)
        ->toHaveCount(1)
        ->and($activePlants->first()->plant_code)->toBe('PLT001');
});

test('plant can be found by code', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant'
    ]);

    $foundPlant = Plant::byCode('PLT001')->first();

    expect($foundPlant)
        ->not->toBeNull()
        ->and($foundPlant->id)->toBe($plant->id);
});

test('plant casts is_active to boolean', function () {
    $plant = Plant::create([
        'plant_code' => 'PLT001',
        'plant_name' => 'Test Plant',
        'is_active' => '1'
    ]);

    expect($plant->is_active)->toBeTrue();

    $plant->is_active = '0';
    $plant->save();
    $plant->refresh();

    expect($plant->is_active)->toBeFalse();
});
