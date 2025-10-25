<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WorkOrderSearchFilter
{
    public function __construct(
        private Request $request
    ) {
        //
    }

    public function __invoke(Builder $query): void
    {
        $query->when($this->request->filled('search'), function (Builder $q) {
            $term = '%' . $this->request->search . '%';
            $q->whereAny([
                'order',
                'description',
                'cause_text',
                'item_text',
            ], 'like', $term);
        })->when($this->request->filled('order_type'), function (Builder $q) {
            $ot = (string) $this->request->order_type;
            $map = [
                '1' => 'PM01',
                '2' => 'PM02',
                '3' => 'PM03',
                '4' => 'PM04',
            ];
            if (strtoupper($ot) === 'ANOMALY') {
                $q->whereNotIn('order_type', array_values($map));
            } else {
                $q->where('order_type', $map[$ot] ?? $ot);
            }
        });
    }
}
