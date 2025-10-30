<script setup lang="js">
import SummaryTable from '@/components/tables/jam-jalan-summary/DataTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Filter } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import MonitoringFilter from '../monitoring/components/MonitoringFilter.vue';

const tableRef = ref(null);

const loading = ref(false);
const error = ref(null);
const summaryData = ref([]);
const filteredSummaryData = ref([]);
const dates = ref([]);
const searchQuery = ref('');

// Filter visibility state (not persisted)
const isFilterVisible = ref(true);

const toggleFilterVisibility = () => {
    isFilterVisible.value = !isFilterVisible.value;
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '',
    },
    {
        title: 'Jam Jalan Summary',
        href: '',
    },
];

// Initialize with default filters (not from store)
const filters = ref({
    date_range: {
        start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
        end: new Date().toISOString().split('T')[0],
    },
    regional_ids: [],
    plant_ids: [],
    station_codes: [],
});

const pagination = ref({
    total: 0,
    per_page: 15,
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    has_more_pages: false,
});

const fetchSummary = async () => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();

        // Add date range parameters
        if (filters.value.date_range.start) {
            params.append('date_start', filters.value.date_range.start);
        }
        if (filters.value.date_range.end) {
            params.append('date_end', filters.value.date_range.end);
        }

        // Add filter parameters
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

        const response = await axios.get(
            `/api/monitoring/jam-jalan-summary?${params}`,
        );

        summaryData.value = response.data.data;
        dates.value = response.data.dates;
        applySearchFilter();
    } catch (err) {
        error.value =
            err.response?.data?.message || 'Terjadi kesalahan saat memuat data';
        console.error('Error fetching summary:', err);
    } finally {
        loading.value = false;
    }
};

const applySearchFilter = () => {
    if (!searchQuery.value) {
        filteredSummaryData.value = summaryData.value;
    } else {
        const query = searchQuery.value.toLowerCase();
        filteredSummaryData.value = summaryData.value.filter((plant) =>
            plant.name.toLowerCase().includes(query),
        );
    }

    // Update pagination based on filtered results
    pagination.value = {
        total: filteredSummaryData.value.length,
        per_page: filteredSummaryData.value.length || 15,
        current_page: 1,
        last_page: 1,
        from: filteredSummaryData.value.length > 0 ? 1 : 0,
        to: filteredSummaryData.value.length,
        has_more_pages: false,
    };
};

const handleSearchInput = (event) => {
    searchQuery.value = event.target.value;
    applySearchFilter();
};

const handleFilterChange = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
    fetchSummary();
};

onMounted(() => {
    fetchSummary();
});
</script>

<template>

    <Head title="Jam Jalan Summary" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 md:space-y-6">

            <!-- Toggleable Filter Container -->
            <transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2">
                <div v-show="isFilterVisible" class="">
                    <MonitoringFilter :filters="filters" :disable-store="true" @filter-change="handleFilterChange" />
                </div>
            </transition>

            <!-- Summary Table -->
            <SummaryTable ref="tableRef" :data="filteredSummaryData" :dates="dates" :loading="loading" :error="error"
                :pagination="pagination" />
        </div>
    </AppLayout>
</template>
