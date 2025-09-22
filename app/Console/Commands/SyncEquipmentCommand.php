<?php

namespace App\Console\Commands;

use App\Jobs\SyncEquipmentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncEquipmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:equipment {--force : Force sync even if another sync is running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize equipment data from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting equipment synchronization...');

        try {
            // Check if there's already a running sync job (unless forced)
            if (!$this->option('force')) {
                $runningSyncLog = \App\Models\ApiSyncLog::where('sync_type', 'equipment')
                    ->where('status', 'running')
                    ->first();

                if ($runningSyncLog) {
                    $this->warn('Equipment synchronization is already running. Use --force to override.');
                    return self::FAILURE;
                }
            }

            // Dispatch the sync job
            SyncEquipmentJob::dispatch();

            $this->info('Equipment synchronization job has been dispatched to the queue.');

            Log::info('Equipment synchronization command executed', [
                'forced' => $this->option('force'),
                'user' => $this->getUser(),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to dispatch equipment synchronization job: ' . $e->getMessage());

            Log::error('Equipment synchronization command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Get the current user context (if available).
     *
     * @return string
     */
    protected function getUser(): string
    {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $user = posix_getpwuid(posix_geteuid());
            return $user['name'] ?? 'unknown';
        }

        return 'system';
    }
}
