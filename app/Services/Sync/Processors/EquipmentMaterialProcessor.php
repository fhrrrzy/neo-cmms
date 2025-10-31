<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\EquipmentMaterial;
use App\Models\WorkOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EquipmentMaterialProcessor
{
    private const BATCH_SIZE = 2000;
    // Any material numbers that start with these prefixes will be ignored for insert/update
    private const EXCLUDED_MATERIAL_PREFIXES = ['11', '12', '31'];

    /**
     * Process a single equipment material item
     */
    public function process(array $item, array $allowedPlantCodes = []): void
    {
        $this->processBatch([$item], $allowedPlantCodes);
    }

    /**
     * Process equipment material items in batches
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
     * Process a chunk of equipment material items
     */
    private function processChunk(array $chunk, array $allowedPlantCodes = []): void
    {
        DB::transaction(function () use ($chunk, $allowedPlantCodes) {
            $lookupData = $this->preloadLookupData($chunk, $allowedPlantCodes);
            $equipmentMaterialData = [];
            $deletions = [];
            $retainByPlantAndOrder = [];

            foreach ($chunk as $item) {
                $plantCode = Arr::get($item, 'plant');
                if (!$plantCode || (!empty($allowedPlantCodes) && !in_array($plantCode, $allowedPlantCodes, true))) {
                    continue;
                }

                $plant = $lookupData['plants'][$plantCode] ?? null;
                if (!$plant) {
                    Log::warning('Skipping equipment_material item due to unknown plant code', [
                        'plant_code' => $plantCode,
                        'item_id' => Arr::get($item, 'id') ?? Arr::get($item, 'reservation_number'),
                    ]);
                    continue;
                }

                $deletionFlag = Arr::get($item, 'deletion_flag') ?? Arr::get($item, 'item_deleted');
                if ($deletionFlag === 'X') {
                    $deletions[] = [
                        'plant_id' => $plant->id,
                        'material_number' => Arr::get($item, 'material_number') ?? Arr::get($item, 'material'),
                        'reservation_number' => Arr::get($item, 'reservation_number') ?? Arr::get($item, 'reservation'),
                    ];
                    continue; // skip insert/upsert for deletion flagged records
                }

                // Skip inserts/updates for excluded material number prefixes
                $materialNumber = Arr::get($item, 'material_number') ?? Arr::get($item, 'material');
                if ($this->isExcludedMaterialNumber($materialNumber)) {
                    continue;
                }

                $prepared = $this->prepareEquipmentMaterialData($item, $plant, $lookupData);
                $equipmentMaterialData[] = $prepared;

                // Track materials to retain for delete-sync per (plant_id, production_order)
                if (!empty($prepared['production_order'])) {
                    $key = $prepared['plant_id'] . '|' . $prepared['production_order'];
                    if (!isset($retainByPlantAndOrder[$key])) {
                        $retainByPlantAndOrder[$key] = [
                            'plant_id' => $prepared['plant_id'],
                            'production_order' => $prepared['production_order'],
                            'materials' => [],
                        ];
                    }
                    $retainByPlantAndOrder[$key]['materials'][] = (string) $prepared['material_number'];
                }
            }

            // Perform deletions for items flagged with deletion_flag = 'X'
            if (!empty($deletions)) {
                foreach (array_chunk($deletions, 500) as $deleteChunk) {
                    foreach ($deleteChunk as $d) {
                        if (!empty($d['plant_id']) && !empty($d['material_number']) && !empty($d['reservation_number'])) {
                            EquipmentMaterial::where('plant_id', $d['plant_id'])
                                ->where('material_number', $d['material_number'])
                                ->where('reservation_number', $d['reservation_number'])
                                ->delete();
                        }
                    }
                }
            }

            // Delete-sync: remove rows not present in incoming payload for each (plant_id, production_order)
            if (!empty($retainByPlantAndOrder)) {
                foreach ($retainByPlantAndOrder as $group) {
                    $materials = array_values(array_unique(array_filter($group['materials'])));
                    if (!empty($materials)) {
                        EquipmentMaterial::where('plant_id', $group['plant_id'])
                            ->where('production_order', $group['production_order'])
                            ->whereNotIn('material_number', $materials)
                            ->delete();
                    }
                }
            }

            $this->bulkUpsertEquipmentMaterials($equipmentMaterialData);
        });
    }

    /**
     * Determine if a material number should be excluded based on prefixes
     */
    private function isExcludedMaterialNumber($materialNumber): bool
    {
        if ($materialNumber === null || $materialNumber === '') {
            return false;
        }
        $materialString = (string) $materialNumber;
        foreach (self::EXCLUDED_MATERIAL_PREFIXES as $prefix) {
            if (str_starts_with($materialString, $prefix)) {
                return true;
            }
        }
        return false;
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

        // Extract and validate production orders
        $productionOrders = collect($chunk)
            ->map(fn($item) => Arr::get($item, 'production_order') ?? Arr::get($item, 'planned_order'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $validWorkOrders = WorkOrder::whereIn('order', $productionOrders)
            ->pluck('order')
            ->flip()
            ->toArray();

        return [
            'plants' => $plants,
            'validWorkOrders' => $validWorkOrders,
        ];
    }

    /**
     * Prepare equipment material data for bulk upsert
     */
    private function prepareEquipmentMaterialData(array $item, Plant $plant, array $lookupData): array
    {
        // Validate production_order against existing work orders
        $productionOrder = Arr::get($item, 'production_order');
        if ($productionOrder && !isset($lookupData['validWorkOrders'][$productionOrder])) {
            $productionOrder = null; // Set to null if work order doesn't exist
        }

        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'plant_id' => $plant->id,
            'material_number' => Arr::get($item, 'material_number') ?? Arr::get($item, 'material'),
            'reservation_number' => Arr::get($item, 'reservation_number') ?? Arr::get($item, 'reservation'),
            'reservation_item' => Arr::get($item, 'reservation_item'),
            'reservation_type' => Arr::get($item, 'reservation_type'),
            'requirement_type' => Arr::get($item, 'requirement_type'),
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
            'requirement_qty' => $this->toDecimal(Arr::get($item, 'requirement_qty') ?? Arr::get($item, 'requirement_quantity')),
            'unit_of_measure' => Arr::get($item, 'unit_of_measure') ?? Arr::get($item, 'base_unit_of_measure'),
            'debit_credit_indicator' => Arr::get($item, 'debit_credit_indicator') ?? Arr::get($item, 'debit_credit_ind'),
            'issued_qty' => $this->toDecimal(Arr::get($item, 'issued_qty')),
            'withdrawn_qty' => $this->toDecimal(Arr::get($item, 'withdrawn_qty') ?? Arr::get($item, 'quantity_withdrawn')),
            'withdrawn_value' => $this->toDecimal(Arr::get($item, 'withdrawn_value') ?? Arr::get($item, 'value_withdrawn')),
            'currency' => Arr::get($item, 'currency'),
            'entry_qty' => $this->toDecimal(Arr::get($item, 'entry_qty')),
            'entry_uom' => Arr::get($item, 'entry_uom') ?? Arr::get($item, 'unit_of_entry'),
            'planned_order' => Arr::get($item, 'planned_order'),
            'purchase_requisition' => Arr::get($item, 'purchase_requisition'),
            'purchase_requisition_item' => Arr::get($item, 'purchase_requisition_item'),
            'production_order' => $productionOrder, // Use validated production order
            'movement_type' => Arr::get($item, 'movement_type'),
            'gl_account' => Arr::get($item, 'gl_account'),
            'receiving_storage_loc' => Arr::get($item, 'receiving_storage_loc') ?? Arr::get($item, 'receiving_storage_location'),
            'receiving_plant' => Arr::get($item, 'receiving_plant'),
            'api_created_at' => $this->parseDateTime(Arr::get($item, 'api_created_at') ?? Arr::get($item, 'created_at')),
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
     * Bulk upsert equipment materials
     */
    private function bulkUpsertEquipmentMaterials(array $equipmentMaterialData): void
    {
        if (empty($equipmentMaterialData)) {
            return;
        }

        // Chunk to avoid placeholder limits
        $chunks = array_chunk($equipmentMaterialData, 500);

        foreach ($chunks as $chunk) {
            EquipmentMaterial::upsert(
                $chunk,
                ['plant_id', 'material_number', 'production_order'], // unique keys per request
                [
                    'reservation_item',
                    'reservation_type',
                    'requirement_type',
                    'reservation_status',
                    'deletion_flag',
                    'goods_receipt_flag',
                    'final_issue_flag',
                    'error_flag',
                    'storage_location',
                    'production_supply_area',
                    'batch_number',
                    'storage_bin',
                    'special_stock_indicator',
                    'requirement_date',
                    'requirement_qty',
                    'unit_of_measure',
                    'debit_credit_indicator',
                    'issued_qty',
                    'withdrawn_qty',
                    'withdrawn_value',
                    'currency',
                    'entry_qty',
                    'entry_uom',
                    'planned_order',
                    'purchase_requisition',
                    'purchase_requisition_item',
                    'production_order',
                    'movement_type',
                    'gl_account',
                    'receiving_storage_loc',
                    'receiving_plant',
                    'api_created_at',
                    'updated_at'
                ]
            );
        }
    }
}
