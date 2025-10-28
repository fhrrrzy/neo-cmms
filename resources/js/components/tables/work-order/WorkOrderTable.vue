<script setup>
import WorkOrderItemsDialog from '@/components/tables/work-order/WorkOrderItemsDialog.vue';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from 'axios';
import { ClipboardList } from 'lucide-vue-next';
import { computed, h, onMounted, ref, watch } from 'vue';
import { workOrderColumns } from './columns';
import WorkOrderPagination from './WorkOrderPagination.vue';

const props = defineProps({
    plantId: { type: [String, Number], required: false },
    dateRange: { type: Object, required: true },
    // Tailwind class for max height; default to 80vh
    maxHeightClass: { type: String, default: 'max-h-[80vh]' },
    equipmentNumber: { type: [String, Number], required: false },
});

const rows = ref([]);
const loading = ref(false);
const page = ref(1);
const perPage = ref(15);
const sortBy = ref('created_on');
const sortDirection = ref('desc');
const search = ref('');
const orderType = ref('ALL');
let searchDebounceId;

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

const orderTypeToLabel = (code) => {
    const map = {
        PM01: 'PM01 - Preventive Maintenance',
        PM02: 'PM02 - Corrective Maintenance',
        PM03: 'PM03 - Emergency Maintenance',
        PM04: 'PM04 - Project Maintenance',
    };
    if (!code) return 'Unknown';
    return map[code] ?? `Anomaly: ${code}`;
};
const selectionTypeToModelLabel = (selection) => {
    const numericToLabel = {
        1: 'PM01 - Preventive Maintenance',
        2: 'PM02 - Corrective Maintenance',
        3: 'PM03 - Emergency Maintenance',
        4: 'PM04 - Project Maintenance',
    };
    if (selection === 'ANOMALY') return 'Anomaly';
    if (selection === 'ALL') return 'Semua Jenis';
    return numericToLabel[selection] ?? 'Semua Jenis';
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        if (props.equipmentNumber)
            params.append('equipment_number', String(props.equipmentNumber));
        params.append('page', String(page.value));
        params.append('per_page', String(perPage.value));
        params.append('sort_by', sortBy.value);
        params.append('sort_direction', sortDirection.value);
        if (search.value) params.append('search', search.value);
        if (orderType.value && orderType.value !== 'ALL') {
            params.append('order_type', orderType.value);
        }
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
    () => [props.dateRange?.start, props.dateRange?.end, props.equipmentNumber],
    fetchData,
);

watch(
    () => search.value,
    () => {
        if (searchDebounceId) clearTimeout(searchDebounceId);
        searchDebounceId = setTimeout(() => {
            page.value = 1;
            fetchData();
        }, 300);
    },
);

const handlePageChange = (newPage) => {
    page.value = newPage;
    fetchData();
};

const handlePageSizeChange = (perPageValue) => {
    perPage.value = perPageValue;
    page.value = 1; // reset to first page when page size changes
    fetchData();
};
const isDesktop = ref(true);
const showSheet = ref(false);
const selectedOrderNumber = ref('');

if (typeof window !== 'undefined') {
    const mql = window.matchMedia('(min-width: 768px)');
    const updateMatch = () => (isDesktop.value = mql.matches);
    updateMatch();
    mql.addEventListener('change', updateMatch);
}

const openWorkOrder = async (wo) => {
    selectedOrderNumber.value = String(wo.order || wo.order_number || '');
    showSheet.value = true;
};

const displayedRows = computed(() => {
    const term = (search.value || '').toString().toLowerCase().trim();
    const type = orderType.value;
    const numericToCode = { 1: 'PM01', 2: 'PM02', 3: 'PM03', 4: 'PM04' };
    const knownCodes = Object.values(numericToCode);
    return (rows.value || []).filter((wo) => {
        if (
            props.equipmentNumber &&
            String(wo.equipment_number) !== String(props.equipmentNumber)
        ) {
            return false;
        }
        let matchesType = true;
        if (type !== 'ALL') {
            if (type === 'ANOMALY') {
                matchesType = !knownCodes.includes(wo.order_type);
            } else if (numericToCode[type]) {
                matchesType = wo.order_type === numericToCode[type];
            }
        }
        if (!term) return matchesType;
        const haystack = [
            String(wo.order || ''),
            String(wo.order_number || ''),
            wo.description || '',
            wo.cause_text || '',
            wo.item_text || '',
        ]
            .join(' ')
            .toLowerCase();
        return matchesType && haystack.includes(term);
    });
});
</script>

<template>
    <div class="w-full">
        <div class="mb-4 flex flex-wrap justify-end gap-2 md:flex-nowrap">
            <div class="">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Cari order, deskripsi, cause, item..."
                    class="h-9"
                />
            </div>
            <div>
                <Select
                    :model-value="orderType"
                    @update:model-value="
                        (v) => {
                            orderType = v;
                            page = 1; /* FE filtered */
                        }
                    "
                >
                    <SelectTrigger class="h-9">
                        <SelectValue
                            :placeholder="selectionTypeToModelLabel(orderType)"
                        />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem value="ALL">Semua Jenis</SelectItem>
                        <SelectItem value="1"
                            >PM01 - Preventive Maintenance</SelectItem
                        >
                        <SelectItem value="2"
                            >PM02 - Corrective Maintenance</SelectItem
                        >
                        <SelectItem value="3"
                            >PM03 - Emergency Maintenance</SelectItem
                        >
                        <SelectItem value="4"
                            >PM04 - Project Maintenance</SelectItem
                        >
                        <SelectItem value="ANOMALY">Anomaly</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <!-- Active filters -->
        <div
            v-if="search || orderType !== 'ALL'"
            class="mb-3 flex flex-wrap items-center gap-2"
        >
            <span
                v-if="search"
                class="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs"
            >
                Search: <span class="font-medium">{{ search }}</span>
                <button
                    class="rounded-full p-1 hover:bg-background"
                    @click="
                        () => {
                            search = '';
                            page = 1;
                            fetchData();
                        }
                    "
                >
                    ✕
                </button>
            </span>
            <span
                v-if="orderType !== 'ALL'"
                class="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs"
            >
                Jenis:
                <span class="font-medium">{{
                    selectionTypeToModelLabel(orderType)
                }}</span>
                <button
                    class="rounded-full p-1 hover:bg-background"
                    @click="
                        () => {
                            orderType = 'ALL';
                            page = 1; // FE filtered
                        }
                    "
                >
                    ✕
                </button>
            </span>
        </div>
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
                <TableRow v-if="displayedRows.length === 0">
                    <TableCell :colspan="workOrderColumns.length" class="p-8">
                        <div class="flex items-center justify-center">
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <ClipboardList />
                                    </EmptyMedia>
                                    <EmptyTitle>No Work Orders</EmptyTitle>
                                    <EmptyDescription>
                                        No work orders found
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        </div>
                    </TableCell>
                </TableRow>
                <TableRow
                    v-else
                    v-for="(wo, idx) in displayedRows"
                    :key="wo.id"
                    class="cursor-pointer hover:bg-muted/50"
                    @click="openWorkOrder(wo)"
                >
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
                    <TableCell>{{ orderTypeToLabel(wo.order_type) }}</TableCell>
                    <TableCell
                        class="max-w-[320px] truncate"
                        :title="wo.description"
                    >
                        {{ renderOrNA(wo.description) }}
                    </TableCell>
                    <TableCell
                        class="max-w-[320px] truncate"
                        :title="wo.cause_text"
                    >
                        {{ renderOrNA(wo.cause_text) }}
                    </TableCell>
                    <TableCell
                        class="max-w-[320px] truncate"
                        :title="wo.item_text"
                    >
                        {{ renderOrNA(wo.item_text) }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <!-- Pagination -->
        <div class="mt-4">
            <WorkOrderPagination
                :pagination="pagination"
                @page-change="handlePageChange"
                @page-size-change="handlePageSizeChange"
            />
        </div>

        <!-- Dialog with items table -->
        <WorkOrderItemsDialog
            :open="showSheet"
            :order-number="selectedOrderNumber"
            @update:open="(v) => (showSheet = v)"
        />
    </div>
</template>
