import { h } from 'vue';
import { makeNumberColumn, makeSortableHeader } from '../columns-utils';

export const equipmentWorkOrderColumns = [
  makeNumberColumn('No'),
  {
    accessorKey: 'requirements_date',
    header: makeSortableHeader('Tanggal', 'requirements_date'),
    label: 'Tanggal',
  },
  {
    accessorKey: 'order_number',
    header: makeSortableHeader('Order', 'order_number'),
    label: 'Order',
  },
  { key: 'reservation', label: 'Reservation' },
  { key: 'material', label: 'Material' },
  { key: 'requirement_quantity', label: 'Qty', align: 'right', numeric: true },
  { key: 'base_unit_of_measure', label: 'UoM' },
  { key: 'quantity_withdrawn', label: 'Qty Withdrawn', align: 'right', numeric: true },
  { key: 'value_withdrawn', label: 'Value', align: 'right', numeric: true },
  { key: 'currency', label: 'Currency' },
];




