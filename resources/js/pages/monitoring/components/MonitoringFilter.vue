<script setup lang="js">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { getLocalTimeZone, parseDate } from '@internationalized/date';
import axios from 'axios';
import {
    Calendar as CalendarIcon,
    ChevronsUpDown,
    Search,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['filter-change']);

const regions = ref([]);
const plants = ref([]);
const stations = ref([]);
const loadingRegions = ref(false);
const loadingPlants = ref(false);
const loadingStations = ref(false);

const localFilters = ref({
    ...props.filters,
    regional_ids: props.filters.regional_ids || [],
    plant_ids: props.filters.plant_ids || [],
    station_ids: props.filters.station_ids || [],
});

// Initialize from localStorage if available
const dateRange = useDateRangeStore();

// sync from pinia store into local on mount
onMounted(() => {
    if (dateRange.start && dateRange.end) {
        localFilters.value.date_range = {
            start: dateRange.start,
            end: dateRange.end,
        };
        rangeValue.value = {
            start: parseDate(dateRange.start),
            end: parseDate(dateRange.end),
        };
    }
});

// Popover states for multi-select
const regionalOpen = ref(false);
const plantOpen = ref(false);
const stationOpen = ref(false);

// Search states
const regionalSearch = ref('');
const plantSearch = ref('');
const stationSearch = ref('');
// Date range state for popover calendar (CalendarDate model like Reka example)
const datePopoverOpen = ref(false);
const rangeValue = ref({
    start: props.filters?.date_range?.start
        ? parseDate(props.filters.date_range.start)
        : undefined,
    end: props.filters?.date_range?.end
        ? parseDate(props.filters.date_range.end)
        : undefined,
});
const lastRangeEvent = ref(null);
const normalizeRange = (range) => {
    const from = range?.from ?? range?.start ?? null;
    const to = range?.to ?? range?.end ?? null;
    return {
        from: from ? new Date(from) : null,
        to: to ? new Date(to) : null,
    };
};
const isRangeEmpty = () => !rangeValue.value.start && !rangeValue.value.end;
const formatIndo = (d) => {
    if (!d) return '';
    const dd = new Date(d);
    const day = String(dd.getDate()).padStart(2, '0');
    const month = String(dd.getMonth() + 1).padStart(2, '0');
    const year = dd.getFullYear();
    return `${day}-${month}-${year}`;
};
const tz = getLocalTimeZone();
const rangeDisplay = () => {
    if (!rangeValue.value.start && !rangeValue.value.end) return 'Pick a date';
    if (rangeValue.value.start && rangeValue.value.end) {
        return `${rangeValue.value.start.toString()} - ${rangeValue.value.end.toString()}`;
    }
    if (rangeValue.value.start) return rangeValue.value.start.toString();
    return 'Pick a date';
};
const handleRangeUpdate = (val) => {
    lastRangeEvent.value = val;
};

// Sync localFilters when rangeValue (v-model) changes
watch(
    rangeValue,
    (val) => {
        const startStr = val?.start?.toString?.();
        const endStr = val?.end?.toString?.();
        if (startStr && endStr) {
            localFilters.value.date_range = { start: startStr, end: endStr };
            datePopoverOpen.value = false;
        }
    },
    { deep: true },
);

// Watch for prop changes
watch(
    () => props.filters,
    (newFilters) => {
        if (JSON.stringify(newFilters) !== JSON.stringify(localFilters.value)) {
            localFilters.value = { ...newFilters };
        }
    },
    { deep: true },
);

// Apply filters on demand via button
const applyFilters = async () => {
    await nextTick();
    if (
        localFilters.value?.date_range?.start &&
        localFilters.value?.date_range?.end
    ) {
        dateRange.setRange(localFilters.value.date_range);
    }
    emit('filter-change', { ...localFilters.value });
};

// Date range changes are now handled by the custom DateRangePicker component

const fetchRegions = async () => {
    loadingRegions.value = true;
    try {
        const response = await axios.get('/api/regions');
        regions.value = response.data;
    } catch (error) {
        console.error('Error fetching regions:', error);
    } finally {
        loadingRegions.value = false;
    }
};

const fetchPlants = async (regionalId) => {
    loadingPlants.value = true;
    try {
        let url = '/api/plants';
        if (regionalId) {
            url += `?regional_id=${regionalId}`;
        }
        const response = await axios.get(url);
        plants.value = response.data;
    } catch (error) {
        console.error('Error fetching plants:', error);
    } finally {
        loadingPlants.value = false;
    }
};

const fetchStations = async (plantId) => {
    if (!plantId) {
        stations.value = [];
        return;
    }

    loadingStations.value = true;
    try {
        const response = await axios.get(`/api/stations?plant_id=${plantId}`);
        stations.value = response.data;
    } catch (error) {
        console.error('Error fetching stations:', error);
    } finally {
        loadingStations.value = false;
    }
};

const toggleRegional = (regionalId) => {
    const ids = localFilters.value.regional_ids || [];
    const index = ids.indexOf(regionalId);

    if (index > -1) {
        localFilters.value.regional_ids = ids.filter((id) => id !== regionalId);
    } else {
        localFilters.value.regional_ids = [...ids, regionalId];
    }
};

const togglePlant = (plantId) => {
    const ids = localFilters.value.plant_ids || [];
    const index = ids.indexOf(plantId);

    if (index > -1) {
        localFilters.value.plant_ids = ids.filter((id) => id !== plantId);
    } else {
        localFilters.value.plant_ids = [...ids, plantId];
    }
};

const toggleStation = (stationId) => {
    const ids = localFilters.value.station_ids || [];
    const index = ids.indexOf(stationId);

    if (index > -1) {
        localFilters.value.station_ids = ids.filter((id) => id !== stationId);
    } else {
        localFilters.value.station_ids = [...ids, stationId];
    }
};

const isRegionalSelected = (regionalId) => {
    return (localFilters.value.regional_ids || []).includes(regionalId);
};

const isPlantSelected = (plantId) => {
    return (localFilters.value.plant_ids || []).includes(plantId);
};

const isStationSelected = (stationId) => {
    return (localFilters.value.station_ids || []).includes(stationId);
};

// Computed properties for display labels
const regionalLabel = computed(() => {
    const count = (localFilters.value.regional_ids || []).length;
    return count === 0
        ? 'Semua Regional'
        : count === 1
          ? regions.value.find(
                (r) => r.id === localFilters.value.regional_ids[0],
            )?.name || 'Regional'
          : `${count} Regional dipilih`;
});

const plantLabel = computed(() => {
    const count = (localFilters.value.plant_ids || []).length;
    return count === 0
        ? 'Semua Pabrik'
        : count === 1
          ? plants.value.find((p) => p.id === localFilters.value.plant_ids[0])
                ?.name || 'Pabrik'
          : `${count} Pabrik dipilih`;
});

const stationLabel = computed(() => {
    const count = (localFilters.value.station_ids || []).length;
    return count === 0
        ? 'Semua Stasiun'
        : count === 1
          ? stations.value.find(
                (s) => s.id === localFilters.value.station_ids[0],
            )?.description || 'Stasiun'
          : `${count} Stasiun dipilih`;
});

// Filtered lists based on search
const filteredRegions = computed(() => {
    const search = regionalSearch.value.toLowerCase();
    return search
        ? regions.value.filter((r) => r.name.toLowerCase().includes(search))
        : regions.value;
});

const filteredPlants = computed(() => {
    const search = plantSearch.value.toLowerCase();
    let filtered = plants.value;

    // Filter by selected regionals if any
    if (
        localFilters.value.regional_ids &&
        localFilters.value.regional_ids.length > 0
    ) {
        filtered = filtered.filter((p) =>
            localFilters.value.regional_ids.includes(p.regional_id),
        );
    }

    // Filter by search
    return search
        ? filtered.filter((p) => p.name.toLowerCase().includes(search))
        : filtered;
});

const filteredStations = computed(() => {
    const search = stationSearch.value.toLowerCase();
    let filtered = stations.value;

    // Filter by selected plants if any
    if (
        localFilters.value.plant_ids &&
        localFilters.value.plant_ids.length > 0
    ) {
        filtered = filtered.filter((s) =>
            localFilters.value.plant_ids.includes(s.plant_id),
        );
    }

    // Filter by search
    return search
        ? filtered.filter((s) => s.description.toLowerCase().includes(search))
        : filtered;
});

const clearFilters = async () => {
    // Reset filters to default
    localFilters.value = {
        date_range: {
            start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0],
            end: new Date().toISOString().split('T')[0],
        },
        regional_ids: [],
        plant_ids: [],
        station_ids: [],
    };

    // Clear search states
    regionalSearch.value = '';
    plantSearch.value = '';
    stationSearch.value = '';

    // Close all dropdowns
    regionalOpen.value = false;
    plantOpen.value = false;
    stationOpen.value = false;

    await nextTick();
};

// Watch for regional selection changes to auto-fetch plants
watch(
    () => localFilters.value.regional_ids,
    async (newIds) => {
        // Fetch plants when regional selection changes
        if (newIds && newIds.length > 0) {
            // Fetch all plants, filtering will be done client-side
            await fetchPlants();
        } else {
            // Fetch all plants when no regional is selected
            await fetchPlants();
        }
    },
    { deep: true },
);

// Watch for plant selection changes to auto-fetch stations
watch(
    () => localFilters.value.plant_ids,
    async (newIds) => {
        // Fetch stations when plant selection changes
        if (newIds && newIds.length > 0) {
            // Fetch all stations, filtering will be done client-side
            await fetchAllStations(newIds);
        }
    },
    { deep: true },
);

// Fetch all stations for selected plants
const fetchAllStations = async (plantIds) => {
    if (!plantIds || plantIds.length === 0) {
        stations.value = [];
        return;
    }

    loadingStations.value = true;
    try {
        // Fetch stations for all selected plants
        const promises = plantIds.map((plantId) =>
            axios.get(`/api/stations?plant_id=${plantId}`),
        );
        const responses = await Promise.all(promises);

        // Combine and deduplicate stations
        const allStations = responses.flatMap((r) => r.data);
        const uniqueStations = Array.from(
            new Map(allStations.map((s) => [s.id, s])).values(),
        );

        stations.value = uniqueStations;
    } catch (error) {
        console.error('Error fetching stations:', error);
    } finally {
        loadingStations.value = false;
    }
};

onMounted(async () => {
    // Fetch initial data
    await fetchRegions();
    await fetchPlants();

    // Load stations if plant filters are already set
    if (
        localFilters.value.plant_ids &&
        localFilters.value.plant_ids.length > 0
    ) {
        await fetchAllStations(localFilters.value.plant_ids);
    }

    await nextTick();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Filter Controls -->
        <div class="flex flex-wrap items-end gap-4">
            <!-- Regional Filter -->
            <div class="min-w-[200px] space-y-2">
                <Label>Regional</Label>
                <Popover v-model:open="regionalOpen">
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-full justify-between"
                        >
                            {{ regionalLabel }}
                            <ChevronsUpDown
                                class="ml-2 h-4 w-4 shrink-0 opacity-50"
                            />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-[250px] p-0">
                        <div class="flex flex-col">
                            <div
                                class="relative flex w-full items-center border-b"
                            >
                                <input
                                    v-model="regionalSearch"
                                    type="text"
                                    placeholder="Cari Regional..."
                                    class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none"
                                />
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center justify-center px-3"
                                >
                                    <Search
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                </span>
                                <button
                                    v-if="regionalSearch"
                                    @click="regionalSearch = ''"
                                    class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <ScrollArea class="h-[200px]">
                                <div class="p-2">
                                    <div
                                        v-if="filteredRegions.length === 0"
                                        class="py-6 text-center text-sm text-muted-foreground"
                                    >
                                        No regional found.
                                    </div>
                                    <div
                                        v-for="region in filteredRegions"
                                        :key="region.id"
                                        class="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                    >
                                        <Checkbox
                                            :id="`regional-${region.id}`"
                                            :checked="
                                                isRegionalSelected(region.id)
                                            "
                                            @update:checked="
                                                toggleRegional(region.id)
                                            "
                                        />
                                        <label
                                            :for="`regional-${region.id}`"
                                            class="flex-1 cursor-pointer text-sm"
                                        >
                                            {{ region.name }}
                                        </label>
                                    </div>
                                </div>
                            </ScrollArea>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Plant Filter -->
            <div class="min-w-[200px] space-y-2">
                <Label>Pabrik</Label>
                <Popover v-model:open="plantOpen">
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-full justify-between"
                            :disabled="
                                !localFilters.regional_ids ||
                                localFilters.regional_ids.length === 0
                            "
                        >
                            {{ plantLabel }}
                            <ChevronsUpDown
                                class="ml-2 h-4 w-4 shrink-0 opacity-50"
                            />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-[250px] p-0">
                        <div class="flex flex-col">
                            <div
                                class="relative flex w-full items-center border-b"
                            >
                                <input
                                    v-model="plantSearch"
                                    type="text"
                                    placeholder="Cari Pabrik..."
                                    class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none"
                                />
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center justify-center px-3"
                                >
                                    <Search
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                </span>
                                <button
                                    v-if="plantSearch"
                                    @click="plantSearch = ''"
                                    class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <ScrollArea class="h-[200px]">
                                <div class="p-2">
                                    <div
                                        v-if="filteredPlants.length === 0"
                                        class="py-6 text-center text-sm text-muted-foreground"
                                    >
                                        No plant found.
                                    </div>
                                    <div
                                        v-for="plant in filteredPlants"
                                        :key="plant.id"
                                        class="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                    >
                                        <Checkbox
                                            :id="`plant-${plant.id}`"
                                            :checked="isPlantSelected(plant.id)"
                                            @update:checked="
                                                togglePlant(plant.id)
                                            "
                                        />
                                        <label
                                            :for="`plant-${plant.id}`"
                                            class="flex-1 cursor-pointer text-sm"
                                        >
                                            {{ plant.name }}
                                        </label>
                                    </div>
                                </div>
                            </ScrollArea>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Station Filter -->
            <div class="min-w-[200px] space-y-2">
                <Label>Stasiun</Label>
                <Popover v-model:open="stationOpen">
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-full justify-between"
                            :disabled="
                                !localFilters.plant_ids ||
                                localFilters.plant_ids.length === 0
                            "
                        >
                            {{ stationLabel }}
                            <ChevronsUpDown
                                class="ml-2 h-4 w-4 shrink-0 opacity-50"
                            />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-[250px] p-0">
                        <div class="flex flex-col">
                            <div
                                class="relative flex w-full items-center border-b"
                            >
                                <input
                                    v-model="stationSearch"
                                    type="text"
                                    placeholder="Cari Stasiun..."
                                    class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none"
                                />
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center justify-center px-3"
                                >
                                    <Search
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                </span>
                                <button
                                    v-if="stationSearch"
                                    @click="stationSearch = ''"
                                    class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <ScrollArea class="h-[200px]">
                                <div class="p-2">
                                    <div
                                        v-if="filteredStations.length === 0"
                                        class="py-6 text-center text-sm text-muted-foreground"
                                    >
                                        No station found.
                                    </div>
                                    <div
                                        v-for="station in filteredStations"
                                        :key="station.id"
                                        class="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                    >
                                        <Checkbox
                                            :id="`station-${station.id}`"
                                            :checked="
                                                isStationSelected(station.id)
                                            "
                                            @update:checked="
                                                toggleStation(station.id)
                                            "
                                        />
                                        <label
                                            :for="`station-${station.id}`"
                                            class="flex-1 cursor-pointer text-sm"
                                        >
                                            {{ station.description }}
                                        </label>
                                    </div>
                                </div>
                            </ScrollArea>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Date Range Filter -->
            <div class="min-w-[300px] space-y-2">
                <Label>Periode Jam Jalan</Label>
                <Popover v-model:open="datePopoverOpen">
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            :class="[
                                'w-[300px] justify-start text-left font-normal',
                                isRangeEmpty() ? 'text-muted-foreground' : '',
                            ]"
                        >
                            <CalendarIcon class="mr-2 h-4 w-4" />
                            {{ rangeDisplay() }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0">
                        <RangeCalendar
                            v-model="rangeValue"
                            :number-of-months="2"
                            @update:value="handleRangeUpdate"
                        />
                    </PopoverContent>
                </Popover>
            </div>

            <!-- Process Button -->
            <div class="space-y-2">
                <Label class="opacity-0 select-none">Proses</Label>
                <Button variant="default" class="w-full" @click="applyFilters">
                    Proses
                </Button>
            </div>
        </div>
    </div>
</template>
