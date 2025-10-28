<script setup>
import WorkOrderPagination from '@/components/tables/work-order/WorkOrderPagination.vue';
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
import axios from 'axios';
import { Package } from 'lucide-vue-next';
import { computed, h, onMounted, ref, watch } from 'vue';
import { equipmentWorkOrderGroupedByMaterialColumns } from './columns';
import MaterialUsageSheet from './MaterialUsageSheet.vue';

const props = defineProps({
    equipmentNumber: { type: String, required: false },
    orderNumber: { type: String, required: false },
    dateRange: { type: Object, required: false },
    maxHeightClass: { type: String, default: 'max-h-[80vh]' },
});

const rows = ref([]);
const loading = ref(false);
const initialized = ref(false);
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
        initialized.value = true;
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

// Sheet state
const sheetOpen = ref(false);
const selectedMaterial = ref('');
const selectedMaterialDesc = ref('');
const selectedEquipmentNumber = ref('');

const openMaterialSheet = (row) => {
    selectedMaterial.value = row?.material || '';
    selectedMaterialDesc.value = row?.material_description || '';
    // Use equipment number from row data (when available) or from props
    selectedEquipmentNumber.value =
        row?.equipment_number || props.equipmentNumber || '';
    sheetOpen.value = true;
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
    <!-- Skeleton while loading or before first fetch completes -->
    <div v-if="!initialized || loading" class="space-y-2">
        <Skeleton class="h-8 w-1/3" />
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead v-for="n in 5" :key="n">
                        <Skeleton class="h-4 w-24" />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="i in 8" :key="i">
                    <TableCell v-for="j in 5" :key="j">
                        <Skeleton class="h-4 w-full" />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <div v-else-if="rows?.length > 0" class="space-y-4">
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
                    class="cursor-pointer hover:bg-muted/50"
                    @click="openMaterialSheet(item)"
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
                                              item[col.accessorKey || col.key],
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

        <MaterialUsageSheet
            :open="sheetOpen"
            :material="selectedMaterial"
            :material-description="selectedMaterialDesc"
            :equipment-number="selectedEquipmentNumber"
            @update:open="(v) => (sheetOpen = v)"
        />
    </div>
    <div v-else class="py-8">
        <Empty>
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Package />
                </EmptyMedia>
                <EmptyTitle>No Material</EmptyTitle>
                <EmptyDescription> No Material found </EmptyDescription>
            </EmptyHeader>
        </Empty>
    </div>
</template>
