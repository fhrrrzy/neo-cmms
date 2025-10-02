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
import { equipmentWorkOrderColumns } from './columns';

const props = defineProps({
    equipmentNumber: { type: String, required: true },
    dateRange: { type: Object, required: true },
    maxHeightClass: { type: String, default: 'max-h-[80vh]' },
});

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const perPage = ref(15);
const sortBy = ref('requirements_date');
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
        params.append('equipment_number', props.equipmentNumber);
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        params.append('page', String(page.value));
        params.append('per_page', String(perPage.value));
        params.append('sort_by', sortBy.value);
        params.append('sort_direction', sortDirection.value);
        const { data } = await axios.get(
            `/api/equipment-work-orders?${params}`,
        );
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
    () => [props.equipmentNumber, props.dateRange?.start, props.dateRange?.end],
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
                            v-for="col in equipmentWorkOrderColumns"
                            :key="col.id || col.accessorKey || col.key"
                            :class="col.align === 'right' ? 'text-right' : ''"
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
                    <TableRow v-for="(item, idx) in rows" :key="item.id">
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
                                    new Date(
                                        item.requirements_date,
                                    ).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                    }),
                                )
                            }}
                        </TableCell>
                        <TableCell class="font-mono text-sm">{{
                            renderOrNA(item.order_number)
                        }}</TableCell>
                        <TableCell class="font-mono text-sm">{{
                            renderOrNA(item.reservation)
                        }}</TableCell>
                        <TableCell class="font-mono text-sm">{{
                            renderOrNA(item.material)
                        }}</TableCell>
                        <TableCell class="text-right font-mono">
                            {{
                                Number(
                                    item.requirement_quantity || 0,
                                ).toLocaleString('id-ID', {
                                    minimumFractionDigits: 3,
                                    maximumFractionDigits: 3,
                                })
                            }}
                        </TableCell>
                        <TableCell>{{
                            renderOrNA(item.base_unit_of_measure)
                        }}</TableCell>
                        <TableCell class="text-right font-mono">
                            {{
                                Number(
                                    item.quantity_withdrawn || 0,
                                ).toLocaleString('id-ID', {
                                    minimumFractionDigits: 3,
                                    maximumFractionDigits: 3,
                                })
                            }}
                        </TableCell>
                        <TableCell class="text-right font-mono">
                            {{
                                Number(
                                    item.value_withdrawn || 0,
                                ).toLocaleString('id-ID', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })
                            }}
                        </TableCell>
                        <TableCell>{{ renderOrNA(item.currency) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No equipment work orders found</p>
    </div>
</template>
