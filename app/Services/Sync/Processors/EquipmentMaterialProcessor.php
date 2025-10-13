<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentMaterial;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;

class EquipmentMaterialProcessor
{
    /**
     * Process a single equipment material record.
     */
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        try {
            $plantCode = Arr::get($item, 'plant');
            if (!$plantCode) {
                throw new Exception('Missing plant in equipment_material item');
            }

            if (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true)) {
                return;
            }

            $plant = Plant::where('plant_code', $plantCode)->first();
            if (!$plant) {
                throw new Exception('Plant not found for equipment_material: ' . (string) $plantCode);
            }

            // Find the equipment work order by reservation number to get the equipment number
            $reservationNumber = Arr::get($item, 'reservation_number');
            $equipmentNumber = null;

            if ($reservationNumber) {
                $equipmentWorkOrder = \App\Models\EquipmentWorkOrder::where('reservation', $reservationNumber)->first();
                if ($equipmentWorkOrder) {
                    $equipmentNumber = $equipmentWorkOrder->equipment_number;
                }
                // If no equipment work order found, equipment_number will remain null
                // The relationship can be established later when equipment work orders are synced
            }

            EquipmentMaterial::updateOrCreate(
                [
                    'ims_id' => (string) (Arr::get($item, 'id') ?? ''),
                ],
                [
                    'plant_id' => $plant->id,
                    'equipment_number' => $equipmentNumber,
                    'material_number' => Arr::get($item, 'material_number') ?? Arr::get($item, 'material'),
                    'reservation_number' => Arr::get($item, 'reservation_number') ?? Arr::get($item, 'reservation'),
                    'reservation_item' => Arr::get($item, 'reservation_item') ?? Arr::get($item, 'reservation_item'),
                    'reservation_type' => Arr::get($item, 'reservation_type'),
                    'requirement_type' => Arr::get($item, 'requirement_type') ?? Arr::get($item, 'requirement_type'),
                    'reservation_status' => Arr::get($item, 'reservation_status'),
                    'deletion_flag' => Arr::get($item, 'deletion_flag') ?? Arr::get($item, 'item_deleted'),
                    'goods_receipt_flag' => Arr::get($item, 'goods_receipt_flag') ?? Arr::get($item, 'movement_allowed'),
                    'final_issue_flag' => Arr::get($item, 'final_issue_flag') ?? Arr::get($item, 'final_issue'),
                    'error_flag' => Arr::get($item, 'error_flag') ?? Arr::get($item, 'missing_part'),
                    'storage_location' => Arr::get($item, 'storage_location'),
                    'production_supply_area' => Arr::get($item, 'production_supply_area'),
                    'batch_number' => Arr::get($item, 'batch_number'),
                    'storage_bin' => Arr::get($item, 'storage_bin'),
                    'special_stock_indicator' => Arr::get($item, 'special_stock_indicator'),
                    'requirement_date' => Arr::get($item, 'requirement_date') ?? Arr::get($item, 'requirements_date'),
                    'requirement_qty' => self::toDecimal(Arr::get($item, 'requirement_qty') ?? Arr::get($item, 'requirement_quantity')),
                    'unit_of_measure' => Arr::get($item, 'unit_of_measure') ?? Arr::get($item, 'base_unit_of_measure'),
                    'debit_credit_indicator' => Arr::get($item, 'debit_credit_indicator') ?? Arr::get($item, 'debit_credit_ind'),
                    'issued_qty' => self::toDecimal(Arr::get($item, 'issued_qty')),
                    'withdrawn_qty' => self::toDecimal(Arr::get($item, 'withdrawn_qty')),
                    'withdrawn_value' => self::toDecimal(Arr::get($item, 'withdrawn_value')),
                    'currency' => Arr::get($item, 'currency'),
                    'entry_qty' => self::toDecimal(Arr::get($item, 'entry_qty')),
                    'entry_uom' => Arr::get($item, 'entry_uom') ?? Arr::get($item, 'unit_of_entry'),
                    'planned_order' => Arr::get($item, 'planned_order'),
                    'purchase_requisition' => Arr::get($item, 'purchase_requisition'),
                    'purchase_requisition_item' => Arr::get($item, 'purchase_requisition_item'),
                    'production_order' => Arr::get($item, 'production_order'),
                    'movement_type' => Arr::get($item, 'movement_type'),
                    'gl_account' => Arr::get($item, 'gl_account'),
                    'receiving_storage_loc' => Arr::get($item, 'receiving_storage_loc') ?? Arr::get($item, 'receiving_storage_location'),
                    'receiving_plant' => Arr::get($item, 'receiving_plant'),
                    'api_created_at' => Arr::get($item, 'api_created_at') ? Carbon::parse(Arr::get($item, 'api_created_at')) : null,
                ]
            );
        } catch (Exception $e) {
            \Log::error('EquipmentMaterialProcessor error: ' . $e->getMessage(), [
                'item' => $item,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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
