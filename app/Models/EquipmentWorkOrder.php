<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentWorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'ims_id',
        'reservation',
        'requirement_type',
        'reservation_status',
        'item_deleted',
        'movement_allowed',
        'final_issue',
        'missing_part',
        'material',
        'plant_id',
        'storage_location',
        'requirements_date',
        'requirement_quantity',
        'base_unit_of_measure',
        'debit_credit_ind',
        'quantity_is_fixed',
        'quantity_withdrawn',
        'value_withdrawn',
        'currency',
        'qty_in_unit_of_entry',
        'unit_of_entry',
        'movement_type',
        'gl_account',
        'receiving_plant',
        'receiving_storage_location',
        'qty_for_avail_check',
        'goods_recipient',
        'material_group',
        'acct_manually',
        'commitment_item_1',
        'funds_center',
        'start_time',
        'end_time',
        'service_duration',
        'service_dur_unit',
        'api_updated_at',
        'commitment_item_2',
        'order_number',
        'equipment_number',
    ];
}
