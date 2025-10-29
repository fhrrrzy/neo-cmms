<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class WebhookTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:test {endpoint?} {--generate-key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook endpoints with curl commands or generate API key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Generate API key if requested
        if ($this->option('generate-key')) {
            $this->generateApiKey();
            return 0;
        }

        $apiKey = config('webhook.api_key');
        $baseUrl = config('app.url');

        if (empty($apiKey)) {
            $this->error('Webhook API key not configured!');
            $this->info('Generate one using: php artisan webhook:test --generate-key');
            return 1;
        }

        $endpoint = $this->argument('endpoint');

        if ($endpoint) {
            $this->testEndpoint($endpoint, $apiKey, $baseUrl);
        } else {
            $this->showAllEndpoints($apiKey, $baseUrl);
        }

        return 0;
    }

    /**
     * Generate a new API key
     */
    protected function generateApiKey(): void
    {
        $apiKey = Str::random(64);

        $this->info('Generated new webhook API key:');
        $this->line('');
        $this->warn($apiKey);
        $this->line('');
        $this->info('Add this to your .env file:');
        $this->line('WEBHOOK_API_KEY=' . $apiKey);
        $this->line('');
    }

    /**
     * Test a specific endpoint
     */
    protected function testEndpoint(string $endpoint, string $apiKey, string $baseUrl): void
    {
        $endpoints = $this->getEndpoints();

        if (!isset($endpoints[$endpoint])) {
            $this->error("Unknown endpoint: {$endpoint}");
            $this->info('Available endpoints: ' . implode(', ', array_keys($endpoints)));
            return;
        }

        $config = $endpoints[$endpoint];
        $url = "{$baseUrl}/api/webhook/sync/{$config['path']}";

        $this->info("Testing: {$config['name']}");
        $this->line('');

        // Method 1: Using header
        $this->comment('Method 1: Using X-Webhook-Key header');
        $curlHeader = "curl -X GET \\\n";
        $curlHeader .= "  '{$url}' \\\n";
        $curlHeader .= "  -H 'X-Webhook-Key: {$apiKey}'";

        if (!empty($config['params'])) {
            $curlHeader .= " \\\n  -G";
            foreach ($config['params'] as $param => $example) {
                $curlHeader .= " \\\n  -d '{$param}={$example}'";
            }
        }

        $this->line($curlHeader);
        $this->line('');

        // Method 2: Using query parameter
        $this->comment('Method 2: Using api_key query parameter');
        $curlQuery = "curl -X GET \\\n";
        $queryParams = array_merge(['api_key' => $apiKey], $config['params'] ?? []);
        $queryString = http_build_query($queryParams);
        $curlQuery .= "  '{$url}?{$queryString}'";

        $this->line($curlQuery);
        $this->line('');

        // Execute test if confirmed
        if ($this->confirm('Execute this test request?', false)) {
            $this->executeTest($url, $apiKey, $config['params'] ?? []);
        }
    }

    /**
     * Show all available endpoints
     */
    protected function showAllEndpoints(string $apiKey, string $baseUrl): void
    {
        $this->info('Available Webhook Endpoints:');
        $this->line('');

        $endpoints = $this->getEndpoints();

        foreach ($endpoints as $key => $config) {
            $this->comment("{$config['name']} ({$key})");
            $this->line("  URL: {$baseUrl}/api/webhook/sync/{$config['path']}");

            if (!empty($config['params'])) {
                $this->line('  Parameters:');
                foreach ($config['params'] as $param => $example) {
                    $this->line("    - {$param}: {$example}");
                }
            }

            $this->line('');
        }

        $this->info('Test a specific endpoint:');
        $this->line('  php artisan webhook:test <endpoint>');
        $this->line('');
        $this->line('Example: php artisan webhook:test equipment');
    }

    /**
     * Get endpoint configurations
     */
    protected function getEndpoints(): array
    {
        return [
            'equipment' => [
                'name' => 'Sync Equipment',
                'path' => 'equipment',
                'params' => [],
            ],
            'running-time' => [
                'name' => 'Sync Running Time',
                'path' => 'running-time',
                'params' => [
                    'start_date' => date('Y-m-d', strtotime('-3 days')),
                    'end_date' => date('Y-m-d'),
                ],
            ],
            'work-orders' => [
                'name' => 'Sync Work Orders',
                'path' => 'work-orders',
                'params' => [
                    'start_date' => date('Y-m-d', strtotime('-3 days')),
                    'end_date' => date('Y-m-d'),
                ],
            ],
            'equipment-work-orders' => [
                'name' => 'Sync Equipment Work Orders',
                'path' => 'equipment-work-orders',
                'params' => [
                    'start_date' => date('Y-m-d', strtotime('-3 days')),
                    'end_date' => date('Y-m-d'),
                ],
            ],
            'equipment-materials' => [
                'name' => 'Sync Equipment Materials',
                'path' => 'equipment-materials',
                'params' => [
                    'start_date' => date('Y-m-d', strtotime('-3 days')),
                    'end_date' => date('Y-m-d'),
                ],
            ],
            'daily-plant-data' => [
                'name' => 'Sync Daily Plant Data',
                'path' => 'daily-plant-data',
                'params' => [
                    'start_date' => date('Y-m-d', strtotime('-3 days')),
                    'end_date' => date('Y-m-d'),
                ],
            ],
            'all' => [
                'name' => 'Sync All Data',
                'path' => 'all',
                'params' => [
                    'start_date' => date('Y-m-d', strtotime('-3 days')),
                    'end_date' => date('Y-m-d'),
                ],
            ],
        ];
    }

    /**
     * Execute test request
     */
    protected function executeTest(string $url, string $apiKey, array $params): void
    {
        $this->info('Executing request...');

        $queryParams = array_merge(['api_key' => $apiKey], $params);
        $fullUrl = $url . '?' . http_build_query($queryParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->line('');
        $this->info("HTTP Status: {$httpCode}");
        $this->line('');

        if ($response) {
            $json = json_decode($response, true);
            if ($json) {
                $this->line(json_encode($json, JSON_PRETTY_PRINT));
            } else {
                $this->line($response);
            }
        }
    }
}
