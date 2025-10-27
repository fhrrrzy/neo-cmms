<?php

namespace App\Services\Sync\Fetchers;

class DailyPlantDataFetcher
{
    protected int $timeoutSeconds;

    public function __construct()
    {
        $this->timeoutSeconds = (int) config('ims.timeout', 30);
    }

    /**
     * Fetch daily plant data using regional codes
     * Hardcoded regional codes: 1, 2, 3, 4, 5, 6, M, 7, 8, K, 9, J, N
     */
    public function fetch(array $plantCodes, ?string $startDate = null, ?string $endDate = null): array
    {
        $baseUrl = 'https://api-ims.ptpn4.co.id/api/pica-api/rekapitulasi';
        $token = config('ims.token');

        // Hardcoded regional codes
        $regionalCodes = ['1', '2', '3', '4', '5', '6', 'M', '7', '8', 'K', '9', 'J', 'N'];

        $allItems = [];

        // Fetch all regions concurrently
        $promises = [];
        foreach ($regionalCodes as $regional) {
            $params = [
                'regional' => $regional,
                'start_date' => $startDate ?? now()->subDays(3)->toDateString(),
                'end_date' => $endDate ?? now()->toDateString(),
            ];

            \Illuminate\Support\Facades\Log::info("GET {$baseUrl}?regional={$regional}&start_date={$params['start_date']}&end_date={$params['end_date']}");

            $promises[$regional] = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])
                ->timeout($this->timeoutSeconds)
                ->async()
                ->get($baseUrl, $params);
        }

        // Wait for all requests to complete
        foreach ($promises as $regional => $promise) {
            try {
                $response = $promise->wait();

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['data']) && is_array($data['data'])) {
                        // Add all items from this region
                        $allItems = array_merge($allItems, $data['data']);
                        \Illuminate\Support\Facades\Log::info("✓ Got daily plant data for regional {$regional}: " . count($data['data']) . " plants");
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("Failed to get daily plant data for regional {$regional}: HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to get daily plant data for regional {$regional}: " . $e->getMessage());
            }
        }

        return $allItems;
    }
}
