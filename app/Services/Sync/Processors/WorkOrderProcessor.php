<?php

namespace App\Services\Sync\Processors;

use App\Models\Equipment;
use App\Models\Plant;
use App\Models\Station;
use App\Models\WorkOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkOrderProcessor
{
    private const BATCH_SIZE = 2000;

    /**
     * Process a single work order item (legacy method for backward compatibility)
     */
    public function process(array $item): void
    {
        $this->processBatch([$item]);
    }

    /**
     * Process work order items in batches for optimal performance
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
     * Process a chunk of work order items
     */
    private function processChunk(array $chunk): void
    {
        DB::transaction(function () use ($chunk) {
            // Pre-load all related data in bulk
            $lookupData = $this->preloadLookupData($chunk);

            // Prepare work order data for bulk upsert
            $workOrderData = [];

            foreach ($chunk as $item) {
                $workOrderData[] = $this->prepareWorkOrderData($item, $lookupData);
            }

            // Bulk upsert work orders
            $this->bulkUpsertWorkOrders($workOrderData);
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
                return Arr::get($item, 'plant');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Extract unique cost centers
        $costCenters = collect($chunk)
            ->map(function ($item) {
                return Arr::get($item, 'cost_center');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Extract unique equipment numbers
        $equipmentNumbers = collect($chunk)
            ->map(function ($item) {
                return Arr::get($item, 'equipment_number');
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Bulk load plants
        $plants = Plant::whereIn('plant_code', $plantCodes)->get()->keyBy('plant_code');

        // Bulk load valid equipment numbers
        $validEquipment = Equipment::whereIn('equipment_number', $equipmentNumbers)
            ->pluck('equipment_number')
            ->flip()
            ->toArray();

        // Bulk load stations
        $stations = Station::whereIn('plant_id', $plants->pluck('id'))
            ->whereIn('cost_center', $costCenters)
            ->get()
            ->groupBy('plant_id')
            ->map(function ($plantStations) {
                return $plantStations->keyBy('cost_center');
            });

        return [
            'plants' => $plants,
            'stations' => $stations,
            'validEquipment' => $validEquipment,
        ];
    }

    /**
     * Prepare work order data for bulk upsert
     */
    private function prepareWorkOrderData(array $item, array $lookupData): array
    {
        $plantCode = Arr::get($item, 'plant');
        $plant = $lookupData['plants'][$plantCode] ?? null;

        $stationId = null;
        $woCostCenter = Arr::get($item, 'cost_center');
        if ($plant && $woCostCenter && isset($lookupData['stations'][$plant->id][$woCostCenter])) {
            $stationId = $lookupData['stations'][$plant->id][$woCostCenter]->id;
        }

        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'order' => Arr::get($item, 'order'),
            'mandt' => Arr::get($item, 'mandt'),
            'order_type' => Arr::get($item, 'order_type'),
            'created_on' => $this->parseDate(Arr::get($item, 'created_on')),
            'change_date_for_order_master' => $this->parseDate(Arr::get($item, 'change_date_for_order_master')),
            'description' => Arr::get($item, 'description'),
            'company_code' => Arr::get($item, 'company_code'),
            'plant_id' => $plant?->id,
            'plant_code' => $plantCode,
            'station_id' => $stationId,
            'responsible_cctr' => Arr::get($item, 'responsible_cctr'),
            'order_status' => Arr::get($item, 'order_status'),
            'technical_completion' => $this->parseDate(Arr::get($item, 'technical_completion')),
            'cost_center' => Arr::get($item, 'cost_center'),
            'profit_center' => Arr::get($item, 'profit_center'),
            'object_class' => Arr::get($item, 'object_class'),
            'main_work_center' => Arr::get($item, 'main_work_center'),
            'notification' => Arr::get($item, 'notification'),
            'cause' => Arr::get($item, 'cause'),
            'cause_text' => Arr::get($item, 'cause_text'),
            'code_group_problem' => Arr::get($item, 'code_group_problem'),
            'item_text' => Arr::get($item, 'item_text'),
            'created' => $this->parseDate(Arr::get($item, 'created')),
            'released' => $this->parseDate(Arr::get($item, 'released')),
            'completed' => Arr::get($item, 'completed'),
            'closed' => $this->parseDate(Arr::get($item, 'closed')),
            'planned_release' => $this->parseDate(Arr::get($item, 'planned_release')),
            'planned_completion' => $this->parseDate(Arr::get($item, 'planned_completion')),
            'planned_closing_date' => $this->parseDate(Arr::get($item, 'planned_closing_date')),
            'release' => $this->parseDate(Arr::get($item, 'release')),
            'close' => $this->parseDate(Arr::get($item, 'close')),
            'api_updated_at' => $this->parseDate(Arr::get($item, 'updated_at')),
            'equipment_number' => $this->getValidEquipmentNumber($item, $lookupData),
            'functional_location' => Arr::get($item, 'functional_location'),
            'functional_location_description' => Arr::get($item, 'functional_location_description'),
            'opertn_task_list_no' => Arr::get($item, 'opertn_task_list_no'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Get valid equipment number (only if equipment exists)
     */
    private function getValidEquipmentNumber(array $item, array $lookupData): ?string
    {
        $equipmentNumber = Arr::get($item, 'equipment_number');

        if (!$equipmentNumber) {
            return null;
        }

        // Only return equipment number if it exists in the equipment table
        return isset($lookupData['validEquipment'][$equipmentNumber])
            ? $equipmentNumber
            : null;
    }

    /**
     * Parse date string safely
     */
    private function parseDate($dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Bulk upsert work orders using Eloquent for better performance and safety
     */
    private function bulkUpsertWorkOrders(array $workOrderData): void
    {
        if (empty($workOrderData)) {
            return;
        }

        // Chunk data to avoid "too many placeholders" error
        // With ~35 columns, 1000 rows = 35,000 placeholders (well under 65,535 limit)
        $chunks = array_chunk($workOrderData, 1000);

        foreach ($chunks as $chunk) {
            WorkOrder::upsert(
                $chunk,
                ['order'], // unique key
                [
                    'mandt',
                    'order_type',
                    'created_on',
                    'change_date_for_order_master',
                    'description',
                    'company_code',
                    'plant_id',
                    'plant_code',
                    'station_id',
                    'responsible_cctr',
                    'order_status',
                    'technical_completion',
                    'cost_center',
                    'profit_center',
                    'object_class',
                    'main_work_center',
                    'notification',
                    'cause',
                    'cause_text',
                    'code_group_problem',
                    'item_text',
                    'created',
                    'released',
                    'completed',
                    'closed',
                    'planned_release',
                    'planned_completion',
                    'planned_closing_date',
                    'release',
                    'close',
                    'api_updated_at',
                    'equipment_number',
                    'functional_location',
                    'functional_location_description',
                    'opertn_task_list_no',
                    'updated_at'
                ]
            );
        }
    }
}
