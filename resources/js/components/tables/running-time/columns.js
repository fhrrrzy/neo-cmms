import { Button } from '@/components/ui/button';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';

export const runningTimeColumns = [
    {
        id: 'number',
        header: () => 'No',
        cell: ({ row, table }) => {
            // Get pagination info from table options
            const pagination = table.options.meta?.pagination;
            if (pagination) {
                const number = (pagination.current_page - 1) * pagination.per_page + row.index + 1;
                return h(
                    'div',
                    { class: 'text-center font-medium' },
                    number,
                );
            }
            // Fallback to row index if no pagination info
            return h(
                'div',
                { class: 'text-center font-medium' },
                row.index + 1,
            );
        },
    },
    {
        accessorKey: 'date',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'date';
            const isAsc = sorting?.sort_direction === 'asc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const newDirection = isSorted && isAsc ? 'desc' : 'asc';
                        table.options.meta?.onSortChange?.('date', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Tanggal',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const dateValue = row.getValue('date');
            if (!dateValue) {
                return h('div', { class: 'text-left text-muted-foreground' }, 'N/A');
            }
            const formattedDate = new Date(dateValue).toLocaleDateString(
                'id-ID',
                {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                }
            );
            return h('div', { class: 'text-left font-medium' }, formattedDate);
        },
    },
        {
        accessorKey: 'running_hours',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'running_hours';
            const isAsc = sorting?.sort_direction === 'asc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const newDirection = isSorted && isAsc ? 'desc' : 'asc';
                        table.options.meta?.onSortChange?.('running_hours', newDirection);
                    },
                    class: 'h-8 w-full px-2 lg:px-3 justify-end',
                },
                () => [
                    'Jam Jalan',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const hours = row.getValue('running_hours');
            if (!hours || hours === 0) {
                return h('div', { class: 'text-right text-muted-foreground' }, 'N/A');
            }
            const formatted = Number(hours).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });
            return h('div', { class: 'text-right font-mono' }, `${formatted} Jam`);
        },
    },
    {
        accessorKey: 'counter_reading',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'counter_reading';
            const isAsc = sorting?.sort_direction === 'asc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const newDirection = isSorted && isAsc ? 'desc' : 'asc';
                        table.options.meta?.onSortChange?.('counter_reading', newDirection);
                    },
                    class: 'h-8 w-full px-2 lg:px-3 justify-end',
                },
                () => [
                    'Jam jalan Cummulative',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const reading = row.getValue('counter_reading');
            if (!reading || reading === 0) {
                return h('div', { class: 'text-right text-muted-foreground' }, 'N/A');
            }
            const formatted = Number(reading).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });
            return h('div', { class: 'text-right font-mono' }, formatted);
        },
    },

];


