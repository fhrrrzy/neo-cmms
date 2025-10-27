<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\Station;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use RuntimeException;

class EquipmentProcessor
{
    private const BATCH_SIZE = 1000; // Reduced from 2000 to avoid memory issues

    /**
     * Process a single equipment item (legacy method for backward compatibility)
     */
    public function process(array $item): void
    {
        $this->processBatch([$item]);
    }

    /**
     * Process equipment items in batches for optimal performance
     */
    public function processBatch(array $items): void
    {
        if (empty($items)) {
            return;
        }

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($items, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $this->processChunk($chunk);
        }
    }

    /**
     * Process a chunk of equipment items
     */
    private function processChunk(array $chunk): void
    {
        DB::transaction(function () use ($chunk) {
            // Pre-load all related data in bulk
            $lookupData = $this->preloadLookupData($chunk);

            // Prepare equipment data for bulk upsert
            $equipmentData = [];
            $equipmentGroupsToCreate = [];

            foreach ($chunk as $item) {
                $equipmentData[] = $this->prepareEquipmentData($item, $lookupData);

                // Collect equipment groups that need to be created
                $groupName = trim((string) (Arr::get($item, 'group_name') ?? Arr::get($item, 'equipment_group')));
                if ($groupName !== '' && !isset($lookupData['equipment_groups'][$groupName])) {
                    $equipmentGroupsToCreate[$groupName] = [
                        'name' => $groupName,
                        'description' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk create equipment groups
            if (!empty($equipmentGroupsToCreate)) {
                EquipmentGroup::insert(array_values($equipmentGroupsToCreate));

                // Reload equipment groups for the current chunk
                $lookupData['equipment_groups'] = EquipmentGroup::whereIn('name', array_keys($equipmentGroupsToCreate))
                    ->get()
                    ->keyBy('name')
                    ->merge($lookupData['equipment_groups']);
            }

            // Update equipment data with newly created group IDs
            foreach ($equipmentData as &$data) {
                $groupName = trim((string) (Arr::get($data['source_item'], 'group_name') ?? Arr::get($data['source_item'], 'equipment_group')));
                if ($groupName !== '' && isset($lookupData['equipment_groups'][$groupName])) {
                    $data['equipment_group_id'] = $lookupData['equipment_groups'][$groupName]->id;
                }
            }

            // Bulk upsert equipment
            $this->bulkUpsertEquipment($equipmentData);
        });
    }

    /**
     * Pre-load all lookup data needed for the chunk
     */
    private function preloadLookupData(array $chunk): array
    {
        // Extract unique plant codes
        $plantCodes = collect($chunk)
            ->map(function ($item) {
                return Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Extract unique cost centers
        $costCenters = collect($chunk)
            ->map(function ($item) {
                return Arr::get($item, 'cost_center') ?? Arr::get($item, 'KOSTL');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Extract unique equipment group names
        $groupNames = collect($chunk)
            ->map(function ($item) {
                return trim((string) (Arr::get($item, 'group_name') ?? Arr::get($item, 'equipment_group')));
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Bulk load plants
        $plants = Plant::whereIn('plant_code', $plantCodes)->get()->keyBy('plant_code');

        // Bulk load stations
        $stations = Station::whereIn('plant_id', $plants->pluck('id'))
            ->whereIn('cost_center', $costCenters)
            ->get()
            ->groupBy('plant_id')
            ->map(function ($plantStations) {
                return $plantStations->keyBy('cost_center');
            });

        // Bulk load existing equipment groups
        $equipmentGroups = EquipmentGroup::whereIn('name', $groupNames)->get()->keyBy('name');

        return [
            'plants' => $plants,
            'stations' => $stations,
            'equipment_groups' => $equipmentGroups,
        ];
    }

    /**
     * Prepare equipment data for bulk upsert
     */
    private function prepareEquipmentData(array $item, array $lookupData): array
    {
        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code') ?? Arr::get($item, 'SWERK');
        $plant = $lookupData['plants'][$plantCode] ?? null;

        if (!$plant) {
            throw new RuntimeException('Plant not found: ' . (string) $plantCode);
        }

        $equipmentNumber = Arr::get($item, 'equipment_number') ?? Arr::get($item, 'EQUNR');
        $kostl = Arr::get($item, 'cost_center') ?? Arr::get($item, 'KOSTL');

        $station = null;
        if ($kostl && isset($lookupData['stations'][$plant->id][$kostl])) {
            $station = $lookupData['stations'][$plant->id][$kostl];
        }

        $groupName = trim((string) (Arr::get($item, 'group_name') ?? Arr::get($item, 'equipment_group')));
        $equipmentGroup = $lookupData['equipment_groups'][$groupName] ?? null;

        $apiCreatedAt = null;
        $ts = Arr::get($item, 'api_created_at') ?? Arr::get($item, 'CREATED_AT');
        if ($ts) {
            try {
                $apiCreatedAt = Carbon::parse($ts);
            } catch (\Exception $e) {
                // Skip invalid dates
            }
        }

        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'equipment_number' => $equipmentNumber,
            'plant_id' => $plant->id,
            'station_id' => $station?->id,
            'equipment_group_id' => $equipmentGroup?->id,
            'company_code' => Arr::get($item, 'company_code') ?? Arr::get($item, 'BUKRS'),
            'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'description') ?? Arr::get($item, 'EQKTU'),
            'object_number' => Arr::get($item, 'object_number') ?? Arr::get($item, 'OBJNR'),
            'point' => Arr::get($item, 'point') ?? Arr::get($item, 'POINT'),
            'api_created_at' => $apiCreatedAt,
            'mandt' => Arr::get($item, 'MANDT'),
            'baujj' => Arr::get($item, 'BAUJJ'),
            'groes' => Arr::get($item, 'GROES'),
            'herst' => Arr::get($item, 'HERST'),
            'mrnug' => Arr::get($item, 'MRNGU'),
            'eqtyp' => Arr::get($item, 'EQTYP'),
            'eqart' => Arr::get($item, 'EQART'),
            'maintenance_planner_group' => Arr::get($item, 'MAINTAINANCE_PLANNER_GROUP'),
            'maintenance_work_center' => Arr::get($item, 'MAINTAINANCE_WORK_CENTER'),
            'functional_location' => Arr::get($item, 'FUNCTIONAL_LOCATION'),
            'description_func_location' => Arr::get($item, 'DESCRIPTION_FUNC_LOCATION'),
            'created_at' => now(),
            'updated_at' => now(),
            'source_item' => $item, // Store for later reference
        ];
    }

    /**
     * Bulk upsert equipment using Eloquent for better performance and safety
     */
    private function bulkUpsertEquipment(array $equipmentData): void
    {
        if (empty($equipmentData)) {
            return;
        }

        // Clean up the data array to remove source_item
        $cleanData = array_map(function ($data) {
            unset($data['source_item']);
            return $data;
        }, $equipmentData);

        // Chunk data to avoid "too many placeholders" error and reduce memory usage
        // With ~20 columns, 500 rows = 10,000 placeholders (well under 65,535 limit)
        $chunks = array_chunk($cleanData, 500);

        foreach ($chunks as $chunk) {
            Equipment::upsert(
                $chunk,
                ['equipment_number'], // unique key
                [
                    'plant_id',
                    'station_id',
                    'equipment_group_id',
                    'company_code',
                    'equipment_description',
                    'object_number',
                    'point',
                    'api_created_at',
                    'mandt',
                    'baujj',
                    'groes',
                    'herst',
                    'mrnug',
                    'eqtyp',
                    'eqart',
                    'maintenance_planner_group',
                    'maintenance_work_center',
                    'functional_location',
                    'description_func_location',
                    'updated_at'
                ]
            );

            // Free memory after each chunk
            unset($chunk);
        }
    }
}
