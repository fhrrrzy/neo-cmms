<script setup>
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from 'axios';
import { Clock } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import { runningTimeColumns } from './columns';
import RunningTimePagination from './RunningTimePagination.vue';

const props = defineProps({
    equipmentUuid: { type: String, required: false },
    equipmentNumber: { type: String, required: false },
    dateRange: {
        type: Object,
        required: true,
    },
});

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const perPage = ref(10);
const sortBy = ref('date');
const sortDirection = ref('desc');

const pagination = ref({
    total: 0,
    current_page: 1,
    per_page: 10,
    last_page: 1,
});

const tableContext = {
    options: {
        meta: {
            get pagination() {
                return pagination.value;
            },
            get sorting() {
                return {
                    sort_by: sortBy.value,
                    sort_direction: sortDirection.value,
                };
            },
            onSortChange: (by, direction) => {
                sortBy.value = by;
                sortDirection.value = direction;
                page.value = 1;
                fetchData();
            },
        },
    },
};
// No inline style to keep Tailwind-based cn working

const fetchData = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        params.append('rt_page', String(page.value));
        params.append('rt_per_page', String(perPage.value));
        if (sortBy.value && sortDirection.value) {
            params.append('rt_sort_by', sortBy.value);
            params.append('rt_sort_direction', sortDirection.value);
        }
        // Use UUID if available, fallback to equipment number
        const equipmentId = props.equipmentUuid || props.equipmentNumber;
        if (!equipmentId) {
            console.error('Equipment UUID or number is required');
            rows.value = [];
            loading.value = false;
            return;
        }
        const { data } = await axios.get(
            `/api/equipment/${equipmentId}?${params}`,
        );
        rows.value = data?.equipment?.recent_running_times || [];
        const meta = data?.equipment?.running_times_pagination;
        if (meta) {
            pagination.value = {
                total: meta.total ?? 0,
                per_page: meta.per_page ?? perPage.value,
                current_page: meta.current_page ?? page.value,
                last_page: meta.last_page ?? 1,
            };
        }
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (newPage) => {
    page.value = newPage;
    fetchData();
};

const handlePageSizeChange = (perPageValue) => {
    perPage.value = perPageValue;
    page.value = 1; // Reset to first page when changing page size
    fetchData();
};

onMounted(fetchData);
watch(
    () => [
        props.equipmentUuid,
        props.equipmentNumber,
        props.dateRange?.start,
        props.dateRange?.end,
    ],
    fetchData,
);
</script>

<template>
    <div class="w-full">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead
                        v-for="col in runningTimeColumns"
                        :key="col.id || col.accessorKey || col.key"
                    >
                        <div>
                            <component
                                :is="col.header"
                                v-bind="{ table: tableContext, column: col }"
                            />
                        </div>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="rows.length === 0">
                    <TableCell :colspan="runningTimeColumns.length" class="p-8">
                        <Empty>
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <Clock />
                                </EmptyMedia>
                                <EmptyTitle>No Running Times</EmptyTitle>
                                <EmptyDescription>
                                    No running times data available
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    </TableCell>
                </TableRow>
                <TableRow v-else v-for="(time, index) in rows" :key="index">
                    <TableCell
                        v-for="col in runningTimeColumns"
                        :key="col.id || col.accessorKey || col.key"
                    >
                        <component
                            :is="col.cell"
                            v-bind="{
                                row: {
                                    getValue: (key) => time[key],
                                    index: index,
                                    original: time,
                                },
                                table: tableContext,
                            }"
                        />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <!-- Pagination -->
        <div class="mt-4">
            <RunningTimePagination
                :pagination="pagination"
                @page-change="handlePageChange"
                @page-size-change="handlePageSizeChange"
            />
        </div>
    </div>
</template>
