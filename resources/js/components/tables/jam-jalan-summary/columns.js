import { h } from 'vue';
import { Button } from '@/components/ui/button';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { formatDate, getCellClass } from './columns-utils';

let cellClickHandler = null;

// Set the cell click handler from outside
export const setCellClickHandler = (handler) => {
    cellClickHandler = handler;
};

// Column definitions for Jam Jalan Summary table
// This creates a dynamic column structure for plants with date columns
export const createColumns = (dates) => {
    const columns = [
        {
            id: 'number',
            enableSorting: false,
            header: () => h('div', { class: 'text-center font-medium w-full' }, 'No'),
            cell: ({ row }) => {
                // Store original index before any sorting
                const originalIndex = row.original._originalIndex ?? row.index;
                return h(
                    'div',
                    { class: 'text-center font-medium w-full' },
                    originalIndex + 1,
                );
            },
        },
        {
            id: 'plant_name',
            accessorKey: 'name',
            enableSorting: true,
            header: ({ column }) => {
                const sortState = column.getIsSorted();
                const isSorted = sortState !== false;
                const isAsc = sortState === 'asc';
                const isDesc = sortState === 'desc';
                
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
                                newDirection = false;
                            }
                            column.toggleSorting(newDirection, false);
                        },
                        class: 'h-8 px-2',
                    },
                    () => [
                        'Plant',
                        isSorted 
                            ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-2 h-4 w-4' })
                            : h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                    ]
                );
            },
            cell: ({ getValue }) => {
                const value = getValue();
                return h(
                    'div',
                    { class: 'font-medium w-full' },
                    value,
                );
            },
        },
    ];

    // Add date columns dynamically
    dates.forEach((date) => {
        const formattedDate = formatDate(date);
        columns.push({
            id: `date_${date}`,
            accessorKey: `dates.${date}.count`,
            enableSorting: true,
            header: ({ column }) => {
                const sortState = column.getIsSorted();
                const isSorted = sortState !== false;
                const isAsc = sortState === 'asc';
                const isDesc = sortState === 'desc';
                
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
                                newDirection = false;
                            }
                            column.toggleSorting(newDirection, false);
                        },
                        class: 'h-8 px-2 w-full',
                    },
                    () => [
                        h('div', { class: 'text-center text-xs' }, formattedDate),
                        isSorted 
                            ? h(isAsc ? ArrowUp : ArrowDown, { class: 'ml-1 h-3 w-3' })
                            : h(ArrowUpDown, { class: 'ml-1 h-3 w-3' }),
                    ]
                );
            },
            cell: ({ row }) => {
                const plantData = row.original;
                const dateData = plantData.dates?.[date] || { count: 0, is_mengolah: false };
                // Convert to boolean if needed (handle both boolean and integer values)
                const isMengolah = Boolean(dateData.is_mengolah);
                const cellClass = getCellClass(isMengolah, dateData.count);
                
                return h(
                    'div',
                    { 
                        class: `rounded py-1 text-center pointer-events-none ${cellClass} group`,
                    },
                    String(dateData.count ?? 0)
                );
            },
        });
    });

    return columns;
};

// Default export for compatibility
export const columns = [];

