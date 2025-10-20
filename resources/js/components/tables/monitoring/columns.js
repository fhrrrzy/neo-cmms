import { Button } from '@/components/ui/button';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { h } from 'vue';

function renderValueOrNA(value, extraClass = '') {
    const str = typeof value === 'string' ? value : value ?? '';
    const lowered = String(str).toLowerCase();
    const isEmpty = !str || lowered === '-' || lowered === 'n/a' || lowered === 'no data';
    if (isEmpty) {
        return h('span', { class: `text-muted-foreground ${extraClass}`.trim() }, 'N/A');
    }
    return h('span', { class: extraClass }, value);
}

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
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('equipment_number', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Nomor Equipment',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
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
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'plant.name';
            const isAsc = sorting?.sort_direction === 'asc';
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('plant.name', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Pabrik',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const plant = row.original.plant;
            return h('div', null, renderValueOrNA(plant?.name));
        },
    },
    {
        accessorKey: 'station.description',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'station.description';
            const isAsc = sorting?.sort_direction === 'asc';
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('station.description', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Stasiun',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const station = row.original.station;
            return h('div', null, renderValueOrNA(station?.description));
        },
    },
    {
        accessorKey: 'equipment_description',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'equipment_description';
            const isAsc = sorting?.sort_direction === 'asc';
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('equipment_description', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Nama Equipment',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const description = row.getValue('equipment_description');
            const content = renderValueOrNA(description, 'max-w-[200px] truncate');
            content.props = { ...(content.props || {}), title: description };
            return content;
        },
    },
    {
        accessorKey: 'equipment_type',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'equipment_type';
            const isAsc = sorting?.sort_direction === 'asc';
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('equipment_type', newDirection);
                    },
                    class: 'h-8 px-2 lg:px-3',
                },
                () => [
                    'Tipe',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const type = row.getValue('equipment_type');
            const colorMap = {
                'Mesin Produksi': 'bg-emerald-100 text-emerald-800',
                'Kendaraan': 'bg-sky-100 text-sky-800',
                'Alat dan Utilitas': 'bg-amber-100 text-amber-800',
                'IT & Telekomunikasi': 'bg-violet-100 text-violet-800',
                'Aset PMN': 'bg-rose-100 text-rose-800',
            };
            const cls = colorMap[type] || 'bg-muted text-foreground';
            if (!type) return renderValueOrNA(type);
            return h('span', { class: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${cls}` }, type);
        },
    },
        {
        accessorKey: 'running_times_count',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'running_times_count';
            const isAsc = sorting?.sort_direction === 'asc';
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('running_times_count', newDirection);
                    },
                    class: 'h-8 w-full px-2 lg:px-3 justify-end',
                },
                () => [
                    'Total Jam Jalan (Periode)',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const totalHours = Number(row.getValue('running_times_count'));
            if (!totalHours || totalHours <= 0) {
                return h('div', { class: 'text-right text-muted-foreground' }, 'N/A');
            }
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(totalHours);
            return h('div', { class: 'text-right font-mono' }, `${formatted} Jam`);
        },
    },
    {
        accessorKey: 'cumulative_jam_jalan',
        header: ({ column, table }) => {
            const sorting = table.options.meta?.sorting;
            const isSorted = sorting?.sort_by === 'cumulative_jam_jalan';
            const isAsc = sorting?.sort_direction === 'asc';
            const isDesc = sorting?.sort_direction === 'desc';
            
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        let newDirection;
                        if (!isSorted) {
                            newDirection = 'asc';
                        } else if (isAsc) {
                            newDirection = 'desc';
                        } else if (isDesc) {
                            newDirection = null; // Remove sorting
                        }
                        table.options.meta?.onSortChange?.('cumulative_jam_jalan', newDirection);
                    },
                    class: 'h-8 w-full px-2 lg:px-3 justify-end',
                },
                () => [
                    'Jam Jalan Cummulative',
                    isSorted 
                        ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                        : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('cumulative_jam_jalan'));
            if (!amount || amount === 0) {
                return h('div', { class: 'text-right text-muted-foreground' }, 'N/A');
            }
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);
            return h('div', { class: 'text-right font-mono' }, `${formatted} Jam`);
        },
    },

];
