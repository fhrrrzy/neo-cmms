<script setup>
import DataTable from '@/components/tables/monitoring/DataTable.vue';
import DataTableViewOptions from '@/components/tables/monitoring/DataTableViewOptions.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Checkbox } from '@/components/ui/checkbox';
import { RangeCalendar } from '@/components/ui/range-calendar';
import AppLayout from '@/layouts/AppLayout.vue';
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
    ChevronsUpDown,
    MapPin,
    Search,
    Filter,
    X,
    Calendar as CalendarIcon,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch, nextTick } from 'vue';
import { parseDate, getLocalTimeZone } from '@internationalized/date';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    id: {
        type: Number,
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
const isFilterVisible = ref(true);
const stations = ref([]);
const equipmentTypes = ref([]);
const stationOpen = ref(false);
const typeOpen = ref(false);
const stationSearch = ref('');
const typeSearch = ref('');
const datePopoverOpen = ref(false);

const filters = ref({
    date_range: {
        start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
        end: new Date().toISOString().split('T')[0],
    },
    station_codes: [],
    equipment_types: [],
    search: '',
});

const rangeValue = ref({
    start: parseDate(filters.value.date_range.start),
    end: parseDate(filters.value.date_range.end),
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
        const { data } = await axios.get(`/api/pabrik/${props.id}`);
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
        params.append('page', page);
        params.append('per_page', pageSize);
        params.append('plant_id', props.id);

        // Add date range
        if (filters.value.date_range?.start) {
            params.append('date_start', filters.value.date_range.start);
        }
        if (filters.value.date_range?.end) {
            params.append('date_end', filters.value.date_range.end);
        }

        // Add station filter (only if not all stations are selected)
        if (filters.value.station_codes?.length > 0 && filters.value.station_codes.length < stations.value.length) {
            filters.value.station_codes.forEach(code => {
                params.append('station_codes[]', code);
            });
        }

        // Add equipment type filter (only if not all types are selected)
        if (filters.value.equipment_types?.length > 0 && filters.value.equipment_types.length < equipmentTypes.value.length) {
            filters.value.equipment_types.forEach(type => {
                params.append('equipment_types[]', type);
            });
        }

        // Add search
        if (filters.value.search) {
            params.append('search', filters.value.search);
        }

        // Add sorting
        if (sorting.value.sort_by) {
            params.append('sort_by', sorting.value.sort_by);
        }
        if (sorting.value.sort_direction) {
            params.append('sort_direction', sorting.value.sort_direction);
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
    if (plant.value?.regional_id) {
        router.visit(`/regions/${plant.value.regional_id}`);
    }
};

const handleFilterChange = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters };
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

const toggleFilterVisibility = () => {
    isFilterVisible.value = !isFilterVisible.value;
};

const applyFilters = () => {
    fetchEquipment(1, pagination.value.per_page);
};

// Station and Equipment Type data
const stationTypes = [
    { code: 'STAS01', description: 'Jembatan Timbang' },
    { code: 'STAS02', description: 'Loading Ramp' },
    { code: 'STAS03', description: 'Sterilizer' },
    { code: 'STAS04', description: 'Rail Track' },
    { code: 'STAS05', description: 'Thresser & Hoisting' },
    { code: 'STAS06', description: 'Pressan' },
    { code: 'STAS07', description: 'Klarifikasi' },
    { code: 'STAS08', description: 'Pengolahan Inti Sawi' },
    { code: 'STAS09', description: 'Boiler' },
    { code: 'STAS10', description: 'Pengolahan Air' },
    { code: 'STAS11', description: 'Kamar Mesin' },
    { code: 'STAS12', description: 'Tangki Timbun dan Ke' },
    { code: 'STAS13', description: 'Limbah' },
    { code: 'STAS14', description: 'Empty Bunch Hopper' },
    { code: 'STAS19', description: 'Laboratorium' },
];

const equipmentTypeOptions = [
    'Mesin Produksi',
    'Kendaraan',
    'Alat dan Utilitas',
    'IT & Telekomunikasi',
    'Aset PMN',
];

const toggleStation = (stationCode) => {
    const index = filters.value.station_codes.indexOf(stationCode);
    if (index > -1) {
        filters.value.station_codes.splice(index, 1);
    } else {
        filters.value.station_codes.push(stationCode);
    }
};

const toggleEquipmentType = (equipmentType) => {
    const index = filters.value.equipment_types.indexOf(equipmentType);
    if (index > -1) {
        filters.value.equipment_types.splice(index, 1);
    } else {
        filters.value.equipment_types.push(equipmentType);
    }
};

const isStationSelected = (stationCode) => {
    return filters.value.station_codes.includes(stationCode);
};

const isEquipmentTypeSelected = (equipmentType) => {
    return filters.value.equipment_types.includes(equipmentType);
};

const stationLabel = computed(() => {
    const count = filters.value.station_codes.length;
    if (count === 0) return 'Stasiun';
    if (count === 1) {
        const station = stations.value.find(s => s.code === filters.value.station_codes[0]);
        return station?.description || 'Stasiun';
    }
    return `${count} Stasiun dipilih`;
});

const equipmentTypeLabel = computed(() => {
    const count = filters.value.equipment_types.length;
    if (count === 0) return 'Tipe';
    if (count === 1) return filters.value.equipment_types[0];
    return `${count} Tipe dipilih`;
});

const filteredStations = computed(() => {
    const search = stationSearch.value.toLowerCase();
    return search
        ? stations.value.filter(s => s.description.toLowerCase().includes(search))
        : stations.value;
});

const filteredEquipmentTypes = computed(() => {
    const search = typeSearch.value.toLowerCase();
    return search
        ? equipmentTypes.value.filter(t => t.toLowerCase().includes(search))
        : equipmentTypes.value;
});

const selectAllStations = () => {
    filters.value.station_codes = stations.value.map(s => s.code);
};

const deselectAllStations = () => {
    filters.value.station_codes = [];
};

const selectAllEquipmentTypes = () => {
    filters.value.equipment_types = [...equipmentTypes.value];
};

const deselectAllEquipmentTypes = () => {
    filters.value.equipment_types = [];
};

const rangeDisplay = computed(() => {
    if (!rangeValue.value.start && !rangeValue.value.end) return 'Pick a date';
    if (rangeValue.value.start && rangeValue.value.end) {
        return `${rangeValue.value.start.toString()} - ${rangeValue.value.end.toString()}`;
    }
    if (rangeValue.value.start) return rangeValue.value.start.toString();
    return 'Pick a date';
});

watch(
    rangeValue,
    (val) => {
        const startStr = val?.start?.toString?.();
        const endStr = val?.end?.toString?.();
        if (startStr && endStr) {
            filters.value.date_range = { start: startStr, end: endStr };
            dateRangeStore.setRange({ start: startStr, end: endStr });
            datePopoverOpen.value = false;
        }
    },
    { deep: true },
);

onMounted(async () => {
    stations.value = stationTypes;
    equipmentTypes.value = equipmentTypeOptions;

    // Select all by default
    selectAllStations();
    selectAllEquipmentTypes();

    // Load date range from store
    if (dateRangeStore.start && dateRangeStore.end) {
        filters.value.date_range = {
            start: dateRangeStore.start,
            end: dateRangeStore.end,
        };
        rangeValue.value = {
            start: parseDate(dateRangeStore.start),
            end: parseDate(dateRangeStore.end),
        };
    }

    await fetchPlantDetail();
    await fetchEquipment();
});
const goBack = () => {
    // Check if there's a previous page in browser history
    const referrer = document.referrer;
    const currentOrigin = window.location.origin;

    // If referrer is from our site, check the path
    if (referrer.startsWith(currentOrigin)) {
        const referrerPath = new URL(referrer).pathname;

        // If coming from regional detail page
        if (referrerPath.startsWith('/regions/')) {
            router.visit(`/regions/${plant.value.regional_id}`);
            return;
        }

        // If coming from pabrik list page
        if (referrerPath === '/pabrik') {
            router.visit('/pabrik');
            return;
        }
    }

    // Default: go to dashboard (e.g., from global search or direct link)
    router.visit('/dashboard');
};

const backButtonLabel = computed(() => {
    const referrer = document.referrer;
    const currentOrigin = window.location.origin;

    if (referrer.startsWith(currentOrigin)) {
        const referrerPath = new URL(referrer).pathname;

        if (referrerPath.startsWith('/regions/')) {
            return 'Back to Regional';
        }

        if (referrerPath === '/pabrik') {
            return 'Back to Pabrik';
        }
    }

    return 'Back to Dashboard';
});
</script>

<template>

    <Head :title="plant?.name || 'Plant Detail'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="w-full space-y-2">
                        <Skeleton class="h-8 w-2/3" />
                        <Skeleton class="h-4 w-40" />
                        <Skeleton class="h-3 w-56" />
                        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <Skeleton v-for="i in 6" :key="i" class="h-10 w-full" />
                        </div>
                    </div>
                    <div class="hidden w-72 md:block">
                        <Skeleton class="h-10 w-full" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6">
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
            <div v-else-if="notFound" class="flex min-h-[calc(100vh-15rem)] items-center justify-center px-6">
                <div class="space-y-4 text-center">
                    <p class="text-4xl font-semibold text-primary sm:text-2xl md:text-5xl">
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
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ plant.name }}
                        </h1>
                        <p class="text-muted-foreground">
                            #{{ plant.plant_code }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <button class="hover:underline" @click="navigateToRegional">
                                {{ plant.regional_name }}
                            </button>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 md:flex-nowrap">
                        <Button variant="outline" class="w-full md:w-auto" @click="goBack">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            {{ backButtonLabel }}
                        </Button>
                    </div>
                </div>

                <!-- Statistics Cards Section -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6">
                    <!-- Total Equipment -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Wrench class="h-5 w-5" />
                            <CardTitle class="text-sm">Total Equipment</CardTitle>
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
                            <CardTitle class="text-sm">Total Stations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_stations.toLocaleString(
                                        'id-ID',
                                    )
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
                            <CardTitle class="text-sm">Total Work Orders</CardTitle>
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
                            <CardTitle class="text-sm">Active Work Orders</CardTitle>
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
                            Browse and filter all equipment in {{ plant.name }}. Use the filters below to narrow down
                            your search by date range, station, equipment type, or search terms.
                        </p>
                    </div>

                    <!-- Filter Toggle and Controls -->
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="default" @click="toggleFilterVisibility">
                                <Filter class="h-4 w-4" />
                                <span class="ml-2">Filter</span>
                            </Button>
                        </div>

                        <!-- View Options -->
                        <DataTableViewOptions :table="dataTableRef?.table" />
                    </div>

                    <!-- Toggleable Filter Container -->
                    <transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                        <div v-show="isFilterVisible" class="space-y-4">
                            <!-- Filter Row -->
                            <div class="flex flex-wrap items-end gap-4">
                                <!-- Date Range Picker -->
                                <div class="space-y-2">
                                    <Label>Date Range</Label>
                                    <Popover v-model:open="datePopoverOpen">
                                        <PopoverTrigger as-child>
                                            <Button variant="outline" class="w-full justify-between sm:w-[280px]">
                                                <div class="flex items-center">
                                                    <CalendarIcon class="mr-2 h-4 w-4 shrink-0" />
                                                    <div class="mr-2 h-4 w-px bg-border"></div>
                                                    <span class="text-sm">{{ rangeDisplay }}</span>
                                                </div>
                                                <ChevronsUpDown class="ml-2 h-4 w-4 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-auto p-0" align="start">
                                            <RangeCalendar v-model="rangeValue" :columns="2"
                                                class="rounded-md border" />
                                        </PopoverContent>
                                    </Popover>
                                </div>

                                <!-- Station Filter -->
                                <div class="space-y-2">
                                    <Label>Station</Label>
                                    <Popover v-model:open="stationOpen">
                                        <PopoverTrigger as-child>
                                            <Button variant="outline" class="w-full justify-between sm:w-[200px]">
                                                <div class="flex items-center">
                                                    <MapPin class="mr-2 h-4 w-4 shrink-0" />
                                                    <div class="mr-2 h-4 w-px bg-border"></div>
                                                    {{ stationLabel }}
                                                </div>
                                                <ChevronsUpDown class="ml-2 h-4 w-4 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-[280px] p-0" align="start">
                                            <div class="space-y-2 p-2">
                                                <div class="relative">
                                                    <Search
                                                        class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                                    <Input v-model="stationSearch" placeholder="Search stations..."
                                                        class="pl-8" />
                                                </div>
                                                <div class="flex items-center justify-between border-b pb-2">
                                                    <span class="text-xs font-medium">Stations</span>
                                                    <div class="flex gap-1">
                                                        <Button variant="ghost" size="sm" class="h-6 px-2 text-xs"
                                                            @click="selectAllStations">
                                                            All
                                                        </Button>
                                                        <Button variant="ghost" size="sm" class="h-6 px-2 text-xs"
                                                            @click="deselectAllStations">
                                                            None
                                                        </Button>
                                                    </div>
                                                </div>
                                                <ScrollArea class="h-[200px]">
                                                    <div class="space-y-1">
                                                        <div v-for="station in filteredStations" :key="station.code"
                                                            class="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent">
                                                            <Checkbox :id="`station-${station.code}`"
                                                                :checked="isStationSelected(station.code)"
                                                                @update:checked="toggleStation(station.code)" />
                                                            <label :for="`station-${station.code}`"
                                                                class="flex-1 cursor-pointer text-sm">
                                                                {{ station.description }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                </ScrollArea>
                                            </div>
                                        </PopoverContent>
                                    </Popover>
                                </div>

                                <!-- Equipment Type Filter -->
                                <div class="space-y-2">
                                    <Label>Equipment Type</Label>
                                    <Popover v-model:open="typeOpen">
                                        <PopoverTrigger as-child>
                                            <Button variant="outline" class="w-full justify-between sm:w-[200px]">
                                                <div class="flex items-center">
                                                    <Wrench class="mr-2 h-4 w-4 shrink-0" />
                                                    <div class="mr-2 h-4 w-px bg-border"></div>
                                                    {{ equipmentTypeLabel }}
                                                </div>
                                                <ChevronsUpDown class="ml-2 h-4 w-4 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-[280px] p-0" align="start">
                                            <div class="space-y-2 p-2">
                                                <div class="relative">
                                                    <Search
                                                        class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                                    <Input v-model="typeSearch" placeholder="Search types..."
                                                        class="pl-8" />
                                                </div>
                                                <div class="flex items-center justify-between border-b pb-2">
                                                    <span class="text-xs font-medium">Types</span>
                                                    <div class="flex gap-1">
                                                        <Button variant="ghost" size="sm" class="h-6 px-2 text-xs"
                                                            @click="selectAllEquipmentTypes">
                                                            All
                                                        </Button>
                                                        <Button variant="ghost" size="sm" class="h-6 px-2 text-xs"
                                                            @click="deselectAllEquipmentTypes">
                                                            None
                                                        </Button>
                                                    </div>
                                                </div>
                                                <ScrollArea class="h-[200px]">
                                                    <div class="space-y-1">
                                                        <div v-for="type in filteredEquipmentTypes" :key="type"
                                                            class="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent">
                                                            <Checkbox :id="`type-${type}`"
                                                                :checked="isEquipmentTypeSelected(type)"
                                                                @update:checked="toggleEquipmentType(type)" />
                                                            <label :for="`type-${type}`"
                                                                class="flex-1 cursor-pointer text-sm">
                                                                {{ type }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                </ScrollArea>
                                            </div>
                                        </PopoverContent>
                                    </Popover>
                                </div>

                                <!-- Search Input -->
                                <div class="flex-1 space-y-2">
                                    <Label>Search</Label>
                                    <div class="relative">
                                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input v-model="filters.search" placeholder="Search equipment..."
                                            class="pl-8" />
                                        <Button v-if="filters.search" variant="ghost" size="sm"
                                            class="absolute right-1 top-1 h-7 w-7 p-0" @click="filters.search = ''">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Apply Button -->
                                <div class="flex items-end">
                                    <Button @click="applyFilters" class="w-full sm:w-auto">
                                        Apply Filters
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Equipment Data Table -->
                    <DataTable ref="dataTableRef" :data="equipment" :loading="loading" :error="error"
                        :pagination="pagination" :sorting="sorting" :open-sheet-on-row-click="true"
                        @page-change="handlePageChange" @page-size-change="handlePageSizeChange"
                        @sort-change="handleSortChange" />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
