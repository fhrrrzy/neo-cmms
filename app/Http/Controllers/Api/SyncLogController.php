<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiSyncLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SyncLogController extends Controller
{
    /**
     * Get paginated sync logs with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = ApiSyncLog::query()->orderBy('created_at', 'desc');

        // Filter by sync type
        if ($request->has('sync_type') && $request->sync_type !== 'all') {
            $query->where('sync_type', $request->sync_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by days (last X days)
        if ($request->has('days')) {
            $days = (int) $request->days;
            $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $logs = $query->paginate($perPage);

        // Calculate duration for each log
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'sync_type' => $log->sync_type,
                'status' => $log->status,
                'records_processed' => $log->records_processed,
                'records_success' => $log->records_success,
                'records_failed' => $log->records_failed,
                'error_message' => $log->error_message,
                'sync_started_at' => $log->sync_started_at?->toISOString(),
                'sync_completed_at' => $log->sync_completed_at?->toISOString(),
                'duration' => $this->calculateDuration($log),
                'created_at' => $log->created_at->toISOString(),
                'updated_at' => $log->updated_at->toISOString(),
            ];
        });

        return response()->json($logs);
    }

    /**
     * Get sync statistics
     */
    public function stats(): JsonResponse
    {
        $last7Days = Carbon::now()->subDays(7);

        // Total syncs in last 7 days
        $totalSyncs = ApiSyncLog::where('created_at', '>=', $last7Days)->count();

        // Success rate calculation
        $completedSyncs = ApiSyncLog::where('created_at', '>=', $last7Days)
            ->where('status', 'completed')
            ->count();

        $successRate = $totalSyncs > 0
            ? number_format(($completedSyncs / $totalSyncs) * 100, 1) . '%'
            : '0%';

        // Currently running syncs
        $runningSyncs = ApiSyncLog::where('status', 'running')->count();

        // Failed syncs in last 7 days
        $failedSyncs = ApiSyncLog::where('created_at', '>=', $last7Days)
            ->where('status', 'failed')
            ->count();

        // Latest sync per type
        $latestSyncs = [];
        $syncTypes = ['equipment', 'running_time', 'work_order', 'equipment_work_order_materials', 'daily_plant_data'];

        foreach ($syncTypes as $type) {
            $latest = ApiSyncLog::where('sync_type', $type)
                ->where('status', 'completed')
                ->latest('sync_completed_at')
                ->first();

            if ($latest) {
                $latestSyncs[$type] = [
                    'last_sync' => $latest->sync_completed_at?->toISOString(),
                    'status' => $latest->status,
                    'records_processed' => $latest->records_processed,
                    'records_success' => $latest->records_success,
                    'records_failed' => $latest->records_failed,
                ];
            }
        }

        return response()->json([
            'total_syncs' => $totalSyncs,
            'success_rate' => $successRate,
            'running_syncs' => $runningSyncs,
            'failed_syncs' => $failedSyncs,
            'latest_syncs' => $latestSyncs,
        ]);
    }

    /**
     * Get logs grouped by sync type
     */
    public function byType(): JsonResponse
    {
        $syncTypes = ApiSyncLog::selectRaw('sync_type, count(*) as count, 
                                           sum(case when status = "completed" then 1 else 0 end) as completed,
                                           sum(case when status = "failed" then 1 else 0 end) as failed,
                                           sum(case when status = "running" then 1 else 0 end) as running')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('sync_type')
            ->get();

        return response()->json($syncTypes);
    }

    /**
     * Calculate duration in seconds between start and completion
     */
    private function calculateDuration(ApiSyncLog $log): ?int
    {
        if (!$log->sync_started_at || !$log->sync_completed_at) {
            return null;
        }

        return abs($log->sync_completed_at->diffInSeconds($log->sync_started_at));
    }
}
