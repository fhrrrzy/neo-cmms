import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ArrowUpDown, MoreHorizontal } from 'lucide-vue-next';
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
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
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
                { class: 'font-medium' },
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
        accessorKey: 'summed_jam_jalan',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Jam Jalan (Periode)',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('summed_jam_jalan'));
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);
            return h('div', { class: 'text-right font-mono' }, formatted);
        },
    },
    {
        accessorKey: 'running_times_count',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Data Jam Jalan (Periode)',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'text-right' },
                row.getValue('running_times_count'),
            );
        },
    },
    {
        accessorKey: 'is_active',
        header: () => 'Status',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active');
            return h(
                Badge,
                {
                    variant: isActive ? 'default' : 'secondary',
                },
                () => (isActive ? 'Aktif' : 'Nonaktif'),
            );
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const equipment = row.original;

            return h(DropdownMenu, {}, () => [
                h(DropdownMenuTrigger, { asChild: true }, () => [
                    h(
                        Button,
                        { variant: 'ghost', class: 'h-8 w-8 p-0' },
                        () => [
                            h('span', { class: 'sr-only' }, 'Open menu'),
                            h(MoreHorizontal, { class: 'h-4 w-4' }),
                        ],
                    ),
                ]),
                h(DropdownMenuContent, { align: 'end' }, () => [
                    h(DropdownMenuLabel, {}, 'Actions'),
                    h(
                        DropdownMenuItem,
                        {
                            onClick: () =>
                                navigator.clipboard.writeText(
                                    equipment.equipment_number,
                                ),
                        },
                        'Copy equipment number',
                    ),
                    h(DropdownMenuSeparator),
                    h(DropdownMenuItem, {}, 'View details'),
                    h(DropdownMenuItem, {}, 'Edit equipment'),
                ]),
            ]);
        },
    },
];
