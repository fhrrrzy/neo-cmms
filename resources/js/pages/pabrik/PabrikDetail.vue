<script setup>
import DataTable from '@/components/tables/monitoring/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import MonitoringFilter from '@/pages/monitoring/components/MonitoringFilter.vue';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Activity,
    ArrowLeft,
    Building2,
    ClipboardCheck,
    FileText,
    Gauge,
    Wrench,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({
    uuid: {
        type: String,
        required: true,
    },
});

// Component state
const loading = ref(false);
const notFound = ref(false);
const plant = ref(null);
const stats = ref(null);
const error = ref(null);

// Equipment data state for DataTable
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

// Filter state
const dateRangeStore = useDateRangeStore();

// Initialize filters with plant_id pre-set
const filters = ref({
    date_range: {
        start:
            dateRangeStore.start ||
            new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0],
        end: dateRangeStore.end || new Date().toISOString().split('T')[0],
    },
    regional_uuids: [],
    plant_uuids: [props.uuid],
    station_codes: [],
    equipment_types: [],
    search: '',
});

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Pabrik',
        href: '/pabrik',
    },
    {
        title: plant.value?.name || 'Loading...',
        href: '#',
    },
]);

const fetchPlantDetail = async () => {
    loading.value = true;
    error.value = null;
    notFound.value = false;
    try {
        const { data } = await axios.get(`/api/pabrik/${props.uuid}`);
        plant.value = data.plant;
        stats.value = data.stats;
    } catch (e) {
        if (e.response?.status === 404) {
            notFound.value = true;
        } else {
            error.value = 'Failed to load plant data';
        }
        console.error('Error fetching plant detail:', e);
    } finally {
        loading.value = false;
    }
};

const fetchEquipment = async (page = 1, pageSize = 15) => {
    loading.value = true;
    error.value = null;
    try {
        const params = new URLSearchParams();
        params.append('page', page.toString());
        params.append('per_page', pageSize.toString());

        // Add sorting parameters
        params.append('sort_by', sorting.value.sort_by);
        params.append('sort_direction', sorting.value.sort_direction);

        // Add plant_id filter (always set to current plant)
        params.append('plant_uuids[]', props.uuid.toString());

        // Add date range
        if (filters.value.date_range?.start) {
            params.append('date_start', filters.value.date_range.start);
        }
        if (filters.value.date_range?.end) {
            params.append('date_end', filters.value.date_range.end);
        }

        // Add station filter (only if not all stations are selected)
        if (
            filters.value.station_codes &&
            filters.value.station_codes.length > 0 &&
            filters.value.station_codes.length < 15 // Only send if not all stations (15 total)
        ) {
            filters.value.station_codes.forEach((code) => {
                params.append('station_codes[]', code);
            });
        }

        // Add equipment type filter (only if not all types are selected)
        if (
            filters.value.equipment_types &&
            filters.value.equipment_types.length > 0 &&
            filters.value.equipment_types.length < 5 // Only send if not all types (5 total)
        ) {
            filters.value.equipment_types.forEach((type) => {
                params.append('equipment_types[]', type);
            });
        }

        // Add search
        if (filters.value.search) {
            params.append('search', filters.value.search);
        }

        const response = await axios.get(`/api/monitoring/equipment?${params}`);

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
            err.response?.data?.message || 'Failed to load equipment data';
        console.error('Error fetching equipment:', err);
    } finally {
        loading.value = false;
    }
};

const navigateToRegional = () => {
    if (plant.value?.regional_uuid) {
        router.visit(`/regions/${plant.value.regional_uuid}`);
    }
};

const handleFilterChange = async (newFilters) => {
    // Ensure plant_ids always includes current plant
    filters.value = {
        ...filters.value,
        ...newFilters,
        plant_uuids: [props.uuid], // Always lock to current plant
    };
    if (newFilters?.date_range?.start && newFilters?.date_range?.end) {
        dateRangeStore.setRange(newFilters.date_range);
    }
    await nextTick();
    fetchEquipment(1, pagination.value.per_page);
};

const handlePageChange = (page) => {
    fetchEquipment(page, pagination.value.per_page);
};

const handlePageSizeChange = (pageSize) => {
    fetchEquipment(1, pageSize);
};

const handleSortChange = (sortBy, sortDirection) => {
    sorting.value = {
        sort_by: sortBy,
        sort_direction: sortDirection,
    };
    fetchEquipment(pagination.value.current_page, pagination.value.per_page);
};

onMounted(async () => {
    await fetchPlantDetail();
    await fetchEquipment();
});
const goBack = () => {
    // Use browser's native back navigation
    // This properly works with Inertia.js history
    window.history.back();
};

const backButtonLabel = computed(() => 'Back');
</script>

<template>
    <Head :title="plant?.name || 'Plant Detail'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-6">
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="w-full space-y-2">
                        <Skeleton class="h-8 w-2/3" />
                        <Skeleton class="h-4 w-40" />
                        <Skeleton class="h-3 w-56" />
                        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <Skeleton
                                v-for="i in 6"
                                :key="i"
                                class="h-10 w-full"
                            />
                        </div>
                    </div>
                    <div class="hidden w-72 md:block">
                        <Skeleton class="h-10 w-full" />
                    </div>
                </div>
                <div
                    class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6"
                >
                    <Skeleton v-for="i in 6" :key="i" class="h-24 w-full" />
                </div>
                <div>
                    <Skeleton class="h-10 w-64" />
                    <div class="mt-4 space-y-3">
                        <Skeleton v-for="i in 8" :key="i" class="h-12 w-full" />
                    </div>
                </div>
            </div>

            <!-- Not Found State -->
            <div
                v-else-if="notFound"
                class="flex min-h-[calc(100vh-15rem)] items-center justify-center px-6"
            >
                <div class="space-y-4 text-center">
                    <p
                        class="text-4xl font-semibold text-primary sm:text-2xl md:text-5xl"
                    >
                        Plant not found
                    </p>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        {{ backButtonLabel }}
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <template v-else-if="plant && stats">
                <!-- Header Section -->
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ plant.name }}
                        </h1>
                        <p class="text-muted-foreground">
                            #{{ plant.plant_code }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <button
                                class="hover:underline"
                                @click="navigateToRegional"
                            >
                                {{ plant.regional_name }}
                            </button>
                        </p>
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-3 md:flex-nowrap"
                    >
                        <Button
                            variant="outline"
                            class="w-full md:w-auto"
                            @click="goBack"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            {{ backButtonLabel }}
                        </Button>
                    </div>
                </div>

                <!-- Statistics Cards Section -->
                <div
                    class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6"
                >
                    <!-- Total Equipment -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Wrench class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Total Equipment</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_equipment.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Equipment count
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Stations -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Total Stations</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_stations.toLocaleString('id-ID')
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Stations in plant
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Work Orders -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Total Work Orders</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_work_orders.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                All work orders
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Active Work Orders -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <ClipboardCheck class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Active Work Orders</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.active_work_orders.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Currently active
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Installed Capacity -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Gauge class="h-5 w-5" />
                            <CardTitle class="text-sm">Capacity</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    plant.kaps_terpasang
                                        ? plant.kaps_terpasang.toLocaleString(
                                              'id-ID',
                                          )
                                        : '—'
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Installed capacity
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Equipment Utilization (placeholder) -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            <CardTitle class="text-sm">Utilization</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">—</div>
                            <p class="text-xs text-muted-foreground">
                                Equipment usage
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Equipment List Section -->
                <div class="space-y-4">
                    <!-- Section Header -->
                    <div class="space-y-1">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Equipment List
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Browse and filter all equipment in {{ plant.name }}.
                            Use the filters below to narrow down your search by
                            date range, station, equipment type, or search
                            terms.
                        </p>
                    </div>

                    <!-- MonitoringFilter with integrated search and column toggle -->
                    <MonitoringFilter
                        :filters="filters"
                        :table="dataTableRef?.table"
                        :hide-regional="true"
                        :hide-plant="true"
                        @filter-change="handleFilterChange"
                    />

                    <!-- Equipment Data Table -->
                    <DataTable
                        ref="dataTableRef"
                        :data="equipment"
                        :loading="loading"
                        :error="error"
                        :pagination="pagination"
                        :sorting="sorting"
                        :open-sheet-on-row-click="true"
                        @page-change="handlePageChange"
                        @page-size-change="handlePageSizeChange"
                        @sort-change="handleSortChange"
                    />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
