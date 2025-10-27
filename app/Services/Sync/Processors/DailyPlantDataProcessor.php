<?php

namespace App\Services\Sync\Processors;

use App\Models\DailyPlantData;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyPlantDataProcessor
{
    /**
     * Process a single daily plant data item (legacy method for backward compatibility)
     */
    public function process(array $item): void
    {
        $this->processBatch([$item]);
    }

    /**
     * Process daily plant data items in batches
     */
    public function processBatch(array $items, ?string $date = null): void
    {
        if (empty($items)) {
            return;
        }

        $date = $date ?? now()->toDateString();

        DB::transaction(function () use ($items, $date) {
            foreach ($items as $item) {
                if (!isset($item['kode_unit'])) {
                    Log::warning('DailyPlantDataProcessor: Missing kode_unit', ['item' => $item]);
                    continue;
                }

                // Get plant by code
                $plant = Plant::where('plant_code', $item['kode_unit'])->first();

                if (!$plant) {
                    Log::warning('DailyPlantDataProcessor: Plant not found', [
                        'plant_code' => $item['kode_unit']
                    ]);
                    continue;
                }

                // Get or create daily plant data
                DailyPlantData::updateOrCreate(
                    [
                        'plant_id' => $plant->id,
                        'date' => $date,
                    ],
                    [
                        'is_mengolah' => $item['is_mengolah'] ?? 0,
                    ]
                );
            }
        });
    }
}
