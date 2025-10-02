<script setup>
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from 'axios';
import { h, onMounted, ref, watch } from 'vue';
import { workOrderColumns } from './columns';

const props = defineProps({
    plantId: { type: [String, Number], required: true },
    dateRange: { type: Object, required: true },
    // Tailwind class for max height; default to 80vh
    maxHeightClass: { type: String, default: 'max-h-[80vh]' },
});

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const perPage = ref(15);
const sortBy = ref('created_on');
const sortDirection = ref('desc');

const pagination = ref({
    total: 0,
    current_page: 1,
    per_page: 15,
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

const renderOrNA = (value) => {
    const s = value ?? '';
    const lowered = String(s).toLowerCase();
    if (!s || lowered === '-' || lowered === 'no data' || lowered === 'n/a')
        return 'N/A';
    return String(value);
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (props.plantId) params.append('plant_id', String(props.plantId));
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        params.append('page', String(page.value));
        params.append('per_page', String(perPage.value));
        params.append('sort_by', sortBy.value);
        params.append('sort_direction', sortDirection.value);
        const { data } = await axios.get(`/api/workorders?${params}`);
        rows.value = data?.data || [];
        pagination.value = {
            total: data?.total ?? 0,
            per_page: data?.per_page ?? perPage.value,
            current_page: data?.current_page ?? page.value,
            last_page: data?.last_page ?? 1,
        };
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(
    () => [props.plantId, props.dateRange?.start, props.dateRange?.end],
    fetchData,
);
</script>

<template>
    <div v-if="rows?.length > 0">
        <ScrollArea :class="['w-full', props.maxHeightClass]">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="col in workOrderColumns"
                            :key="col.id || col.accessorKey || col.key"
                        >
                            <component
                                :is="
                                    typeof col.header === 'function'
                                        ? col.header({
                                              table: tableContext,
                                              column: col,
                                          })
                                        : h('span', null, col.label)
                                "
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(wo, idx) in rows" :key="wo.id">
                        <TableCell class="text-center font-medium">
                            {{
                                (pagination.current_page - 1) *
                                    pagination.per_page +
                                idx +
                                1
                            }}
                        </TableCell>
                        <TableCell class="font-mono text-sm">
                            {{
                                renderOrNA(
                                    new Date(wo.created_on).toLocaleDateString(
                                        'en-US',
                                        {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                        },
                                    ),
                                )
                            }}
                        </TableCell>
                        <TableCell class="font-mono text-sm">{{
                            renderOrNA(wo.order)
                        }}</TableCell>
                        <TableCell>{{
                            renderOrNA(wo.order_type_label)
                        }}</TableCell>
                        <TableCell>{{
                            renderOrNA(wo.order_status_label)
                        }}</TableCell>
                        <TableCell
                            class="max-w-[320px] truncate"
                            :title="wo.description"
                        >
                            {{ renderOrNA(wo.description) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No work orders found</p>
    </div>
</template>
