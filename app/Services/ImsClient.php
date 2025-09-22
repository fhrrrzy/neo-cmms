<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ImsClient
{
    private string $baseUrl;
    private string $token;
    private int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ims.base_url'), '/');
        $this->token = (string) config('ims.token');
        $this->timeoutSeconds = (int) config('ims.timeout', 30);
    }

    public function getEquipments(): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])
            ->timeout($this->timeoutSeconds)
            ->get($this->baseUrl . '/equipments');

        $response->throw();
        return $response->json() ?? [];
    }

    public function getRunningTime(string $startDate, string $endDate): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])
            ->timeout($this->timeoutSeconds)
            ->get($this->baseUrl . '/equipments/jam-jalan', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $response->throw();
        return $response->json() ?? [];
    }
}
