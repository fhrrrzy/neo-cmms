import { Button } from '@/components/ui/button';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { h } from 'vue';

export function makeNumberColumn(label = 'No') {
  return {
    id: 'number',
    label,
    header: () => h('span', null, label),
    cell: ({ row, table }) => {
      const pagination = table.options.meta?.pagination;
      if (pagination) {
        const number = (pagination.current_page - 1) * pagination.per_page + row.index + 1;
        return h('div', { class: 'text-center font-medium' }, number);
      }
      return h('div', { class: 'text-center font-medium' }, row.index + 1);
    },
  };
}

export function makeSortableHeader(label, sortKey) {
  return ({ table }) => {
    const sorting = table.options.meta?.sorting;
    const isSorted = sorting?.sort_by === sortKey;
    const isAsc = sorting?.sort_direction === 'asc';
    const Icon = isSorted ? (isAsc ? ArrowUp : ArrowDown) : ArrowUpDown;

    const onClick = () => {
      // 3-state: none -> asc -> desc -> none
      if (!isSorted) {
        table.options.meta?.onSortChange?.(sortKey, 'asc');
      } else if (isAsc) {
        table.options.meta?.onSortChange?.(sortKey, 'desc');
      } else {
        table.options.meta?.onSortChange?.(null, null);
      }
    };

    return h(
      Button,
      { variant: 'ghost', onClick, class: 'h-8 px-2 lg:px-3' },
      () => [label, h(Icon, { class: 'ml-2 h-4 w-4' })],
    );
  };
}


