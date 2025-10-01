import { Button } from '@/components/ui/button';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';

export const columns = [
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
        accessorKey: 'equipment_number',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'equipment_number';
            const isAsc = sorting?.sort_direction === 'asc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const newDirection = isSorted && isAsc ? 'desc' : 'asc';
                        table.options.meta?.onSortChange?.('equipment_number', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Nomor Equipment',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'font-medium font-mono text-left px-4' },
                row.getValue('equipment_number'),
            );
        },
    },
    {
        accessorKey: 'plant.name',
        header: () => 'Pabrik',
        cell: ({ row }) => {
            const plant = row.original.plant;
            return h('div', plant?.name || '-');
        },
    },
    {
        accessorKey: 'station.description',
        header: () => 'Stasiun',
        cell: ({ row }) => {
            const station = row.original.station;
            return h('div', station?.description || '-');
        },
    },
    {
        accessorKey: 'equipment_description',
        header: () => 'Nama Equipment',
        cell: ({ row }) => {
            const description = row.getValue('equipment_description');
            return h(
                'div',
                {
                    class: 'max-w-[200px] truncate',
                    title: description,
                },
                description || '-',
            );
        },
    },
    {
        accessorKey: 'cumulative_jam_jalan',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'cumulative_jam_jalan';
            const isAsc = sorting?.sort_direction === 'asc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const newDirection = isSorted && isAsc ? 'desc' : 'asc';
                        table.options.meta?.onSortChange?.('cumulative_jam_jalan', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Jam Jalan Cummulative',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('cumulative_jam_jalan'));
            if (amount === 0) {
                return h('div', { class: 'text-right' }, 'No data');
            }
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);
            return h('div', { class: 'text-right font-mono' }, formatted);
        },
    },
    {
        accessorKey: 'running_times_count',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'running_times_count';
            const isAsc = sorting?.sort_direction === 'asc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const newDirection = isSorted && isAsc ? 'desc' : 'asc';
                        table.options.meta?.onSortChange?.('running_times_count', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3 text-right',
                },
                () => [
                    'Data Jam Jalan (Periode)',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const count = row.getValue('running_times_count');
            return h(
                'div',
                { class: 'text-right' },
                count > 0 ? count : 'No data',
            );
        },
    },
];
