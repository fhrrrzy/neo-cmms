<?php

namespace App\Scopes;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkOrderPaginator
{
    public function __construct(
        private string $sortBy = 'created_on',
        private string $sortDirection = 'desc',
        private int $perPage = 15,
    ) {
        //
    }

    public function __invoke(Builder $query): LengthAwarePaginator
    {
        $allowedSorts = [
            'created_on' => 'created_on',
            'order' => 'order',
            'order_type_label' => 'order_type',
            'order_status_label' => 'order_status',
            'order_type' => 'order_type',
            'order_status' => 'order_status',
        ];

        if (isset($allowedSorts[$this->sortBy])) {
            $query->orderBy($allowedSorts[$this->sortBy], $this->sortDirection);
        } else {
            $query->orderBy('created_on', 'desc');
        }

        return $query->paginate($this->perPage);
    }
}
