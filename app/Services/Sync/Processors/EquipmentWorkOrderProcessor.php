<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\EquipmentWorkOrder;
use App\Models\WorkOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EquipmentWorkOrderProcessor
{
    private const BATCH_SIZE = 2000;

    /**
     * Process a single equipment work order item
     */
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        $this->processBatch([$item], $allowedPlantCodes);
    }

    /**
     * Process equipment work order items in batches
     */
    public function processBatch(array $items, array $allowedPlantCodes = []): void
    {
        if (empty($items)) {
            return;
        }

        $chunks = array_chunk($items, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $this->processChunk($chunk, $allowedPlantCodes);
        }
    }

    /**
     * Process a chunk of equipment work order items
     */
    private function processChunk(array $chunk, array $allowedPlantCodes = []): void
    {
        DB::transaction(function () use ($chunk, $allowedPlantCodes) {
            $lookupData = $this->preloadLookupData($chunk, $allowedPlantCodes);
            $equipmentWorkOrderData = [];

            foreach ($chunk as $item) {
                $plantCode = Arr::get($item, 'plant');
                if (!$plantCode || (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true))) {
                    continue;
                }

                $plant = $lookupData['plants'][$plantCode] ?? null;
                if (!$plant) {
                    Log::warning('Skipping equipment_work_order item due to unknown plant code', [
                        'plant_code' => $plantCode,
                        'item_id' => Arr::get($item, 'id') ?? Arr::get($item, 'reservation'),
                    ]);
                    continue;
                }

                $equipmentWorkOrderData[] = $this->prepareEquipmentWorkOrderData($item, $plant, $lookupData);
            }

            $this->bulkUpsertEquipmentWorkOrders($equipmentWorkOrderData);
        });
    }

    /**
     * Pre-load all lookup data needed for the chunk
     */
    private function preloadLookupData(array $chunk, array $allowedPlantCodes = []): array
    {
        $plantCodes = collect($chunk)
            ->map(fn($item) => Arr::get($item, 'plant'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (!empty($allowedPlantCodes)) {
            $plantCodes = array_intersect($plantCodes, $allowedPlantCodes);
        }

        $plants = Plant::whereIn('plant_code', $plantCodes)->get()->keyBy('plant_code');

        // Load work orders to get equipment numbers
        $orderNumbers = collect($chunk)
            ->map(fn($item) => Arr::get($item, 'order_number'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $workOrders = WorkOrder::whereIn('order', $orderNumbers)
            ->get()
            ->keyBy('order');

        return [
            'plants' => $plants,
            'work_orders' => $workOrders,
        ];
    }

    /**
     * Prepare equipment work order data for bulk upsert
     */
    private function prepareEquipmentWorkOrderData(array $item, Plant $plant, array $lookupData): array
    {
        $orderNumber = Arr::get($item, 'order_number');
        $workOrder = $orderNumber && isset($lookupData['work_orders'][$orderNumber])
            ? $lookupData['work_orders'][$orderNumber]
            : null;

        return [
            'plant_id' => $plant->id,
            'order_number' => $orderNumber,
            'equipment_number' => Arr::get($item, 'equipment_number') ?? $workOrder?->equipment_number,
            'functional_location' => Arr::get($item, 'functional_location'),
            'functional_location_description' => Arr::get($item, 'functional_location_description'),
            'reservation' => Arr::get($item, 'reservation'),
            'requirement_type' => Arr::get($item, 'requirement_type'),
            'reservation_status' => Arr::get($item, 'reservation_status'),
            'item_deleted' => Arr::get($item, 'item_deleted'),
            'movement_allowed' => Arr::get($item, 'movement_allowed'),
            'final_issue' => Arr::get($item, 'final_issue'),
            'missing_part' => Arr::get($item, 'missing_part'),
            'material' => Arr::get($item, 'material'),
            'material_description' => Arr::get($item, 'material_description') ?? Arr::get($item, 'material_text'),
            'storage_location' => Arr::get($item, 'storage_location'),
            'requirements_date' => Arr::get($item, 'requirements_date'),
            'requirement_quantity' => $this->toDecimal(Arr::get($item, 'requirement_quantity')),
            'base_unit_of_measure' => Arr::get($item, 'base_unit_of_measure'),
            'debit_credit_ind' => Arr::get($item, 'debit_credit_ind'),
            'quantity_is_fixed' => Arr::get($item, 'quantity_is_fixed'),
            'quantity_withdrawn' => $this->toDecimal(Arr::get($item, 'quantity_withdrawn')),
            'value_withdrawn' => $this->toDecimal(Arr::get($item, 'value_withdrawn')),
            'currency' => Arr::get($item, 'currency'),
            'qty_in_unit_of_entry' => $this->toDecimal(Arr::get($item, 'qty_in_unit_of_entry')),
            'unit_of_entry' => Arr::get($item, 'unit_of_entry'),
            'movement_type' => Arr::get($item, 'movement_type'),
            'gl_account' => Arr::get($item, 'gl_account'),
            'receiving_plant' => Arr::get($item, 'receiving_plant'),
            'receiving_storage_location' => Arr::get($item, 'receiving_storage_location'),
            'qty_for_avail_check' => $this->toDecimal(Arr::get($item, 'qty_for_avail_check')),
            'goods_recipient' => Arr::get($item, 'goods_recipient'),
            'material_group' => Arr::get($item, 'material_group'),
            'acct_manually' => Arr::get($item, 'acct_manually'),
            'commitment_item_1' => Arr::get($item, 'commitment_item_1'),
            'commitment_item_2' => Arr::get($item, 'commitment_item_2'),
            'funds_center' => Arr::get($item, 'funds_center'),
            'start_time' => Arr::get($item, 'start_time'),
            'end_time' => Arr::get($item, 'end_time'),
            'service_duration' => Arr::get($item, 'service_duration'),
            'service_dur_unit' => Arr::get($item, 'service_dur_unit'),
            'api_updated_at' => $this->parseDateTime(Arr::get($item, 'updated_at')),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Parse datetime safely
     */
    private function parseDateTime($value)
    {
        if (!$value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Convert string to decimal safely
     */
    private function toDecimal($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $v = str_replace([','], [''], (string) $value);
        return is_numeric($v) ? (float) $v : null;
    }

    /**
     * Bulk upsert equipment work orders
     */
    private function bulkUpsertEquipmentWorkOrders(array $equipmentWorkOrderData): void
    {
        if (empty($equipmentWorkOrderData)) {
            return;
        }

        // Chunk to avoid placeholder limits
        $chunks = array_chunk($equipmentWorkOrderData, 500);

        foreach ($chunks as $chunk) {
            EquipmentWorkOrder::upsert(
                $chunk,
                ['plant_id', 'order_number', 'material'], // unique keys
                [
                    'equipment_number',
                    'functional_location',
                    'functional_location_description',
                    'reservation',
                    'requirement_type',
                    'reservation_status',
                    'item_deleted',
                    'movement_allowed',
                    'final_issue',
                    'missing_part',
                    'material_description',
                    'storage_location',
                    'requirements_date',
                    'requirement_quantity',
                    'base_unit_of_measure',
                    'debit_credit_ind',
                    'quantity_is_fixed',
                    'quantity_withdrawn',
                    'value_withdrawn',
                    'currency',
                    'qty_in_unit_of_entry',
                    'unit_of_entry',
                    'movement_type',
                    'gl_account',
                    'receiving_plant',
                    'receiving_storage_location',
                    'qty_for_avail_check',
                    'goods_recipient',
                    'material_group',
                    'acct_manually',
                    'commitment_item_1',
                    'commitment_item_2',
                    'funds_center',
                    'start_time',
                    'end_time',
                    'service_duration',
                    'service_dur_unit',
                    'api_updated_at',
                    'updated_at'
                ]
            );
        }
    }
}
