<script setup lang="js">
import DataTable from '@/components/monitoring/DataTable.vue';
import DataTableViewOptions from '@/components/monitoring/DataTableViewOptions.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { monitoring } from '@/routes';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';
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

const breadcrumbs = [
    {
        title: 'Monitoring',
        href: monitoring().url,
    },
];

const filters = ref({
    date_range: {
        start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
        end: new Date().toISOString().split('T')[0],
    },
});

const fetchEquipment = async (page = 1, perPage = 15) => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();

        // Add pagination parameters
        params.append('page', page.toString());
        params.append('per_page', perPage.toString());

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
    filters.value = { ...newFilters };
    // Reset to first page when filters change
    fetchEquipment(1, pagination.value.per_page);
};

const handlePageChange = (page) => {
    fetchEquipment(page, pagination.value.per_page);
};

const handlePageSizeChange = (perPage) => {
    fetchEquipment(1, perPage);
};

onMounted(() => {
    fetchEquipment();
});
</script>

<template>
    <Head title="Monitoring Equipment" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Filter and View Toggle -->
            <div class="pb-4">
                <div class="flex items-end justify-between gap-4">
                    <MonitoringFilter
                        :filters="filters"
                        @filter-change="handleFilterChange"
                    />
                    <DataTableViewOptions :table="dataTableRef?.table" />
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
                    @page-change="handlePageChange"
                    @page-size-change="handlePageSizeChange"
                />
            </div>
        </div>
    </AppLayout>
</template>
