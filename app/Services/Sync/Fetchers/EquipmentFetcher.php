<?php

namespace App\Services\Sync\Fetchers;

class EquipmentFetcher extends BaseApiFetcher
{
    /**
     * Fetch equipment data
     */
    public function fetch(array $plantCodes, ?string $startDate = null, ?string $endDate = null): array
    {
        $url = $this->baseUrl . '/equipments';
        return $this->fetchInBatches($url, $plantCodes);
    }
}
