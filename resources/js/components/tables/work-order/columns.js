import { makeNumberColumn, makeSortableHeader } from '../columns-utils';

export const workOrderColumns = [
  makeNumberColumn('No'),
  {
    accessorKey: 'created_on',
    header: makeSortableHeader('Tanggal', 'created_on'),
    label: 'Tanggal',
  },
  {
    accessorKey: 'order',
    header: makeSortableHeader('Order', 'order'),
    label: 'Order',
  },
  {
    accessorKey: 'order_type_label',
    header: makeSortableHeader('Jenis', 'order_type_label'),
    label: 'Jenis',
  },
  {
    accessorKey: 'description',
    label: 'Deskripsi',
  },
  {
    accessorKey: 'cause_text',
    label: 'Cause',
  },
  {
    accessorKey: 'item_text',
    label: 'Item',
  },
];


