<script setup lang="js">
import DataTable from '@/components/tables/monitoring/DataTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { monitoring } from '@/routes';
import { useMonitoringFilterStore } from '@/stores/monitoringFilterStore';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import EquipmentDetailSheet from './components/EquipmentDetailSheet.vue';
import MonitoringFilter from './components/MonitoringFilter.vue';

const loading = ref(false);
const error = ref(null);
const equipment = ref([]);
const dataTableRef = ref();

// Sheet state
const isSheetOpen = ref(false);
const selectedEquipmentNumber = ref('');

// Filter visibility state for mobile (always visible on desktop)
const FILTER_VISIBILITY_KEY = 'monitoring_filter_visible_mobile';
const isFilterVisible = ref(
    localStorage.getItem(FILTER_VISIBILITY_KEY) !== 'false', // Default to true
);

// Watch for changes and save to localStorage
watch(isFilterVisible, (newValue) => {
    localStorage.setItem(FILTER_VISIBILITY_KEY, String(newValue));
});

const toggleFilterVisibility = () => {
    isFilterVisible.value = !isFilterVisible.value;
};

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

// Sorting state - default to cumulative jam jalan desc when loading from stored state
const sorting = ref({
    sort_by: 'cumulative_jam_jalan',
    sort_direction: 'desc',
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
    regional_ids: monitoringFilterStore.regional_ids || [],
    plant_ids: monitoringFilterStore.plant_ids || [],
    station_codes: monitoringFilterStore.station_codes || [],
    equipment_types: monitoringFilterStore.equipment_types || [],
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

        // Add filter parameters (arrays)
        if (
            filters.value.regional_ids &&
            filters.value.regional_ids.length > 0
        ) {
            filters.value.regional_ids.forEach((id) => {
                params.append('regional_ids[]', id.toString());
            });
        }
        if (filters.value.plant_ids && filters.value.plant_ids.length > 0) {
            filters.value.plant_ids.forEach((id) => {
                params.append('plant_ids[]', id.toString());
            });
        }
        if (
            filters.value.station_codes &&
            filters.value.station_codes.length > 0 &&
            filters.value.station_codes.length < 15  // Only send if not all stations (15 total)
        ) {
            filters.value.station_codes.forEach((code) => {
                params.append('station_codes[]', code);
            });
        }
        if (
            filters.value.equipment_types &&
            filters.value.equipment_types.length > 0 &&
            filters.value.equipment_types.length < 5  // Only send if not all types (5 total)
        ) {
            filters.value.equipment_types.forEach((type) => {
                params.append('equipment_types[]', type);
            });
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
        regional_ids: filters.value.regional_ids || [],
        plant_ids: filters.value.plant_ids || [],
        station_codes: filters.value.station_codes || [],
        equipment_types: filters.value.equipment_types || [],
        search: filters.value.search,
    });
    if (newFilters?.date_range?.start && newFilters?.date_range?.end) {
        dateRangeStore.setRange(newFilters.date_range);
    }
    // Reset to first page when filters change
    fetchEquipment(1, pagination.value.per_page);
};

const handlePageChange = (page) => {
    fetchEquipment(page, pagination.value.per_page);
};

const handlePageSizeChange = (perPage) => {
    pagination.value.per_page = perPage;
    fetchEquipment(1, perPage);
};

const handleSortChange = (sortBy, sortDirection) => {
    if (sortBy && sortDirection) {
        sorting.value.sort_by = sortBy;
        sorting.value.sort_direction = sortDirection;
    } else {
        // Remove sorting - use default
        sorting.value.sort_by = 'equipment_number';
        sorting.value.sort_direction = 'asc';
    }
    // Reset to first page when sorting changes
    fetchEquipment(1, pagination.value.per_page);
};

const handleRowClick = (equipment) => {
    // Open equipment detail in sheet
    selectedEquipmentNumber.value = equipment.uuid;
    isSheetOpen.value = true;
};

const handleSheetClose = () => {
    isSheetOpen.value = false;
    selectedEquipmentNumber.value = '';
};

onMounted(() => {
    fetchEquipment();
});
</script>

<template>

    <Head title="Monitoring Equipment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 md:space-y-6">
            <!-- Mobile Filter Toggle Button -->
            <div class="md:hidden">
                <Button variant="outline" size="sm" class="w-full justify-between" @click="toggleFilterVisibility">
                    <span>{{ isFilterVisible ? 'Hide Filters' : 'Show Filters' }}</span>
                    <ChevronUp v-if="isFilterVisible" class="h-4 w-4" />
                    <ChevronDown v-else class="h-4 w-4" />
                </Button>
            </div>

            <!-- MonitoringFilter with integrated search and column toggle -->
            <transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2">
                <div v-show="isFilterVisible" class="md:block">
                    <MonitoringFilter :filters="filters" :table="dataTableRef?.table"
                        @filter-change="handleFilterChange" />
                </div>
            </transition>

            <!-- Equipment Data Table -->
            <div class="space-y-4">
                <DataTable ref="dataTableRef" :data="equipment" :loading="loading" :error="error"
                    :pagination="pagination" :sorting="sorting" @page-change="handlePageChange"
                    @page-size-change="handlePageSizeChange" @sort-change="handleSortChange"
                    @row-click="handleRowClick" />
            </div>
        </div>

        <!-- Equipment Detail Sheet -->
        <EquipmentDetailSheet :is-open="isSheetOpen" :equipment-number="selectedEquipmentNumber"
            @close="handleSheetClose" />
    </AppLayout>
</template>
