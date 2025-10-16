<?php

namespace App\Services\Sync\Processors;

use App\Models\Plant;
use App\Models\Station;
use App\Models\WorkOrder;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class WorkOrderProcessor
{
    public function process(array $item): void
    {
        $plantCode = Arr::get($item, 'plant');
        $plant = null;
        if ($plantCode) {
            $plant = Plant::where('plant_code', $plantCode)->first();
        }

        $stationId = null;
        $woCostCenter = Arr::get($item, 'cost_center');
        if ($plant && $woCostCenter) {
            $s = Station::where('plant_id', $plant->id)->where('cost_center', $woCostCenter)->first();
            $stationId = $s?->id;
        }

        WorkOrder::updateOrCreate(
            ['order' => Arr::get($item, 'order')],
            [
                'ims_id' => Arr::get($item, 'id'),
                'mandt' => Arr::get($item, 'mandt'),
                'order_type' => Arr::get($item, 'order_type'),
                'created_on' => Arr::get($item, 'created_on') ? Carbon::parse(Arr::get($item, 'created_on')) : null,
                'change_date_for_order_master' => Arr::get($item, 'change_date_for_order_master') ? Carbon::parse(Arr::get($item, 'change_date_for_order_master')) : null,
                'description' => Arr::get($item, 'description'),
                'company_code' => Arr::get($item, 'company_code'),
                'plant_id' => $plant?->id,
                'plant_code' => $plantCode,
                'station_id' => $stationId,
                'responsible_cctr' => Arr::get($item, 'responsible_cctr'),
                'order_status' => Arr::get($item, 'order_status'),
                'technical_completion' => Arr::get($item, 'technical_completion') ? Carbon::parse(Arr::get($item, 'technical_completion')) : null,
                'cost_center' => Arr::get($item, 'cost_center'),
                'profit_center' => Arr::get($item, 'profit_center'),
                'object_class' => Arr::get($item, 'object_class'),
                'main_work_center' => Arr::get($item, 'main_work_center'),
                'notification' => Arr::get($item, 'notification'),
                'cause' => Arr::get($item, 'cause'),
                'cause_text' => Arr::get($item, 'cause_text'),
                'code_group_problem' => Arr::get($item, 'code_group_problem'),
                'item_text' => Arr::get($item, 'item_text'),
                'created' => Arr::get($item, 'created') ? Carbon::parse(Arr::get($item, 'created')) : null,
                'released' => Arr::get($item, 'released') ? Carbon::parse(Arr::get($item, 'released')) : null,
                'completed' => Arr::get($item, 'completed'),
                'closed' => Arr::get($item, 'closed') ? Carbon::parse(Arr::get($item, 'closed')) : null,
                'planned_release' => Arr::get($item, 'planned_release') ? Carbon::parse(Arr::get($item, 'planned_release')) : null,
                'planned_completion' => Arr::get($item, 'planned_completion') ? Carbon::parse(Arr::get($item, 'planned_completion')) : null,
                'planned_closing_date' => Arr::get($item, 'planned_closing_date') ? Carbon::parse(Arr::get($item, 'planned_closing_date')) : null,
                'release' => Arr::get($item, 'release') ? Carbon::parse(Arr::get($item, 'release')) : null,
                'close' => Arr::get($item, 'close') ? Carbon::parse(Arr::get($item, 'close')) : null,
                'api_updated_at' => Arr::get($item, 'updated_at') ? Carbon::parse(Arr::get($item, 'updated_at')) : null,
                'equipment_number' => Arr::get($item, 'equipment_number'),
                'functional_location' => Arr::get($item, 'functional_location'),
                'functional_location_description' => Arr::get($item, 'functional_location_description'),
                'opertn_task_list_no' => Arr::get($item, 'opertn_task_list_no'),
            ]
        );
    }
}
