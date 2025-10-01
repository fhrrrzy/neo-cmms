<script setup lang="js">
import DataTable from '@/components/monitoring/DataTable.vue';
import DataTableViewOptions from '@/components/monitoring/DataTableViewOptions.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { monitoring } from '@/routes';
import { useMonitoringFilterStore } from '@/stores/monitoringFilterStore';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { nextTick, onMounted, ref } from 'vue';
import MonitoringFilter from './components/MonitoringFilter.vue';

const loading = ref(false);
const error = ref(null);
const equipment = ref([]);
const dataTableRef = ref();

// Pagination state
const pagination = ref({
    total: 0,
    per_page: 15,
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    has_more_pages: false,
});

// Sorting state
const sorting = ref({
    sort_by: 'equipment_number',
    sort_direction: 'asc',
});

const breadcrumbs = [
    {
        title: 'Monitoring',
        href: monitoring().url,
    },
];

const dateRangeStore = useDateRangeStore();
const monitoringFilterStore = useMonitoringFilterStore();
monitoringFilterStore.load();

const filters = ref({
    date_range: {
        start: dateRangeStore.start,
        end: dateRangeStore.end,
    },
    regional_id: monitoringFilterStore.regional_id,
    plant_id: monitoringFilterStore.plant_id,
    station_id: monitoringFilterStore.station_id,
    search: monitoringFilterStore.search,
});

const fetchEquipment = async (page = 1, perPage = 15) => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();

        // Add pagination parameters
        params.append('page', page.toString());
        params.append('per_page', perPage.toString());

        // Add sorting parameters
        params.append('sort_by', sorting.value.sort_by);
        params.append('sort_direction', sorting.value.sort_direction);

        // Add filter parameters
        if (filters.value.regional_id) {
            params.append('regional_id', filters.value.regional_id.toString());
        }
        if (filters.value.plant_id) {
            params.append('plant_id', filters.value.plant_id.toString());
        }
        if (filters.value.station_id) {
            params.append('station_id', filters.value.station_id.toString());
        }
        if (filters.value.date_range.start) {
            params.append('date_start', filters.value.date_range.start);
        }
        if (filters.value.date_range.end) {
            params.append('date_end', filters.value.date_range.end);
        }
        if (filters.value.search) {
            params.append('search', filters.value.search);
        }

        const response = await axios.get(`/api/monitoring/equipment?${params}`);

        // Update equipment data and pagination info
        equipment.value = response.data.data;
        pagination.value = {
            total: response.data.total,
            per_page: response.data.per_page,
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            from: response.data.from,
            to: response.data.to,
            has_more_pages: response.data.has_more_pages,
        };
    } catch (err) {
        error.value =
            err.response?.data?.message || 'Terjadi kesalahan saat memuat data';
        console.error('Error fetching equipment:', err);
    } finally {
        loading.value = false;
    }
};

const handleFilterChange = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
    monitoringFilterStore.setFilters({
        regional_id: filters.value.regional_id,
        plant_id: filters.value.plant_id,
        station_id: filters.value.station_id,
        search: filters.value.search,
    });
    if (newFilters?.date_range?.start && newFilters?.date_range?.end) {
        dateRangeStore.setRange(newFilters.date_range);
    }
    // Reset to first page when filters change
    fetchEquipment(1, pagination.value.per_page);
};

// Debounced search handler
let searchTimer;
const handleSearchInput = (event) => {
    const value = event?.target?.value ?? '';
    filters.value.search = value;
    monitoringFilterStore.setSearch(value);
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        await nextTick();
        fetchEquipment(1, pagination.value.per_page);
    }, 300);
};

const handlePageChange = (page) => {
    fetchEquipment(page, pagination.value.per_page);
};

const handlePageSizeChange = (perPage) => {
    pagination.value.per_page = perPage;
    fetchEquipment(1, perPage);
};

const handleSortChange = (sortBy, sortDirection) => {
    sorting.value.sort_by = sortBy;
    sorting.value.sort_direction = sortDirection;
    // Reset to first page when sorting changes
    fetchEquipment(1, pagination.value.per_page);
};

const handleRowClick = (equipment) => {
    // Navigate to equipment detail page
    router.visit(`/equipment/${equipment.equipment_number}`);
};

onMounted(() => {
    fetchEquipment();
});
</script>

<template>
    <Head title="Monitoring Equipment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Filter and View Toggle -->
            <div class="">
                <div class="flex items-end justify-between gap-4">
                    <MonitoringFilter
                        :filters="filters"
                        @filter-change="handleFilterChange"
                    />
                    <div class="flex items-center gap-3">
                        <input
                            type="text"
                            class="h-9 w-48 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                            :value="filters.search"
                            @input="handleSearchInput"
                            placeholder="Search..."
                            aria-label="Search equipment"
                        />
                        <DataTableViewOptions :table="dataTableRef?.table" />
                    </div>
                </div>
            </div>

            <!-- Equipment Data Table -->
            <div class="space-y-4">
                <DataTable
                    ref="dataTableRef"
                    :data="equipment"
                    :loading="loading"
                    :error="error"
                    :pagination="pagination"
                    :sorting="sorting"
                    @page-change="handlePageChange"
                    @page-size-change="handlePageSizeChange"
                    @sort-change="handleSortChange"
                    @row-click="handleRowClick"
                />
            </div>
        </div>
    </AppLayout>
</template>
