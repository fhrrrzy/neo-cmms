<script setup lang="js">
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
    TableBody,
    TableCell,
    TableHead,
    TableRow,
} from '@/components/ui/table';
import {
    getCoreRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { AlertCircle, Factory } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { createColumns } from './columns';
import EquipmentDetailModal from './EquipmentDetailModal.vue';

const props = defineProps({
    data: { type: Array, required: true },
    dates: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
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

const emit = defineEmits(['close']);

const isModalOpen = ref(false);
const selectedPlant = ref(null);
const selectedDate = ref('');

const handleCellClick = (plantUuid, plantName, date) => {
    selectedPlant.value = { uuid: plantUuid, name: plantName };
    selectedDate.value = date;
    isModalOpen.value = true;
};

// Create columns based on all dates
const columns = ref(createColumns(props.dates));

// Sorting state
const tableSorting = ref([]);

// Add original index to data for fixed row numbering
const dataWithOriginalIndex = computed(() => {
    return props.data.map((row, index) => ({
        ...row,
        _originalIndex: index,
    }));
});

// Watch for dates prop changes and regenerate columns
watch(
    () => props.dates,
    (newDates) => {
        columns.value = createColumns(newDates);
    },
    { immediate: true },
);

const table = useVueTable({
    get data() {
        return dataWithOriginalIndex.value;
    },
    get columns() {
        return columns.value;
    },
    onSortingChange: (updaterOrValue) => {
        tableSorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(tableSorting.value)
                : updaterOrValue;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
    state: {
        get sorting() {
            return tableSorting.value;
        },
    },
});

const isLoading = computed(() => props.loading);
const hasError = computed(() => props.error);
const hasData = computed(() => props.data.length > 0);

defineExpose({
    handleCellClick,
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
            <!-- Table -->
            <div class="max-h-[80svh] overflow-auto">
                <table
                    class="w-full table-auto caption-bottom border-collapse text-sm"
                >
                    <thead class="sticky top-0 z-20 bg-background">
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                :class="[
                                    'border-r border-border bg-background',
                                    header.id === 'number' ||
                                    header.id === 'plant_name'
                                        ? 'sticky left-0 z-30 shadow-[2px_0_4px_rgba(0,0,0,0.1)]'
                                        : '',
                                ]"
                            >
                                <div v-if="!header.isPlaceholder">
                                    <component
                                        :is="header.column.columnDef.header"
                                        v-bind="header.getContext()"
                                    />
                                </div>
                            </TableHead>
                        </TableRow>
                    </thead>
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
                                    class="p-8"
                                >
                                    <Empty>
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <Factory />
                                            </EmptyMedia>
                                            <EmptyTitle
                                                >No Plant Data</EmptyTitle
                                            >
                                            <EmptyDescription>
                                                Tidak ada data plant
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                </TableCell>
                            </TableRow>
                        </template>
                        <template v-else>
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                    :class="[
                                        'border-r border-border',
                                        cell.column.id.startsWith('date_')
                                            ? 'group cursor-pointer hover:bg-accent/50'
                                            : '',
                                        cell.column.id === 'number' ||
                                        cell.column.id === 'plant_name'
                                            ? 'sticky left-0 z-10 bg-background shadow-[2px_0_4px_rgba(0,0,0,0.1)]'
                                            : '',
                                    ]"
                                    @click="
                                        cell.column.id.startsWith('date_')
                                            ? handleCellClick(
                                                  row.original.uuid,
                                                  row.original.name,
                                                  cell.column.id.replace(
                                                      'date_',
                                                      '',
                                                  ),
                                              )
                                            : null
                                    "
                                >
                                    <component
                                        :is="cell.column.columnDef.cell"
                                        v-bind="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>
                        </template>
                    </TableBody>
                </table>
            </div>
        </div>

        <!-- Legend -->
        <div
            class="flex flex-wrap items-center gap-3 rounded-md border bg-card p-3 text-xs sm:text-sm"
        >
            <div class="flex items-center gap-2">
                <span
                    class="inline-block h-3 w-3 rounded-full bg-blue-500/80 sm:h-3.5 sm:w-3.5 dark:bg-blue-500/50"
                ></span>
                <span class="text-muted-foreground">Tidak mengolah</span>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="inline-block h-3 w-3 rounded-full bg-red-500/80 sm:h-3.5 sm:w-3.5 dark:bg-red-500/50"
                ></span>
                <span class="text-muted-foreground"
                    >Mengolah tapi tidak ada data</span
                >
            </div>
        </div>

        <!-- Equipment Detail Modal -->
        <EquipmentDetailModal
            :is-open="isModalOpen"
            :plant-uuid="selectedPlant?.uuid"
            :plant-name="selectedPlant?.name || ''"
            :date="selectedDate"
            @close="isModalOpen = false"
        />
    </div>
</template>
