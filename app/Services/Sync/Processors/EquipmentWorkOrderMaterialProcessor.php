<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentWorkOrderMaterial;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class EquipmentWorkOrderMaterialProcessor
{
    /**
     * Process a single equipment work order material record.
     */
    public function process(array $item, array $allowedPlantCodes = [], string $dataType = 'equipment_work_orders'): void
    {
        try {
            $plantCode = Arr::get($item, 'plant');
            if (!$plantCode) {
                Log::warning('Skipping equipment_work_order_material item due to missing plant code', ['item' => $item]);
                return; // skip instead of throwing to avoid marking as failed
            }

            if (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true)) {
                return;
            }

            $plant = Plant::where('plant_code', $plantCode)->first();
            if (!$plant) {
                Log::warning('Skipping equipment_work_order_material item due to unknown plant code', [
                    'plant_code' => $plantCode,
                    'item_id' => Arr::get($item, 'id') ?? Arr::get($item, 'reservation_number'),
                ]);
                return; // skip items for plants not present locally
            }

            // Extract order number and material number for composite key
            $orderNumber = $this->extractOrderNumber($item, $dataType);
            $materialNumber = $this->extractMaterialNumber($item, $dataType);

            if (!$orderNumber && !$materialNumber) {
                Log::warning('Skipping equipment_work_order_material item due to missing order_number and material_number', [
                    'item' => $item,
                    'data_type' => $dataType
                ]);
                return;
            }

            // Handle equipment creation/update if equipment_number is present
            $equipmentNumber = trim((string) (Arr::get($item, 'equipment_number') ?? ''));
            if ($equipmentNumber !== '') {
                $this->handleEquipment($equipmentNumber, $plant);
            }

            // Build composite key for upsert: order_number + material_number
            $where = [
                'plant_id' => $plant->id,
                'order_number' => $orderNumber,
                'material_number' => $materialNumber,
            ];

            // Prepare data based on data type
            $data = $this->prepareData($item, $plant, $dataType);

            EquipmentWorkOrderMaterial::updateOrCreate($where, $data);
        } catch (Exception $e) {
            Log::error('EquipmentWorkOrderMaterialProcessor error: ' . $e->getMessage(), [
                'item' => $item,
                'data_type' => $dataType,
                'trace' => $e->getTraceAsString()
            ]);
            // Do not rethrow; allow sync to continue and mark item as handled.
            return;
        }
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
        }
        return null;
    }

    /**
     * Handle equipment creation/update
     */
    private function handleEquipment(string $equipmentNumber, Plant $plant): void
    {
        $existingEq = Equipment::where('equipment_number', $equipmentNumber)->first();
        if ($existingEq) {
            if ($existingEq->plant_id !== $plant->id) {
                $existingEq->plant_id = $plant->id;
                $existingEq->is_active = true;
                $existingEq->save();
            }
        } else {
            Equipment::updateOrCreate(
                ['equipment_number' => $equipmentNumber],
                [
                    'plant_id' => $plant->id,
                    'equipment_group_id' => null,
                    'company_code' => null,
                    'equipment_description' => null,
                    'object_number' => null,
                    'point' => null,
                    'api_created_at' => Carbon::now(),
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Prepare data array based on data type
     */
    private function prepareData(array $item, Plant $plant, string $dataType): array
    {
        $baseData = [
            'plant_id' => $plant->id,
        ];

        if ($dataType === 'equipment_work_orders') {
            return array_merge($baseData, [
                // Work Order specific fields
                'order_number' => Arr::get($item, 'order_number'),
                'equipment_number' => Arr::get($item, 'equipment_number'),
                'material_description' => Arr::get($item, 'material_description') ?? Arr::get($item, 'material_text'),

                // Common fields
                'requirement_type' => Arr::get($item, 'requirement_type'),
                'reservation_status' => Arr::get($item, 'reservation_status'),
                'storage_location' => Arr::get($item, 'storage_location'),
                'requirement_date' => Arr::get($item, 'requirements_date'),
                'requirement_qty' => self::toDecimal(Arr::get($item, 'requirement_quantity')),
                'unit_of_measure' => Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_indicator' => Arr::get($item, 'debit_credit_ind'),
                'withdrawn_qty' => self::toDecimal(Arr::get($item, 'quantity_withdrawn')),
                'withdrawn_value' => self::toDecimal(Arr::get($item, 'value_withdrawn')),
                'currency' => Arr::get($item, 'currency'),
                'movement_type' => Arr::get($item, 'movement_type'),
                'gl_account' => Arr::get($item, 'gl_account'),
                'receiving_plant' => Arr::get($item, 'receiving_plant'),
                'receiving_storage_loc' => Arr::get($item, 'receiving_storage_location'),

                // Work Order specific fields
                'quantity_is_fixed' => Arr::get($item, 'quantity_is_fixed'),
                'qty_in_unit_of_entry' => self::toDecimal(Arr::get($item, 'qty_in_unit_of_entry')),
                'unit_of_entry' => Arr::get($item, 'unit_of_entry'),
                'qty_for_avail_check' => self::toDecimal(Arr::get($item, 'qty_for_avail_check')),
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
                'api_updated_at' => Arr::get($item, 'updated_at') ? Carbon::parse(Arr::get($item, 'updated_at')) : null,

                // Material fields from work order
                'material_number' => Arr::get($item, 'material'),
                'reservation_number' => Arr::get($item, 'reservation'),

                // Map work order flags to material flags
                'deletion_flag' => Arr::get($item, 'item_deleted'),
                'final_issue_flag' => Arr::get($item, 'final_issue'),
                'error_flag' => Arr::get($item, 'missing_part'),
                'goods_receipt_flag' => Arr::get($item, 'movement_allowed'),
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
                'requirement_qty' => self::toDecimal(Arr::get($item, 'requirement_qty') ?? Arr::get($item, 'requirement_quantity')),
                'unit_of_measure' => Arr::get($item, 'unit_of_measure') ?? Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_indicator' => Arr::get($item, 'debit_credit_indicator') ?? Arr::get($item, 'debit_credit_ind'),
                'withdrawn_qty' => self::toDecimal(Arr::get($item, 'withdrawn_qty')),
                'withdrawn_value' => self::toDecimal(Arr::get($item, 'withdrawn_value')),
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
                'issued_qty' => self::toDecimal(Arr::get($item, 'issued_qty')),
                'entry_qty' => self::toDecimal(Arr::get($item, 'entry_qty')),
                'entry_uom' => Arr::get($item, 'entry_uom') ?? Arr::get($item, 'unit_of_entry'),
                'purchase_requisition' => Arr::get($item, 'purchase_requisition'),
                'purchase_requisition_item' => Arr::get($item, 'purchase_requisition_item'),
                'api_created_at' => self::parseApiDateTime(Arr::get($item, 'api_created_at') ?? Arr::get($item, 'created_at')),

                // Extract order number from production_order or planned_order
                'order_number' => Arr::get($item, 'production_order') ?? Arr::get($item, 'planned_order'),
            ]);
        }
    }

    private static function parseApiDateTime($value): ?Carbon
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

    private static function toDecimal($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $v = str_replace([','], [''], (string) $value);
        return is_numeric($v) ? (float) $v : null;
    }
}
