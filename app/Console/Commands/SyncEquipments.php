<?php

namespace App\Console\Commands;

use App\Models\ApiSyncLog;
use App\Models\Equipment;
use App\Models\EquipmentGroup;
use App\Models\Plant;
use App\Services\ImsClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SyncEquipments extends Command
{
    protected $signature = 'sync:equipments';

    protected $description = 'Sync equipments from IMS API into local database';

    public function handle(ImsClient $client): int
    {
        $log = ApiSyncLog::start(ApiSyncLog::SYNC_TYPE_EQUIPMENT);

        try {
            $items = $client->getEquipments();

            $processed = 0;
            $success = 0;
            $failed = 0;

            DB::transaction(function () use ($items, &$processed, &$success, &$failed) {
                foreach ($items as $item) {
                    $processed++;
                    try {
                        $plantCode = Arr::get($item, 'plant_id') ?? Arr::get($item, 'plant_code');
                        $plant = Plant::where('plant_code', $plantCode)->first();

                        $groupName = trim((string) (Arr::get($item, 'group_name') ?? Arr::get($item, 'equipment_group')));
                        $equipmentGroup = null;
                        if ($groupName !== '') {
                            $equipmentGroup = EquipmentGroup::firstOrCreate(['name' => $groupName], [
                                'description' => null,
                                'is_active' => true,
                            ]);
                        }

                        Equipment::updateOrCreate(
                            ['equipment_number' => Arr::get($item, 'equipment_number')],
                            [
                                'plant_id' => $plant?->id,
                                'equipment_group_id' => $equipmentGroup?->id,
                                'company_code' => Arr::get($item, 'company_code'),
                                'equipment_description' => Arr::get($item, 'equipment_description') ?? Arr::get($item, 'description'),
                                'object_number' => Arr::get($item, 'object_number'),
                                'point' => Arr::get($item, 'point'),
                                'api_created_at' => Arr::get($item, 'api_created_at') ? Carbon::parse(Arr::get($item, 'api_created_at')) : null,
                                'is_active' => true,
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

            $this->info("Equipments synced: processed={$processed}, success={$success}, failed={$failed}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $log->finishFailed($e->getMessage());
            report($e);
            $this->error('Equipment sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
