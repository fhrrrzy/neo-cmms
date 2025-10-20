<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentWorkOrderMaterial;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class EquipmentWorkOrderMaterialProcessor
{
    private const BATCH_SIZE = 2000;

    /**
     * Process a single equipment work order material item (legacy method for backward compatibility)
     */
    public function process(array $item, array $allowedPlantCodes = [], string $dataType = 'equipment_work_orders'): void
    {
        $this->processBatch([$item], $allowedPlantCodes, $dataType);
    }

    /**
     * Process equipment work order material items in batches for optimal performance
     */
    public function processBatch(array $items, array $allowedPlantCodes = [], string $dataType = 'equipment_work_orders'): void
    {
        if (empty($items)) {
            return;
        }

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($items, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $this->processChunk($chunk, $allowedPlantCodes, $dataType);
        }
    }

    /**
     * Process a chunk of equipment work order material items
     */
    private function processChunk(array $chunk, array $allowedPlantCodes = [], string $dataType = 'equipment_work_orders'): void
    {
        DB::transaction(function () use ($chunk, $allowedPlantCodes, $dataType) {
            // Pre-load all related data in bulk
            $lookupData = $this->preloadLookupData($chunk, $allowedPlantCodes);

            // Prepare equipment work order material data for bulk upsert
            $equipmentWorkOrderMaterialData = [];

            foreach ($chunk as $item) {
                $plantCode = Arr::get($item, 'plant');
                if (!$plantCode || (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true))) {
                    continue;
                }

                $plant = $lookupData['plants'][$plantCode] ?? null;
                if (!$plant) {
                    Log::warning('Skipping equipment_work_order_material item due to unknown plant code', [
                        'plant_code' => $plantCode,
                        'item_id' => Arr::get($item, 'id') ?? Arr::get($item, 'reservation_number'),
                    ]);
                    continue;
                }

                // Extract order number and material number for composite key
                $orderNumber = $this->extractOrderNumber($item, $dataType);
                $materialNumber = $this->extractMaterialNumber($item, $dataType);

                if (!$orderNumber && !$materialNumber) {
                    Log::warning('Skipping equipment_work_order_material item due to missing order_number and material_number', [
                        'item' => $item,
                        'data_type' => $dataType
                    ]);
                    continue;
                }


                $equipmentWorkOrderMaterialData[] = $this->prepareEquipmentWorkOrderMaterialData($item, $plant, $dataType);
            }

            // Bulk upsert equipment work order materials
            $this->bulkUpsertEquipmentWorkOrderMaterials($equipmentWorkOrderMaterialData);
        });
    }

    /**
     * Pre-load all lookup data needed for the chunk
     */
    private function preloadLookupData(array $chunk, array $allowedPlantCodes = []): array
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

        // Filter by allowed plant codes if specified
        if (!empty($allowedPlantCodes)) {
            $plantCodes = array_intersect($plantCodes, $allowedPlantCodes);
        }

        // Bulk load plants
        $plants = Plant::whereIn('plant_code', $plantCodes)->get()->keyBy('plant_code');

        return [
            'plants' => $plants,
        ];
    }

    /**
     * Extract order number from item based on data type
     */
    private function extractOrderNumber(array $item, string $dataType): ?string
    {
        if ($dataType === 'equipment_work_orders') {
            return Arr::get($item, 'order_number');
        } elseif ($dataType === 'equipment_materials') {
            return Arr::get($item, 'production_order') ?? Arr::get($item, 'planned_order');
        } elseif ($dataType === 'equipment_work_order_materials') {
            // For equipment_work_order_materials, use production_order as order_number
            return Arr::get($item, 'production_order') ?? Arr::get($item, 'planned_order') ?? Arr::get($item, 'order_number');
        }
        return null;
    }

    /**
     * Extract material number from item based on data type
     */
    private function extractMaterialNumber(array $item, string $dataType): ?string
    {
        if ($dataType === 'equipment_work_orders') {
            return Arr::get($item, 'material');
        } elseif ($dataType === 'equipment_materials') {
            return Arr::get($item, 'material_number') ?? Arr::get($item, 'material');
        } elseif ($dataType === 'equipment_work_order_materials') {
            // For equipment_work_order_materials, use material_number
            return Arr::get($item, 'material_number') ?? Arr::get($item, 'material');
        }
        return null;
    }

    /**
     * Prepare equipment work order material data for bulk upsert
     */
    private function prepareEquipmentWorkOrderMaterialData(array $item, Plant $plant, string $dataType): array
    {
        $baseData = [
            'plant_id' => $plant->id,
        ];

        if ($dataType === 'equipment_work_orders') {
            return array_merge($baseData, [
                // Work Order specific fields
                'order_number' => Arr::get($item, 'order_number'),
                'material_description' => Arr::get($item, 'material_description') ?? Arr::get($item, 'material_text'),

                // Common fields
                'requirement_type' => Arr::get($item, 'requirement_type'),
                'reservation_status' => Arr::get($item, 'reservation_status'),
                'storage_location' => Arr::get($item, 'storage_location'),
                'requirement_date' => Arr::get($item, 'requirements_date'),
                'requirement_qty' => $this->toDecimal(Arr::get($item, 'requirement_quantity')),
                'unit_of_measure' => Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_indicator' => Arr::get($item, 'debit_credit_ind'),
                'withdrawn_qty' => $this->toDecimal(Arr::get($item, 'quantity_withdrawn')),
                'withdrawn_value' => $this->toDecimal(Arr::get($item, 'value_withdrawn')),
                'currency' => Arr::get($item, 'currency'),
                'movement_type' => Arr::get($item, 'movement_type'),
                'gl_account' => Arr::get($item, 'gl_account'),
                'receiving_plant' => Arr::get($item, 'receiving_plant'),
                'receiving_storage_loc' => Arr::get($item, 'receiving_storage_location'),

                // Work Order specific fields
                'quantity_is_fixed' => Arr::get($item, 'quantity_is_fixed'),
                'qty_in_unit_of_entry' => $this->toDecimal(Arr::get($item, 'qty_in_unit_of_entry')),
                'unit_of_entry' => Arr::get($item, 'unit_of_entry'),
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
                'api_updated_at' => $this->parseApiDateTime(Arr::get($item, 'updated_at')),

                // Material fields from work order
                'material_number' => Arr::get($item, 'material'),
                'reservation_number' => Arr::get($item, 'reservation'),

                // Map work order flags to material flags
                'deletion_flag' => Arr::get($item, 'item_deleted'),
                'final_issue_flag' => Arr::get($item, 'final_issue'),
                'error_flag' => Arr::get($item, 'missing_part'),
                'goods_receipt_flag' => Arr::get($item, 'movement_allowed'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else { // equipment_materials
            return array_merge($baseData, [
                // Material specific fields
                'material_number' => Arr::get($item, 'material_number') ?? Arr::get($item, 'material'),
                'reservation_number' => Arr::get($item, 'reservation_number') ?? Arr::get($item, 'reservation'),
                'reservation_item' => Arr::get($item, 'reservation_item'),
                'reservation_type' => Arr::get($item, 'reservation_type'),
                'production_order' => Arr::get($item, 'production_order'),
                'planned_order' => Arr::get($item, 'planned_order'),

                // Common fields
                'requirement_type' => Arr::get($item, 'requirement_type'),
                'reservation_status' => Arr::get($item, 'reservation_status'),
                'storage_location' => Arr::get($item, 'storage_location'),
                'requirement_date' => Arr::get($item, 'requirement_date') ?? Arr::get($item, 'requirements_date'),
                'requirement_qty' => $this->toDecimal(Arr::get($item, 'requirement_qty') ?? Arr::get($item, 'requirement_quantity')),
                'unit_of_measure' => Arr::get($item, 'unit_of_measure') ?? Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_indicator' => Arr::get($item, 'debit_credit_indicator') ?? Arr::get($item, 'debit_credit_ind'),
                'withdrawn_qty' => $this->toDecimal(Arr::get($item, 'withdrawn_qty')),
                'withdrawn_value' => $this->toDecimal(Arr::get($item, 'withdrawn_value')),
                'currency' => Arr::get($item, 'currency'),
                'movement_type' => Arr::get($item, 'movement_type'),
                'gl_account' => Arr::get($item, 'gl_account'),
                'receiving_plant' => Arr::get($item, 'receiving_plant'),
                'receiving_storage_loc' => Arr::get($item, 'receiving_storage_loc') ?? Arr::get($item, 'receiving_storage_location'),

                // Material specific fields
                'deletion_flag' => Arr::get($item, 'deletion_flag') ?? Arr::get($item, 'item_deleted'),
                'goods_receipt_flag' => Arr::get($item, 'goods_receipt_flag') ?? Arr::get($item, 'movement_allowed'),
                'final_issue_flag' => Arr::get($item, 'final_issue_flag') ?? Arr::get($item, 'final_issue'),
                'error_flag' => Arr::get($item, 'error_flag') ?? Arr::get($item, 'missing_part'),
                'production_supply_area' => Arr::get($item, 'production_supply_area'),
                'batch_number' => Arr::get($item, 'batch_number'),
                'storage_bin' => Arr::get($item, 'storage_bin'),
                'special_stock_indicator' => Arr::get($item, 'special_stock_indicator'),
                'issued_qty' => $this->toDecimal(Arr::get($item, 'issued_qty')),
                'entry_qty' => $this->toDecimal(Arr::get($item, 'entry_qty')),
                'entry_uom' => Arr::get($item, 'entry_uom') ?? Arr::get($item, 'unit_of_entry'),
                'purchase_requisition' => Arr::get($item, 'purchase_requisition'),
                'purchase_requisition_item' => Arr::get($item, 'purchase_requisition_item'),
                'api_created_at' => $this->parseApiDateTime(Arr::get($item, 'api_created_at') ?? Arr::get($item, 'created_at')),

                // Extract order number from production_order or planned_order
                'order_number' => Arr::get($item, 'production_order') ?? Arr::get($item, 'planned_order'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Parse API datetime safely
     */
    private function parseApiDateTime($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            Log::warning('Failed to parse equipment_work_order_material api_created_at', ['value' => $value]);
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
     * Bulk upsert equipment work order materials using Eloquent for better performance and safety
     * Chunks data to avoid MySQL placeholder limit (65535)
     */
    private function bulkUpsertEquipmentWorkOrderMaterials(array $equipmentWorkOrderMaterialData): void
    {
        if (empty($equipmentWorkOrderMaterialData)) {
            return;
        }

        // Chunk data to avoid "too many placeholders" error
        // With ~50 columns, 500 rows = 25,000 placeholders (well under 65,535 limit)
        $chunks = array_chunk($equipmentWorkOrderMaterialData, 500);

        foreach ($chunks as $chunk) {
            EquipmentWorkOrderMaterial::upsert(
                $chunk,
                ['plant_id', 'order_number', 'material_number'], // unique keys
                [
                    'reservation_number',
                    'reservation_item',
                    'reservation_type',
                    'requirement_type',
                    'reservation_status',
                    'storage_location',
                    'requirement_date',
                    'requirement_qty',
                    'unit_of_measure',
                    'debit_credit_indicator',
                    'withdrawn_qty',
                    'withdrawn_value',
                    'currency',
                    'movement_type',
                    'gl_account',
                    'receiving_plant',
                    'receiving_storage_loc',
                    'quantity_is_fixed',
                    'qty_in_unit_of_entry',
                    'unit_of_entry',
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
                    'deletion_flag',
                    'final_issue_flag',
                    'error_flag',
                    'goods_receipt_flag',
                    'production_supply_area',
                    'batch_number',
                    'storage_bin',
                    'special_stock_indicator',
                    'issued_qty',
                    'entry_qty',
                    'entry_uom',
                    'purchase_requisition',
                    'purchase_requisition_item',
                    'production_order',
                    'api_created_at',
                    'updated_at'
                ]
            );
        }
    }
}
