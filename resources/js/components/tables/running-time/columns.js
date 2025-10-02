import { h } from 'vue';
import { makeNumberColumn, makeSortableHeader } from '../columns-utils';

export const runningTimeColumns = [
  makeNumberColumn('No'),
  {
    accessorKey: 'date',
    header: makeSortableHeader('Date', 'date'),
    cell: ({ row }) => h('div', { class: 'text-left' }, row.getValue('date')),
  },
  {
    accessorKey: 'counter_reading',
    align: 'right',
    header: makeSortableHeader('Counter Reading', 'counter_reading'),
    cell: ({ row }) => h('div', { class: 'text-right font-mono' }, `${row.getValue('counter_reading')} ${row.getValue('counter_reading_unit')}`),
  },
  {
    accessorKey: 'running_hours',
    align: 'right',
    header: makeSortableHeader('Running Hours', 'running_hours'),
    cell: ({ row }) => h('div', { class: 'text-right font-mono' }, `${row.getValue('running_hours')} Jam`),
  },
];


