<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EquipmentFilter
{
    public function __construct(
        private Request $request
    ) {
        //
    }

    public function __invoke(Builder $query): void
    {
        $query->when($this->request->filled('equipment_number'), function (Builder $q) {
            $q->where('equipment_number', $this->request->equipment_number);
        })->when($this->request->filled('plant_id') && !$this->request->filled('equipment_number'), function (Builder $q) {
            $q->where('plant_id', $this->request->plant_id);
        })->when($this->request->filled('date_start') && $this->request->filled('date_end'), function (Builder $q) {
            $q->whereBetween('created_on', [$this->request->date_start, $this->request->date_end]);
        });
    }
}
