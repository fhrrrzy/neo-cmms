<?php

namespace App\Services\Sync\Fetchers;

class EquipmentMaterialFetcher extends BaseApiFetcher
{
    /**
     * Fetch equipment material data
     */
    public function fetch(array $plantCodes, ?string $startDate = null, ?string $endDate = null): array
    {
        $url = $this->baseUrl . '/equipments/material?start_date=' . urlencode($startDate ?? now()->subDays(3)->toDateString()) . '&end_date=' . urlencode($endDate ?? now()->toDateString());
        return $this->fetchInBatches($url, $plantCodes);
    }
}
