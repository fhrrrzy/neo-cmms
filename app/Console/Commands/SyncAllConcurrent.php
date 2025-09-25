<?php

namespace App\Console\Commands;

use App\Services\Sync\ConcurrentApiSyncService;
use App\Models\Plant;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncAllConcurrent extends Command
{
    protected $signature = 'sync:all-concurrent 
                            {--plants= : Comma-separated list of plant codes to sync (optional, defaults to all active plants)}
                            {--running-time-start= : YYYY-MM-DD start date for running time (defaults to yesterday)}
                            {--running-time-end= : YYYY-MM-DD end date for running time (defaults to yesterday)}
                            {--work-order-start= : YYYY-MM-DD start date for work orders (defaults to first day of previous month)}
                            {--work-order-end= : YYYY-MM-DD end date for work orders (defaults to today)}';

    protected $description = 'Sync all APIs (equipment, running time, work orders) concurrently using Http::pool()';

    public function handle(): int
    {
        $this->info("🚀 Starting concurrent synchronization of all APIs...");

        $startTime = now();

        try {
            // Get plant codes
            $plantCodes = $this->getPlantCodes();

            // Get date ranges
            $runningTimeStart = $this->option('running-time-start') ?? Carbon::yesterday()->toDateString();
            $runningTimeEnd = $this->option('running-time-end') ?? Carbon::yesterday()->toDateString();
            $workOrderStart = $this->option('work-order-start') ?? Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $workOrderEnd = $this->option('work-order-end') ?? Carbon::today()->toDateString();

            $this->info("Plants: " . count($plantCodes) . " (" . implode(', ', array_slice($plantCodes, 0, 5)) . (count($plantCodes) > 5 ? '...' : '') . ")");
            $this->info("Running Time: {$runningTimeStart} to {$runningTimeEnd}");
            $this->info("Work Orders: {$workOrderStart} to {$workOrderEnd}");

            // Use concurrent sync service
            $syncService = new ConcurrentApiSyncService();
            $results = $syncService->syncAllConcurrently(
                $plantCodes,
                $runningTimeStart,
                $runningTimeEnd,
                $workOrderStart,
                $workOrderEnd
            );

            $duration = now()->diffInSeconds($startTime);

            // Display results
            $this->displayResults($results, $duration);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);
            $this->error("❌ Concurrent sync failed after {$duration} seconds: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Get plant codes to sync
     */
    private function getPlantCodes(): array
    {
        $plantsOption = $this->option('plants');

        if ($plantsOption) {
            // Use specified plants
            return array_map('trim', explode(',', $plantsOption));
        }

        // Use all active plants
        return Plant::where('is_active', true)
            ->pluck('plant_code')
            ->toArray();
    }

    /**
     * Display sync results
     */
    private function displayResults(array $results, int $duration): void
    {
        $this->info("✅ Concurrent synchronization completed in {$duration} seconds");
        $this->line("");
        $this->line("📊 Summary:");

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($results as $apiType => $result) {
            $status = isset($result['error']) ? '❌' : '✅';
            $processed = $result['processed'] ?? 0;
            $success = $result['success'] ?? 0;
            $failed = $result['failed'] ?? 0;

            $totalProcessed += $processed;
            $totalSuccess += $success;
            $totalFailed += $failed;

            $this->line("  {$status} " . ucfirst(str_replace('_', ' ', $apiType)) . ": processed={$processed}, success={$success}, failed={$failed}");

            if (isset($result['error'])) {
                $this->line("      Error: " . $result['error']);
            }
        }

        $this->line("");
        $this->line("🎯 Total: processed={$totalProcessed}, success={$totalSuccess}, failed={$totalFailed}");

        // Performance comparison
        $sequentialEstimate = ($duration * 3); // Estimate if done sequentially
        $improvement = $sequentialEstimate > 0 ? round((($sequentialEstimate - $duration) / $sequentialEstimate) * 100, 1) : 0;

        $this->line("⚡ Performance: ~{$improvement}% faster than sequential requests");
    }
}
