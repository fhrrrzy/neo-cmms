<?php

namespace App\Console\Commands;

use App\Models\ApiSyncLog;
use App\Models\Equipment;
use App\Models\EquipmentRunningTime;
use App\Models\Plant;
use App\Services\ImsClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SyncRunningTime extends Command
{
    protected $signature = 'sync:running-time {--date= : YYYY-MM-DD date to sync, defaults to today}';

    protected $description = 'Sync equipment running time (jam jalan) from IMS API into local database';

    public function handle(ImsClient $client): int
    {
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        $start = $date;
        $end = $date;

        $log = ApiSyncLog::start(ApiSyncLog::SYNC_TYPE_RUNNING_TIME);

        try {
            $items = $client->getRunningTime($start, $end);

            $processed = 0;
            $success = 0;
            $failed = 0;

            DB::transaction(function () use ($items, &$processed, &$success, &$failed) {
                foreach ($items as $item) {
                    $processed++;
                    try {
                        $equipmentNumber = (string) Arr::get($item, 'equipment_number');
                        $equipment = Equipment::where('equipment_number', $equipmentNumber)->first();

                        if (!$equipment) {
                            throw new \RuntimeException('Equipment not found: ' . $equipmentNumber);
                        }

                        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code');
                        $plantId = $equipment->plant_id ?? Plant::where('plant_code', $plantCode)->value('id');

                        EquipmentRunningTime::updateOrCreate(
                            [
                                'equipment_id' => $equipment->id,
                                'date' => Arr::get($item, 'date') ?? Arr::get($item, 'tanggal'),
                            ],
                            [
                                'plant_id' => $plantId,
                                'point' => Arr::get($item, 'point'),
                                'date_time' => Arr::get($item, 'date_time') ?? Arr::get($item, 'waktu'),
                                'description' => Arr::get($item, 'description'),
                                'running_hours' => Arr::get($item, 'running_hours') ?? Arr::get($item, 'jam_berjalan'),
                                'cumulative_hours' => Arr::get($item, 'cumulative_hours') ?? Arr::get($item, 'jam_kumulatif'),
                                'company_code' => Arr::get($item, 'company_code'),
                                'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'deskripsi_equipment'),
                                'object_number' => Arr::get($item, 'object_number'),
                                'api_created_at' => Arr::get($item, 'api_created_at') ? Carbon::parse(Arr::get($item, 'api_created_at')) : null,
                            ]
                        );

                        $success++;
                    } catch (\Throwable $e) {
                        $failed++;
                        report($e);
                    }
                }
            });

            $log->finishSuccess($processed, $success, $failed);

            $this->info("Running time synced: processed={$processed}, success={$success}, failed={$failed}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $log->finishFailed($e->getMessage());
            report($e);
            $this->error('Running time sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
