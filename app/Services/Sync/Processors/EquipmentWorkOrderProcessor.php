<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentWorkOrder;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;

class EquipmentWorkOrderProcessor
{
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        $plantCode = Arr::get($item, 'plant');
        if (!$plantCode) {
            throw new Exception('Missing plant in equipment_work_orders item');
        }
        if (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true)) {
            return;
        }

        $plant = Plant::where('plant_code', $plantCode)->first();
        if (!$plant) {
            throw new Exception('Plant not found for equipment_work_orders: ' . (string) $plantCode);
        }

        $equipmentNumber = trim((string) (Arr::get($item, 'equipment_number') ?? ''));
        if ($equipmentNumber !== '') {
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

        EquipmentWorkOrder::updateOrCreate(
            ['ims_id' => (string) (Arr::get($item, 'id') ?? '')],
            [
                'reservation' => Arr::get($item, 'reservation'),
                'requirement_type' => Arr::get($item, 'requirement_type'),
                'reservation_status' => Arr::get($item, 'reservation_status'),
                'item_deleted' => Arr::get($item, 'item_deleted'),
                'movement_allowed' => Arr::get($item, 'movement_allowed'),
                'final_issue' => Arr::get($item, 'final_issue'),
                'missing_part' => Arr::get($item, 'missing_part'),
                'material' => Arr::get($item, 'material'),
                'plant_id' => $plant->id,
                'storage_location' => Arr::get($item, 'storage_location'),
                'requirements_date' => Arr::get($item, 'requirements_date'),
                'requirement_quantity' => self::toDecimal(Arr::get($item, 'requirement_quantity')),
                'base_unit_of_measure' => Arr::get($item, 'base_unit_of_measure'),
                'debit_credit_ind' => Arr::get($item, 'debit_credit_ind'),
                'quantity_is_fixed' => Arr::get($item, 'quantity_is_fixed'),
                'quantity_withdrawn' => self::toDecimal(Arr::get($item, 'quantity_withdrawn')),
                'value_withdrawn' => self::toDecimal(Arr::get($item, 'value_withdrawn')),
                'currency' => Arr::get($item, 'currency'),
                'qty_in_unit_of_entry' => self::toDecimal(Arr::get($item, 'qty_in_unit_of_entry')),
                'unit_of_entry' => Arr::get($item, 'unit_of_entry'),
                'movement_type' => Arr::get($item, 'movement_type'),
                'gl_account' => Arr::get($item, 'gl_account'),
                'receiving_plant' => Arr::get($item, 'receiving_plant'),
                'receiving_storage_location' => Arr::get($item, 'receiving_storage_location'),
                'qty_for_avail_check' => self::toDecimal(Arr::get($item, 'qty_for_avail_check')),
                'goods_recipient' => Arr::get($item, 'goods_recipient'),
                'material_group' => Arr::get($item, 'material_group'),
                'acct_manually' => Arr::get($item, 'acct_manually'),
                'commitment_item_1' => Arr::get($item, 'commitment_item_1'),
                'funds_center' => Arr::get($item, 'funds_center'),
                'start_time' => Arr::get($item, 'start_time'),
                'end_time' => Arr::get($item, 'end_time'),
                'service_duration' => Arr::get($item, 'service_duration'),
                'service_dur_unit' => Arr::get($item, 'service_dur_unit'),
                'api_updated_at' => Arr::get($item, 'updated_at') ? Carbon::parse(Arr::get($item, 'updated_at')) : null,
                'commitment_item_2' => Arr::get($item, 'commitment_item_2'),
                'order_number' => Arr::get($item, 'order_number'),
                'equipment_number' => $equipmentNumber,
            ]
        );
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
