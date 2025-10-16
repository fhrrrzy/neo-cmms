<script setup>
import WorkOrderPagination from '@/components/tables/work-order/WorkOrderPagination.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from 'axios';
import { computed, h, onMounted, ref, watch } from 'vue';
import { equipmentWorkOrderGroupedByMaterialColumns } from './columns';

const props = defineProps({
    equipmentNumber: { type: String, required: false },
    orderNumber: { type: String, required: false },
    dateRange: { type: Object, required: false },
    maxHeightClass: { type: String, default: 'max-h-[80vh]' },
});

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const perPage = ref(15);
const sortBy = ref('count');
const sortDirection = ref('desc');

const pagination = ref({
    total: 0,
    current_page: 1,
    per_page: 15,
    last_page: 1,
});

const isDetailMode = computed(() => !!props.orderNumber);
const activeColumns = equipmentWorkOrderGroupedByMaterialColumns;

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
    return !s || lowered === '-' || lowered === 'no data' || lowered === 'n/a'
        ? 'N/A'
        : String(value);
};

const fetchData = async () => {
    loading.value = true;
    try {
        if (isDetailMode.value) {
            const { data } = await axios.get(
                `/api/equipment-work-orders/${encodeURIComponent(props.orderNumber)}?group_by=material`,
            );
            rows.value = Array.isArray(data?.data) ? data.data : [];
            pagination.value = {
                total: rows.value.length,
                per_page: rows.value.length || 1,
                current_page: 1,
                last_page: 1,
            };
            return;
        }
        const params = new URLSearchParams();
        params.append('group_by', 'material');
        if (props.equipmentNumber)
            params.append('equipment_number', props.equipmentNumber);
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        params.append('page', String(page.value));
        params.append('per_page', String(perPage.value));
        params.append('sort_by', sortBy.value || 'count');
        params.append('sort_direction', sortDirection.value || 'desc');
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

const handlePageChange = (newPage) => {
    if (isDetailMode.value) return;
    page.value = newPage;
    fetchData();
};

const handlePageSizeChange = (per) => {
    if (isDetailMode.value) return;
    perPage.value = per;
    page.value = 1;
    fetchData();
};

onMounted(fetchData);
watch(
    () => [
        props.equipmentNumber,
        props.dateRange?.start,
        props.dateRange?.end,
        props.orderNumber,
    ],
    () => {
        page.value = 1;
        fetchData();
    },
);
</script>

<template>
    <div v-if="rows?.length > 0" class="space-y-4">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead
                        v-for="col in activeColumns"
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
                <TableRow
                    v-for="(item, idx) in rows"
                    :key="item.id || `${item.material}-${idx}`"
                >
                    <TableCell class="text-center font-medium">
                        {{
                            (pagination.current_page - 1) *
                                pagination.per_page +
                            idx +
                            1
                        }}
                    </TableCell>
                    <template
                        v-for="col in activeColumns.slice(1)"
                        :key="col.id || col.accessorKey || col.key"
                    >
                        <TableCell
                            :class="
                                col.align === 'right'
                                    ? 'text-right'
                                    : col.class || ''
                            "
                        >
                            <component
                                :is="
                                    typeof col.cell === 'function'
                                        ? col.cell({
                                              item,
                                              column: col,
                                              index: idx,
                                              table: tableContext,
                                          })
                                        : h(
                                              'span',
                                              null,
                                              renderOrNA(
                                                  item[
                                                      col.accessorKey || col.key
                                                  ],
                                              ),
                                          )
                                "
                            />
                        </TableCell>
                    </template>
                </TableRow>
            </TableBody>
        </Table>
        <WorkOrderPagination
            v-if="!isDetailMode"
            :pagination="pagination"
            @page-change="handlePageChange"
            @page-size-change="handlePageSizeChange"
        />
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No equipment work orders found</p>
    </div>
</template>
