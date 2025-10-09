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

// Initialize with proper arrays - ensure reactivity
const localFilters = ref({
    date_range: props.filters?.date_range || {
        start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
        end: new Date().toISOString().split('T')[0],
    },
    regional_ids: Array.isArray(props.filters?.regional_ids)
        ? [...props.filters.regional_ids]
        : [],
    plant_ids: Array.isArray(props.filters?.plant_ids)
        ? [...props.filters.plant_ids]
        : [],
    station_ids: Array.isArray(props.filters?.station_ids)
        ? [...props.filters.station_ids]
        : [],
    search: props.filters?.search || '',
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

// Debug watchers to track state changes
watch(
    () => localFilters.value.regional_ids,
    (newVal) => {
        console.log('Regional IDs changed:', newVal);
    },
    { deep: true },
);

watch(
    () => localFilters.value.plant_ids,
    (newVal) => {
        console.log('Plant IDs changed:', newVal);
    },
    { deep: true },
);

watch(
    () => localFilters.value.station_ids,
    (newVal) => {
        console.log('Station IDs changed:', newVal);
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

const fetchPlants = async () => {
    loadingPlants.value = true;
    try {
        const response = await axios.get('/api/plants');
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

const toggleRegional = async (regionalId) => {
    console.log('toggleRegional', regionalId);

    // Ensure array exists
    if (!Array.isArray(localFilters.value.regional_ids)) {
        localFilters.value.regional_ids = [];
    }

    const currentIds = [...localFilters.value.regional_ids];
    const index = currentIds.indexOf(regionalId);

    if (index > -1) {
        // Remove
        currentIds.splice(index, 1);
    } else {
        // Add
        currentIds.push(regionalId);
    }

    // Create new array reference for reactivity
    localFilters.value = {
        ...localFilters.value,
        regional_ids: currentIds,
    };
};

const togglePlant = async (plantId) => {
    console.log('togglePlant', plantId);

    // Ensure array exists
    if (!Array.isArray(localFilters.value.plant_ids)) {
        localFilters.value.plant_ids = [];
    }

    const currentIds = [...localFilters.value.plant_ids];
    const index = currentIds.indexOf(plantId);

    if (index > -1) {
        // Remove
        currentIds.splice(index, 1);
    } else {
        // Add
        currentIds.push(plantId);
    }

    // Create new array reference for reactivity
    localFilters.value = {
        ...localFilters.value,
        plant_ids: currentIds,
    };
};

const toggleStation = async (stationId) => {
    console.log('toggleStation', stationId);

    // Ensure array exists
    if (!Array.isArray(localFilters.value.station_ids)) {
        localFilters.value.station_ids = [];
    }

    const currentIds = [...localFilters.value.station_ids];
    const index = currentIds.indexOf(stationId);

    if (index > -1) {
        // Remove
        currentIds.splice(index, 1);
    } else {
        // Add
        currentIds.push(stationId);
    }

    // Create new array reference for reactivity
    localFilters.value = {
        ...localFilters.value,
        station_ids: currentIds,
    };
};

// Handlers compatible with shadcn-vue Checkbox (v-model:checked / update:checked)
const onRegionalChecked = async (regionalId, checked) => {
    console.log('onRegionalChecked', regionalId, checked);
    const currentlySelected = isRegionalSelected(regionalId);
    const plantIdsForRegional = getPlantIdsForRegional(regionalId);

    if (checked && !currentlySelected) {
        // mark regional as selected template
        await toggleRegional(regionalId);

        // Add all plants under this regional
        const currentPlantIds = new Set(localFilters.value.plant_ids || []);
        plantIdsForRegional.forEach((id) => currentPlantIds.add(id));

        localFilters.value = {
            ...localFilters.value,
            plant_ids: Array.from(currentPlantIds),
        };
    } else if (!checked && currentlySelected) {
        // unmark regional template
        await toggleRegional(regionalId);

        // Remove all plants under this regional
        const toRemove = new Set(plantIdsForRegional);
        const remainingPlantIds = (localFilters.value.plant_ids || []).filter(
            (id) => !toRemove.has(id),
        );

        // Also remove stations that belong to removed plants
        const stationIdsToRemove = new Set(
            stations.value
                .filter((s) => toRemove.has(s.plant_id))
                .map((s) => s.id),
        );
        const remainingStationIds = (
            localFilters.value.station_ids || []
        ).filter((id) => !stationIdsToRemove.has(id));

        localFilters.value = {
            ...localFilters.value,
            plant_ids: remainingPlantIds,
            station_ids: remainingStationIds,
        };
    }
};

const onPlantChecked = async (plantId, checked) => {
    console.log('onPlantChecked', plantId, checked);
    const currentlySelected = isPlantSelected(plantId);
    if (checked && !currentlySelected) {
        await togglePlant(plantId);
    } else if (!checked && currentlySelected) {
        await togglePlant(plantId);
    }

    // If any plant in the regional is deselected, uncheck the regional template
    const plant = plants.value.find((p) => p.id === plantId);
    if (plant) {
        const regionalId = plant.regional_id;
        if (!isRegionalFullySelected(regionalId)) {
            // remove regional from selected templates if present
            if (isRegionalSelected(regionalId)) {
                await toggleRegional(regionalId);
            }
        } else {
            // all plants under this regional are selected, ensure regional is checked
            if (!isRegionalSelected(regionalId)) {
                await toggleRegional(regionalId);
            }
        }
    }
};

const onStationChecked = async (stationId, checked) => {
    console.log('onStationChecked', stationId, checked);
    const currentlySelected = isStationSelected(stationId);
    if (checked && !currentlySelected) {
        await toggleStation(stationId);
    } else if (!checked && currentlySelected) {
        await toggleStation(stationId);
    }
};

// v-model adapters for shadcn-vue Checkbox
const regionalModel = (regionalId) =>
    computed({
        get() {
            return isRegionalSelected(regionalId);
        },
        async set(val) {
            await onRegionalChecked(regionalId, !!val);
        },
    });

const plantModel = (plantId) =>
    computed({
        get() {
            return isPlantSelected(plantId);
        },
        async set(val) {
            await onPlantChecked(plantId, !!val);
        },
    });

const stationModel = (stationId) =>
    computed({
        get() {
            return isStationSelected(stationId);
        },
        async set(val) {
            await onStationChecked(stationId, !!val);
        },
    });

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

    // Do not constrain by selected regionals; show all plants

    // Filter by search
    return search
        ? filtered.filter((p) => p.name.toLowerCase().includes(search))
        : filtered;
});

const filteredStations = computed(() => {
    const search = stationSearch.value.toLowerCase();
    let filtered = stations.value;

    // Only filter by selected plants if we're inside the station dropdown
    // and plant selections exist
    if (
        stationOpen.value &&
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

// Fetch all stations for all plants
const fetchAllStations = async () => {
    loadingStations.value = true;
    try {
        // Fetch all plants first to get their IDs
        if (plants.value.length === 0) {
            await fetchPlants();
        }

        // Fetch stations for all plants
        const promises = plants.value.map((plant) =>
            axios.get(`/api/stations?plant_id=${plant.id}`),
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

const getPlantIdsForRegional = (regionalId) => {
    return plants.value
        .filter((p) => p.regional_id === regionalId)
        .map((p) => p.id);
};

const isRegionalFullySelected = (regionalId) => {
    const allPlantIds = getPlantIdsForRegional(regionalId);
    if (allPlantIds.length === 0) return false;
    const selected = new Set(localFilters.value.plant_ids || []);
    return allPlantIds.every((id) => selected.has(id));
};

onMounted(async () => {
    // Fetch all initial data
    await fetchRegions();
    await fetchPlants();
    await fetchAllStations();

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
                            class="w-full min-w-[300px] justify-between"
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
                                            :model-value="
                                                isRegionalSelected(region.id)
                                            "
                                            @update:model-value="
                                                (val) =>
                                                    onRegionalChecked(
                                                        region.id,
                                                        val,
                                                    )
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
                                            :model-value="
                                                isPlantSelected(plant.id)
                                            "
                                            @update:model-value="
                                                (val) =>
                                                    onPlantChecked(
                                                        plant.id,
                                                        val,
                                                    )
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
                                            :model-value="
                                                isStationSelected(station.id)
                                            "
                                            @update:model-value="
                                                (val) =>
                                                    onStationChecked(
                                                        station.id,
                                                        val,
                                                    )
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
