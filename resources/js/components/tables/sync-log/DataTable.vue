<script setup>
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
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
    useVueTable,
} from '@tanstack/vue-table';
import { AlertCircle, Database } from 'lucide-vue-next';
import { computed } from 'vue';
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
});

const emit = defineEmits(['page-change', 'page-size-change']);

const table = useVueTable({
    get data() {
        return props.data;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    meta: {
        get pagination() {
            return props.pagination;
        },
    },
});

const isLoading = computed(() => props.loading);
const hasError = computed(() => props.error);
const hasData = computed(() => props.data.length > 0);
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
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <div v-if="!header.isPlaceholder">
                                <component :is="header.column.columnDef.header" v-bind="header.getContext()" />
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="isLoading">
                        <TableRow v-for="i in 15" :key="i">
                            <TableCell v-for="j in 9" :key="j">
                                <Skeleton class="h-4 w-full" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else-if="!hasData">
                        <TableRow>
                            <TableCell :colspan="table.getAllColumns().length" class="p-8">
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <Database />
                                        </EmptyMedia>
                                        <EmptyTitle>No Sync Logs Found</EmptyTitle>
                                        <EmptyDescription>
                                            No sync logs found for the selected filters
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <component :is="cell.column.columnDef.cell" v-bind="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <DataTablePagination :pagination="pagination" @page-change="emit('page-change', $event)"
            @page-size-change="emit('page-size-change', $event)" />
    </div>
</template>
