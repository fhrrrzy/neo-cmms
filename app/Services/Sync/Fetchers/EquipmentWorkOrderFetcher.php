<?php

namespace App\Services\Sync\Fetchers;

class EquipmentWorkOrderFetcher extends BaseApiFetcher
{
    /**
     * Fetch equipment work order data
     */
    public function fetch(array $plantCodes, ?string $startDate = null, ?string $endDate = null): array
    {
        $url = $this->baseUrl . '/equipments/work-order?start_date=' . urlencode($startDate ?? now()->subDays(3)->toDateString()) . '&end_date=' . urlencode($endDate ?? now()->toDateString());
        return $this->fetchInBatches($url, $plantCodes);
    }
}
