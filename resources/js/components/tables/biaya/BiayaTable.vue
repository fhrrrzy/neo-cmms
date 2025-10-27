<script setup>
import WorkOrderPagination from '@/components/tables/work-order/WorkOrderPagination.vue';
import Skeleton from '@/components/ui/skeleton/Skeleton.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

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

const formatCurrency = (amount, currency = 'IDR') => {
    if (!amount && amount !== 0) return 'N/A';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
};

const formatNumber = (num) => {
    if (!num && num !== 0) return 'N/A';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    }).format(num);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
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
        params.append('sort_by', sortBy.value || 'requirements_date');
        params.append('sort_direction', sortDirection.value || 'desc');

        const { data } = await axios.get(`/api/monitoring/biaya?${params}`);
        rows.value = data?.data || [];
        pagination.value = {
            total: data?.total ?? 0,
            per_page: data?.per_page ?? perPage.value,
            current_page: data?.current_page ?? page.value,
            last_page: data?.last_page ?? 1,
        };
    } catch (error) {
        console.error('Error fetching biaya data:', error);
        rows.value = [];
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (newPage) => {
    page.value = newPage;
    fetchData();
};

const handlePageSizeChange = (per) => {
    perPage.value = per;
    page.value = 1;
    fetchData();
};

const handleSort = (column) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'desc';
    }
    page.value = 1;
    fetchData();
};

const sortIcon = (column) => {
    if (sortBy.value !== column) return null;
    return sortDirection.value === 'asc' ? '↑' : '↓';
};

onMounted(fetchData);
watch(
    () => [props.equipmentNumber, props.dateRange?.start, props.dateRange?.end],
    () => {
        page.value = 1;
        fetchData();
    },
);
</script>

<template>
    <div class="space-y-4">
        <!-- Improved Skeleton Loader with Dark Mode Support -->
        <div v-if="loading" class="space-y-4 rounded-lg border p-4">
            <!-- Table header skeleton -->
            <div
                class="grid grid-cols-8 gap-4 border-b pb-3 dark:border-gray-800"
            >
                <Skeleton class="h-5 w-12" />
                <Skeleton class="h-5 w-24" />
                <Skeleton class="h-5 w-28" />
                <Skeleton class="h-5 w-20" />
                <Skeleton class="h-5 w-32 justify-self-end" />
                <Skeleton class="h-5 w-20 justify-self-end" />
                <Skeleton class="h-5 w-24 justify-self-end" />
                <Skeleton class="h-5 w-16" />
            </div>
            <!-- Table rows skeleton -->
            <div v-for="i in 5" :key="i" class="grid grid-cols-8 gap-4 py-2">
                <Skeleton class="h-4 w-8" />
                <Skeleton class="h-4 w-20" />
                <Skeleton class="h-4 w-28" />
                <Skeleton class="h-4 w-24" />
                <Skeleton class="h-4 w-28 justify-self-end" />
                <Skeleton class="h-4 w-20 justify-self-end" />
                <Skeleton class="h-4 w-24 justify-self-end" />
                <Skeleton class="h-4 w-12" />
            </div>
        </div>

        <div
            v-else-if="rows.length > 0"
            :class="maxHeightClass"
            class="overflow-x-auto"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            class="w-12 text-center"
                            @click="handleSort('requirements_date')"
                        >
                            #
                        </TableHead>
                        <TableHead @click="handleSort('requirements_date')">
                            Date
                            <span v-if="sortIcon('requirements_date')">
                                {{ sortIcon('requirements_date') }}
                            </span>
                        </TableHead>
                        <TableHead @click="handleSort('order_number')">
                            Order No
                            <span v-if="sortIcon('order_number')">
                                {{ sortIcon('order_number') }}
                            </span>
                        </TableHead>
                        <TableHead @click="handleSort('material')">
                            Material
                            <span v-if="sortIcon('material')">
                                {{ sortIcon('material') }}
                            </span>
                        </TableHead>
                        <TableHead class="text-right">
                            Material Description
                        </TableHead>
                        <TableHead
                            class="text-right"
                            @click="handleSort('quantity_withdrawn')"
                        >
                            Qty
                            <span v-if="sortIcon('quantity_withdrawn')">
                                {{ sortIcon('quantity_withdrawn') }}
                            </span>
                        </TableHead>
                        <TableHead
                            class="text-right"
                            @click="handleSort('value_withdrawn')"
                        >
                            Value
                            <span v-if="sortIcon('value_withdrawn')">
                                {{ sortIcon('value_withdrawn') }}
                            </span>
                        </TableHead>
                        <TableHead class="text-left"> Currency </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(item, idx) in rows" :key="item.id || idx">
                        <TableCell class="text-center">
                            {{
                                (pagination.current_page - 1) *
                                    pagination.per_page +
                                idx +
                                1
                            }}
                        </TableCell>
                        <TableCell>
                            {{ formatDate(item.requirements_date) }}
                        </TableCell>
                        <TableCell class="font-medium">
                            {{ item.order_number || 'N/A' }}
                        </TableCell>
                        <TableCell>
                            {{ item.material || 'N/A' }}
                        </TableCell>
                        <TableCell class="text-right">
                            {{ item.material_description || 'N/A' }}
                        </TableCell>
                        <TableCell class="text-right">
                            {{ formatNumber(item.quantity_withdrawn) }}
                            <span
                                v-if="item.base_unit_of_measure"
                                class="text-xs text-muted-foreground"
                            >
                                {{ item.base_unit_of_measure }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right font-semibold">
                            {{
                                formatCurrency(
                                    item.value_withdrawn,
                                    item.currency,
                                )
                            }}
                        </TableCell>
                        <TableCell class="text-left">
                            {{ item.currency || 'N/A' }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-else class="py-8 text-center text-muted-foreground">
            <p>No biaya data found for this equipment</p>
        </div>

        <WorkOrderPagination
            v-if="rows.length > 0 && !loading"
            :pagination="pagination"
            @page-change="handlePageChange"
            @page-size-change="handlePageSizeChange"
        />
    </div>
</template>
