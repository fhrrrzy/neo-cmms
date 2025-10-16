<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Equipment;
use App\Models\EquipmentMaterial;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

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
                Log::warning('Skipping equipment_material item due to missing plant code', ['item' => $item]);
                return; // skip instead of throwing to avoid marking as failed
            }

            if (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true)) {
                return;
            }

            $plant = Plant::where('plant_code', $plantCode)->first();
            if (!$plant) {
                Log::warning('Skipping equipment_material item due to unknown plant code', [
                    'plant_code' => $plantCode,
                    'item_id' => Arr::get($item, 'id') ?? Arr::get($item, 'reservation_number'),
                ]);
                return; // skip items for plants not present locally
            }

            // Note: Equipment materials don't have equipment_number in the API response
            // The relationship to equipment can be established through equipment work orders
            // via reservation_number or material_number when needed

            // Build unique keys: prefer ims_id when present; otherwise use a natural composite key
            $imsId = Arr::get($item, 'id');
            $naturalKey = [
                'plant_id' => $plant->id,
                'reservation_number' => Arr::get($item, 'reservation_number') ?? Arr::get($item, 'reservation'),
                'reservation_item' => Arr::get($item, 'reservation_item') ?? Arr::get($item, 'reservation_item'),
                'material_number' => Arr::get($item, 'material_number') ?? Arr::get($item, 'material'),
            ];
            $where = $imsId ? ['ims_id' => (string) $imsId] : $naturalKey;

            EquipmentMaterial::updateOrCreate(
                $where,
                [
                    'plant_id' => $plant->id,
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
                    'api_created_at' => self::parseApiDateTime(Arr::get($item, 'api_created_at') ?? Arr::get($item, 'created_at')),
                    // Persist ims_id as nullable when missing to avoid unique conflicts on empty string
                    'ims_id' => $imsId ? (string) $imsId : null,
                ]
            );
        } catch (Exception $e) {
            Log::error('EquipmentMaterialProcessor error: ' . $e->getMessage(), [
                'item' => $item,
                'trace' => $e->getTraceAsString()
            ]);
            // Do not rethrow; allow sync to continue and mark item as handled.
            return;
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
            Log::warning('Failed to parse equipment_material api_created_at', ['value' => $value]);
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
