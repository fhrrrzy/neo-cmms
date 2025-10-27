<script setup>
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { AlertCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { columns } from './columns';
import DataTablePagination from './DataTablePagination.vue';

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
    pagination: {
        type: Object,
        default: () => ({
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            has_more_pages: false,
        }),
    },
    sorting: {
        type: Object,
        default: () => ({
            sort_by: 'equipment_number',
            sort_direction: 'asc',
        }),
    },
});

const emit = defineEmits([
    'page-change',
    'page-size-change',
    'sort-change',
    'row-click',
]);

// Sheet state management
const isSheetOpen = ref(false);
const selectedEquipment = ref(null);

// Initialize sorting state from props
const tableSorting = ref([
    {
        id: props.sorting.sort_by,
        desc: props.sorting.sort_direction === 'desc',
    },
]);
const columnFilters = ref([]);
// Load column visibility from localStorage on initialization
const getInitialColumnVisibility = () => {
    const savedVisibility = localStorage.getItem(
        'monitoring_table_column_visibility',
    );
    if (savedVisibility) {
        try {
            return JSON.parse(savedVisibility);
        } catch (error) {
            console.warn('Failed to parse saved column visibility:', error);
        }
    }
    return {};
};
const columnVisibility = ref(getInitialColumnVisibility());
const rowSelection = ref({});

const table = useVueTable({
    get data() {
        return props.data;
    },
    columns,
    onSortingChange: (updaterOrValue) => {
        tableSorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(tableSorting.value)
                : updaterOrValue;

        // Emit sort change to parent component
        if (tableSorting.value.length > 0) {
            const sort = tableSorting.value[0];
            const sortDirection = sort.desc ? 'desc' : 'asc';
            emit('sort-change', sort.id, sortDirection);
        } else {
            // No sorting - emit null to remove sorting
            emit('sort-change', null, null);
        }
    },
    onColumnFiltersChange: (updaterOrValue) => {
        columnFilters.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnFilters.value)
                : updaterOrValue;
    },
    onColumnVisibilityChange: (updaterOrValue) => {
        columnVisibility.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnVisibility.value)
                : updaterOrValue;
    },
    onRowSelectionChange: (updaterOrValue) => {
        rowSelection.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(rowSelection.value)
                : updaterOrValue;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    meta: {
        get pagination() {
            return props.pagination;
        },
        get sorting() {
            return props.sorting;
        },
        onSortChange: (sortBy, sortDirection) => {
            emit('sort-change', sortBy, sortDirection);
        },
    },
    state: {
        get sorting() {
            return tableSorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
});

const isLoading = computed(() => props.loading);
const hasError = computed(() => props.error);
const hasData = computed(() => props.data.length > 0);

const handleRowClick = (equipment) => {
    selectedEquipment.value = equipment;
    isSheetOpen.value = true;
    // Still emit for backward compatibility if needed
    emit('row-click', equipment);
};

// Watch for sorting changes from parent and update table sorting
watch(
    () => props.sorting,
    (newSorting) => {
        if (newSorting.sort_by && newSorting.sort_direction) {
            tableSorting.value = [
                {
                    id: newSorting.sort_by,
                    desc: newSorting.sort_direction === 'desc',
                },
            ];
        } else {
            // No sorting
            tableSorting.value = [];
        }
    },
    { deep: true },
);

// Expose table instance for parent components
defineExpose({
    table,
});
</script>

<template>
    <div class="space-y-4">
        <!-- Error Alert -->
        <Alert v-if="hasError" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <!-- Table -->
        <div class="rounded-md border">
            <!-- Desktop Table -->
            <div class="hidden md:block">
                <Table class="w-full table-auto">
                    <TableHeader>
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                            >
                                <div v-if="!header.isPlaceholder">
                                    <component
                                        :is="header.column.columnDef.header"
                                        v-bind="header.getContext()"
                                    />
                                </div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="isLoading">
                            <TableRow v-for="i in 15" :key="i">
                                <TableCell v-for="j in 10" :key="j">
                                    <Skeleton class="h-4 w-full" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else-if="!hasData">
                            <TableRow>
                                <TableCell
                                    :colspan="table.getAllColumns().length"
                                    class="h-24 text-center"
                                >
                                    Tidak ada data equipment yang ditemukan
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                                :data-state="row.getIsSelected() && 'selected'"
                                class="cursor-pointer hover:bg-muted/50"
                                @click="handleRowClick(row.original)"
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                >
                                    <component
                                        :is="cell.column.columnDef.cell"
                                        v-bind="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </Table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden">
                <template v-if="isLoading">
                    <div v-for="i in 5" :key="i" class="border-b p-4">
                        <div class="space-y-2">
                            <Skeleton class="h-4 w-3/4" />
                            <Skeleton class="h-3 w-1/2" />
                            <Skeleton class="h-3 w-2/3" />
                        </div>
                    </div>
                </template>
                <template v-else-if="!hasData">
                    <div class="p-8 text-center text-muted-foreground">
                        <p>Tidak ada data equipment yang ditemukan</p>
                    </div>
                </template>
                <template v-else>
                    <div
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="cursor-pointer border-b p-4 hover:bg-muted/50"
                        @click="handleRowClick(row.original)"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="font-mono font-medium">
                                    {{ row.original.equipment_number }}
                                </h3>
                                <span
                                    v-if="row.original.equipment_type"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800':
                                            row.original.equipment_type ===
                                            'Mesin Produksi',
                                        'bg-sky-100 text-sky-800':
                                            row.original.equipment_type ===
                                            'Kendaraan',
                                        'bg-amber-100 text-amber-800':
                                            row.original.equipment_type ===
                                            'Alat dan Utilitas',
                                        'bg-violet-100 text-violet-800':
                                            row.original.equipment_type ===
                                            'IT & Telekomunikasi',
                                        'bg-rose-100 text-rose-800':
                                            row.original.equipment_type ===
                                            'Aset PMN',
                                        'bg-muted text-foreground': ![
                                            'Mesin Produksi',
                                            'Kendaraan',
                                            'Alat dan Utilitas',
                                            'IT & Telekomunikasi',
                                            'Aset PMN',
                                        ].includes(row.original.equipment_type),
                                    }"
                                >
                                    {{ row.original.equipment_type }}
                                </span>
                            </div>
                            <p class="truncate text-sm text-muted-foreground">
                                {{
                                    row.original.equipment_description || 'N/A'
                                }}
                            </p>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-muted-foreground"
                                        >Pabrik:</span
                                    >
                                    <p>
                                        {{ row.original.plant?.name || 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground"
                                        >Stasiun:</span
                                    >
                                    <p>
                                        {{
                                            row.original.station?.description ||
                                            'N/A'
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-muted-foreground"
                                        >Jam Jalan:</span
                                    >
                                    <p class="font-mono">
                                        {{
                                            row.original.cumulative_jam_jalan
                                                ? new Intl.NumberFormat(
                                                      'id-ID',
                                                      {
                                                          minimumFractionDigits: 2,
                                                          maximumFractionDigits: 2,
                                                      },
                                                  ).format(
                                                      row.original
                                                          .cumulative_jam_jalan,
                                                  ) + ' Jam'
                                                : 'N/A'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground"
                                        >Total Periode:</span
                                    >
                                    <p class="font-mono">
                                        {{
                                            row.original.running_times_count &&
                                            row.original.running_times_count > 0
                                                ? new Intl.NumberFormat(
                                                      'id-ID',
                                                      {
                                                          minimumFractionDigits: 2,
                                                          maximumFractionDigits: 2,
                                                      },
                                                  ).format(
                                                      row.original
                                                          .running_times_count,
                                                  ) + ' Jam'
                                                : 'N/A'
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-muted-foreground"
                                        >Biaya Periode:</span
                                    >
                                    <p
                                        class="font-mono font-semibold text-primary"
                                    >
                                        {{
                                            row.original.biaya
                                                ? new Intl.NumberFormat(
                                                      'id-ID',
                                                      {
                                                          style: 'currency',
                                                          currency: 'IDR',
                                                          minimumFractionDigits: 0,
                                                          maximumFractionDigits: 0,
                                                      },
                                                  ).format(row.original.biaya)
                                                : 'N/A'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-muted-foreground"
                                        >Fungsional Lokasi:</span
                                    >
                                    <p class="truncate">
                                        {{
                                            row.original.functional_location ||
                                            'N/A'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Pagination -->
        <DataTablePagination
            :pagination="pagination"
            @page-change="emit('page-change', $event)"
            @page-size-change="emit('page-size-change', $event)"
        />

        <!-- Equipment Detail Sheet -->
        <EquipmentDetailSheet
            :is-open="isSheetOpen"
            :equipment-number="selectedEquipment?.equipment_number || ''"
            @close="isSheetOpen = false"
        />
    </div>
</template>
