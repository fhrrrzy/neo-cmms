import { h } from 'vue';
import { makeNumberColumn, makeSortableHeader } from '../columns-utils';

function renderValueOrNA(value, extraClass = '') {
  const str = typeof value === 'string' ? value : value ?? '';
  const lowered = String(str).toLowerCase();
  const isEmpty = !str || lowered === '-' || lowered === 'n/a' || lowered === 'no data';
  if (isEmpty) {
    return h('span', { class: `text-muted-foreground ${extraClass}`.trim() }, 'N/A');
  }
  return h('span', { class: extraClass }, String(value));
}

function renderDateYMD(value) {
  if (!value) return renderValueOrNA(value);
  try {
    const d = new Date(value);
    return renderValueOrNA(
      d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
      'font-mono text-sm',
    );
  } catch {
    return renderValueOrNA(value);
  }
}

function renderNumber(value, fractionDigits = 3, extraClass = 'text-right font-mono') {
  const n = Number(value);
  if (!isFinite(n)) return renderValueOrNA(null, extraClass);
  const formatted = new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  }).format(n);
  return h('span', { class: extraClass }, formatted);
}

export const equipmentWorkOrderColumns = [
  makeNumberColumn('No'),
  {
    accessorKey: 'requirements_date',
    header: makeSortableHeader('Tanggal', 'requirements_date'),
    label: 'Tanggal',
    cell: ({ item }) => renderDateYMD(item?.requirements_date),
  },
  {
    accessorKey: 'order_number',
    header: makeSortableHeader('Order', 'order_number'),
    label: 'Order',
    cell: ({ item }) => renderValueOrNA(item?.order_number, 'font-mono text-sm'),
  },
  { key: 'reservation', label: 'Reservation', cell: ({ item }) => renderValueOrNA(item?.reservation, 'font-mono text-sm') },
  { key: 'material', label: 'Material', cell: ({ item }) => renderValueOrNA(item?.material, 'font-mono text-sm') },
  { key: 'material_description', label: 'Material Description', cell: ({ item }) => renderValueOrNA(item?.material_description, 'font-mono text-sm') },
  {
    accessorKey: 'requirement_quantity',
    label: 'Qty',
    align: 'right',
    numeric: true,
    cell: ({ item }) => renderNumber(item?.requirement_quantity, 3),
  },
  { key: 'base_unit_of_measure', label: 'UoM', cell: ({ item }) => renderValueOrNA(item?.base_unit_of_measure) },
  {
    accessorKey: 'quantity_withdrawn',
    label: 'Qty Withdrawn',
    align: 'right',
    numeric: true,
    cell: ({ item }) => renderNumber(item?.quantity_withdrawn, 3),
  },
  {
    accessorKey: 'value_withdrawn',
    label: 'Value',
    align: 'right',
    numeric: true,
    cell: ({ item }) => renderNumber(item?.value_withdrawn, 2),
  },
  { key: 'currency', label: 'Currency', cell: ({ item }) => renderValueOrNA(item?.currency) },
];

export const equipmentWorkOrderGroupedByMaterialColumns = [
  makeNumberColumn('No'),
  { key: 'material', label: 'Material', cell: ({ item }) => renderValueOrNA(item?.material, 'font-mono text-sm') },
  { key: 'material_description', label: 'Material Description', cell: ({ item }) => renderValueOrNA(item?.material_description) },
  { key: 'count', label: 'Jumlah Penggunaan', align: 'right', numeric: true, cell: ({ item }) => renderNumber(item?.count || 0, 0) },
];




