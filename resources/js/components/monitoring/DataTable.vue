<script setup lang="js">
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
import { computed, ref } from 'vue';
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

const tableSorting = ref([]);
const columnFilters = ref([]);
const columnVisibility = ref({});
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
    emit('row-click', equipment);
};

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
            <Table>
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
                            <TableCell v-for="j in 8" :key="j">
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

        <!-- Pagination -->
        <DataTablePagination
            :pagination="pagination"
            @page-change="emit('page-change', $event)"
            @page-size-change="emit('page-size-change', $event)"
        />
    </div>
</template>
