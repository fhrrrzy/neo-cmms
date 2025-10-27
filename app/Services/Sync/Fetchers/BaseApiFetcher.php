<?php

namespace App\Services\Sync\Fetchers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseApiFetcher
{
    protected int $timeoutSeconds;
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->timeoutSeconds = config('ims.timeout', 300);
        $this->baseUrl = rtrim(config('ims.base_url'), '/');
        $this->token = config('ims.token');
    }

    /**
     * Fetch data from the API
     */
    abstract public function fetch(array $plantCodes, ?string $startDate = null, ?string $endDate = null): array;

    /**
     * Make an authenticated HTTP request
     */
    protected function makeRequest(string $method, string $url, array $options = []): mixed
    {
        $defaultHeaders = [
            'Authorization' => str_replace('Bearer ', '', $this->token),
            'Content-Type' => 'application/json'
        ];

        $response = Http::withHeaders($defaultHeaders)
            ->timeout($this->timeoutSeconds)
            ->send($method, $url, array_merge(['json' => []], $options));

        return $response;
    }

    /**
     * Process plants in batches to avoid API limits
     */
    protected function fetchInBatches(string $url, array $plantCodes, int $batchSize = 5, array $requestBodyCallback = null): array
    {
        $plantBatches = array_chunk($plantCodes, $batchSize);
        $allItems = [];

        foreach ($plantBatches as $batchIndex => $plantBatch) {
            $options = ['json' => ['plant' => array_values($plantBatch)]];

            if ($requestBodyCallback && is_callable($requestBodyCallback)) {
                $options['json'] = $requestBodyCallback($plantBatch);
            }

            $this->logBatch($batchIndex + 1, count($plantBatches), $plantBatch, $url);

            $response = $this->makeRequest('GET', $url, $options);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                $items = $data['data'] ?? $data;

                if (!empty($items) && is_array($items)) {
                    $allItems = array_merge($allItems, $items);
                    $this->logBatchSuccess($batchIndex + 1, count($items));
                }
            }
        }

        return $allItems;
    }

    /**
     * Fetch all plants concurrently using async requests
     */
    protected function fetchConcurrent(string $baseUrl, array $plantCodes, array $paramsCallback = null): array
    {
        $allItems = [];
        $promises = [];

        foreach ($plantCodes as $plantCode) {
            $url = $baseUrl;
            $params = ['pks' => $plantCode];

            if ($paramsCallback && is_callable($paramsCallback)) {
                $params = array_merge($params, $paramsCallback($plantCode));
            }

            $this->logConcurrentRequest($plantCode, $url, $params);

            $promises[$plantCode] = Http::timeout($this->timeoutSeconds)->async()->get($url, $params);
        }

        // Wait for all requests to complete
        foreach ($promises as $plantCode => $promise) {
            try {
                $response = $promise->wait();

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['data'][0])) {
                        $allItems[] = $data['data'][0];
                        $this->logConcurrentSuccess($plantCode);
                    }
                }
            } catch (\Exception $e) {
                $this->logConcurrentError($plantCode, $e);
            }
        }

        return $allItems;
    }

    protected function logBatch(int $batchNum, int $totalBatches, array $plantBatch, string $url): void
    {
        $message = "GET {$url} (batch {$batchNum}/{$totalBatches}, plants: " . implode(', ', $plantBatch) . ")";
        Log::info($message);
    }

    protected function logBatchSuccess(int $batchNum, int $itemCount): void
    {
        $message = "Batch {$batchNum} returned {$itemCount} items";
        Log::info($message);
    }

    protected function logConcurrentRequest(string $plantCode, string $url, array $params): void
    {
        $queryString = http_build_query($params);
        $message = "GET {$url}?{$queryString}";
        Log::info($message);
    }

    protected function logConcurrentSuccess(string $plantCode): void
    {
        Log::info("✓ Got data for {$plantCode}");
    }

    protected function logConcurrentError(string $plantCode, \Exception $e): void
    {
        Log::error("Failed to get data for {$plantCode}: " . $e->getMessage());
    }
}
