<script setup lang="js">
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { loadDateRange, saveDateRange } from '@/lib/dateRangeStorage';
import { dateRangeStore } from '@/stores/dateRangeStore';
import axios from 'axios';
import {
    Calendar as CalendarIcon,
    Check,
    ChevronsUpDown,
    Search,
} from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

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

const localFilters = ref({ ...props.filters });

// Initialize from localStorage if available
const stored = loadDateRange();
if (stored?.start && stored?.end) {
    localFilters.value.date_range = { start: stored.start, end: stored.end };
    uiDateRange.value = {
        from: new Date(stored.start),
        to: new Date(stored.end),
    };
}

// sync from global store into local on mount
onMounted(() => {
    if (dateRangeStore.start.value && dateRangeStore.end.value) {
        localFilters.value.date_range = {
            start: dateRangeStore.start.value,
            end: dateRangeStore.end.value,
        };
        uiDateRange.value = {
            from: new Date(dateRangeStore.start.value),
            to: new Date(dateRangeStore.end.value),
        };
    }
});

// Combobox states
const regionalOpen = ref(false);
const plantOpen = ref(false);
const stationOpen = ref(false);
// Date range state for popover calendar
const datePopoverOpen = ref(false);
const uiDateRange = ref({
    from: props.filters?.date_range?.start
        ? new Date(props.filters.date_range.start)
        : null,
    to: props.filters?.date_range?.end
        ? new Date(props.filters.date_range.end)
        : null,
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
const isRangeEmpty = () => !uiDateRange.value.from && !uiDateRange.value.to;
const rangeDisplay = () => {
    if (!uiDateRange.value.from && !uiDateRange.value.to)
        return 'Pilih periode';
    const toStr = (d) => d.toISOString().split('T')[0];
    const fromStr = uiDateRange.value.from ? toStr(uiDateRange.value.from) : '';
    const toDateStr = uiDateRange.value.to ? toStr(uiDateRange.value.to) : '';
    return `${fromStr} - ${toDateStr}`;
};
const handleUiDateRangeChange = (newRange) => {
    lastRangeEvent.value = newRange;
    const normalized = normalizeRange(newRange);
    uiDateRange.value = normalized;
    const toStr = (d) =>
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const startStr = normalized.from ? toStr(new Date(normalized.from)) : null;
    const endStr = normalized.to ? toStr(new Date(normalized.to)) : null;
    if (startStr && endStr) {
        localFilters.value.date_range = { start: startStr, end: endStr };
        saveDateRange(localFilters.value.date_range);
        dateRangeStore.setRange(localFilters.value.date_range);
        datePopoverOpen.value = false;
    }
};

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
        saveDateRange(localFilters.value.date_range);
        dateRangeStore.setRange(localFilters.value.date_range);
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

const handleRegionalChange = async (selectedItem) => {
    const id = selectedItem?.value ? parseInt(selectedItem.value) : undefined;

    // Update filters
    localFilters.value.regional_id = id;
    localFilters.value.plant_id = undefined;
    localFilters.value.station_id = undefined;

    // Close dropdown
    regionalOpen.value = false;

    // Fetch dependent data - always fetch plants (all or filtered)
    await fetchPlants(id);
    stations.value = [];
};

const handlePlantChange = async (selectedItem) => {
    const id = selectedItem?.value ? parseInt(selectedItem.value) : undefined;

    // Update filters
    localFilters.value.plant_id = id;
    localFilters.value.station_id = undefined;

    // Close dropdown
    plantOpen.value = false;

    // Fetch dependent data
    if (id) {
        await fetchStations(id);
    } else {
        stations.value = [];
    }
};

const handleStationChange = (selectedItem) => {
    const id = selectedItem?.value ? parseInt(selectedItem.value) : undefined;

    // Update filters
    localFilters.value.station_id = id;

    // Close dropdown
    stationOpen.value = false;
};

// Helper function to get selected object for comboboxes
const getSelectedItem = (id, items, defaultLabel, labelKey = 'name') => {
    if (!id) {
        return { value: '', label: defaultLabel };
    }

    const item = items.value.find((item) => item.id === id);
    return item
        ? { value: item.id.toString(), label: item[labelKey] }
        : { value: '', label: defaultLabel };
};

// Get selected objects for comboboxes
const getSelectedRegional = () => {
    return getSelectedItem(
        localFilters.value.regional_id,
        regions,
        'Semua Regional',
        'name',
    );
};

const getSelectedPlant = () => {
    return getSelectedItem(
        localFilters.value.plant_id,
        plants,
        'Semua Pabrik',
        'name',
    );
};

const getSelectedStation = () => {
    return getSelectedItem(
        localFilters.value.station_id,
        stations,
        'Semua Stasiun',
        'description',
    );
};

const clearFilters = async () => {
    // Reset filters to default
    localFilters.value = {
        date_range: {
            start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0],
            end: new Date().toISOString().split('T')[0],
        },
    };

    // Clear dependent data
    plants.value = [];
    stations.value = [];

    // Close all dropdowns
    regionalOpen.value = false;
    plantOpen.value = false;
    stationOpen.value = false;

    await nextTick();
};

const hasActiveFilters = () => {
    return (
        localFilters.value.regional_id ||
        localFilters.value.plant_id ||
        localFilters.value.station_id ||
        localFilters.value.date_range.start !==
            new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0] ||
        localFilters.value.date_range.end !==
            new Date().toISOString().split('T')[0]
    );
};

onMounted(async () => {
    // Fetch initial data
    await fetchRegions();

    // Always fetch all plants initially
    await fetchPlants();

    // Load dependent data if filters are already set
    if (localFilters.value.plant_id) {
        await fetchStations(localFilters.value.plant_id);
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
                <Combobox
                    v-model:open="regionalOpen"
                    :model-value="getSelectedRegional()"
                    @update:model-value="handleRegionalChange"
                    by="label"
                >
                    <ComboboxAnchor as-child>
                        <ComboboxTrigger as-child>
                            <Button
                                variant="outline"
                                class="w-full justify-between"
                            >
                                {{
                                    getSelectedRegional()?.label ??
                                    'Semua Regional'
                                }}
                                <ChevronsUpDown
                                    class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </ComboboxTrigger>
                    </ComboboxAnchor>
                    <ComboboxList>
                        <div class="relative flex w-full items-center">
                            <ComboboxInput
                                class="h-10 rounded-none border-0 border-b focus-visible:ring-0"
                                placeholder="Cari Regional..."
                            />
                            <span
                                class="absolute inset-y-0 start-0 flex items-center justify-center px-3"
                            >
                                <Search class="size-4 text-muted-foreground" />
                            </span>
                        </div>
                        <ComboboxEmpty>No regional found.</ComboboxEmpty>
                        <ComboboxGroup>
                            <ComboboxItem
                                :value="{ value: '', label: 'Semua Regional' }"
                            >
                                Semua Regional
                                <ComboboxItemIndicator>
                                    <Check class="ml-auto h-4 w-4" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                            <ComboboxItem
                                v-for="region in regions"
                                :key="region.id"
                                :value="{
                                    value: region.id.toString(),
                                    label: region.name,
                                }"
                            >
                                {{ region.name }}
                                <ComboboxItemIndicator>
                                    <Check class="ml-auto h-4 w-4" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                        </ComboboxGroup>
                    </ComboboxList>
                </Combobox>
            </div>

            <!-- Plant Filter -->
            <div class="min-w-[200px] space-y-2">
                <Label>Pabrik</Label>
                <Combobox
                    v-model:open="plantOpen"
                    :model-value="getSelectedPlant()"
                    @update:model-value="handlePlantChange"
                    :disabled="!localFilters.regional_id"
                    by="label"
                >
                    <ComboboxAnchor as-child>
                        <ComboboxTrigger as-child>
                            <Button
                                variant="outline"
                                class="w-full justify-between"
                                :disabled="!localFilters.regional_id"
                            >
                                {{
                                    getSelectedPlant()?.label ?? 'Semua Pabrik'
                                }}
                                <ChevronsUpDown
                                    class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </ComboboxTrigger>
                    </ComboboxAnchor>
                    <ComboboxList>
                        <div class="relative flex w-full items-center">
                            <ComboboxInput
                                class="h-10 rounded-none border-0 border-b focus-visible:ring-0"
                                placeholder="Cari Pabrik..."
                            />
                            <span
                                class="absolute inset-y-0 start-0 flex items-center justify-center px-3"
                            >
                                <Search class="size-4 text-muted-foreground" />
                            </span>
                        </div>
                        <ComboboxEmpty>No plant found.</ComboboxEmpty>
                        <ComboboxGroup>
                            <ComboboxItem
                                :value="{ value: '', label: 'Semua Pabrik' }"
                            >
                                Semua Pabrik
                                <ComboboxItemIndicator>
                                    <Check class="ml-auto h-4 w-4" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                            <ComboboxItem
                                v-for="plant in plants"
                                :key="plant.id"
                                :value="{
                                    value: plant.id.toString(),
                                    label: plant.name,
                                }"
                            >
                                {{ plant.name }}
                                <ComboboxItemIndicator>
                                    <Check class="ml-auto h-4 w-4" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                        </ComboboxGroup>
                    </ComboboxList>
                </Combobox>
            </div>

            <!-- Station Filter -->
            <div class="min-w-[200px] space-y-2">
                <Label>Stasiun</Label>
                <Combobox
                    v-model:open="stationOpen"
                    :model-value="getSelectedStation()"
                    @update:model-value="handleStationChange"
                    :disabled="!localFilters.plant_id"
                    by="label"
                >
                    <ComboboxAnchor as-child>
                        <ComboboxTrigger as-child>
                            <Button
                                variant="outline"
                                class="w-full justify-between"
                                :disabled="!localFilters.plant_id"
                            >
                                {{
                                    getSelectedStation()?.label ??
                                    'Semua Stasiun'
                                }}
                                <ChevronsUpDown
                                    class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </ComboboxTrigger>
                    </ComboboxAnchor>
                    <ComboboxList>
                        <div class="relative flex w-full items-center">
                            <ComboboxInput
                                class="h-10 rounded-none border-0 border-b focus-visible:ring-0"
                                placeholder="Cari Stasiun..."
                            />
                            <span
                                class="absolute inset-y-0 start-0 flex items-center justify-center px-3"
                            >
                                <Search class="size-4 text-muted-foreground" />
                            </span>
                        </div>
                        <ComboboxEmpty>No station found.</ComboboxEmpty>
                        <ComboboxGroup>
                            <ComboboxItem
                                :value="{ value: '', label: 'Semua Stasiun' }"
                            >
                                Semua Stasiun
                                <ComboboxItemIndicator>
                                    <Check class="ml-auto h-4 w-4" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                            <ComboboxItem
                                v-for="station in stations"
                                :key="station.id"
                                :value="{
                                    value: station.id.toString(),
                                    label: station.description,
                                }"
                            >
                                {{ station.description }}
                                <ComboboxItemIndicator>
                                    <Check class="ml-auto h-4 w-4" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                        </ComboboxGroup>
                    </ComboboxList>
                </Combobox>
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
                            v-model="uiDateRange"
                            :number-of-months="2"
                            @update:model-value="handleUiDateRangeChange"
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
        <!-- Debug Panel -->
        <div class="mt-4 rounded-md border p-3 text-xs">
            <div class="mb-2 font-semibold">Debug</div>
            <div class="grid grid-cols-1 gap-1">
                <div>datePopoverOpen: {{ String(datePopoverOpen) }}</div>
                <div>
                    uiDateRange.from:
                    {{
                        uiDateRange?.from?.toISOString?.() ??
                        String(uiDateRange?.from)
                    }}
                </div>
                <div>
                    uiDateRange.to:
                    {{
                        uiDateRange?.to?.toISOString?.() ??
                        String(uiDateRange?.to)
                    }}
                </div>
                <div>
                    localFilters.date_range.start:
                    {{ localFilters?.date_range?.start }}
                </div>
                <div>
                    localFilters.date_range.end:
                    {{ localFilters?.date_range?.end }}
                </div>
                <div>lastRangeEvent: {{ JSON.stringify(lastRangeEvent) }}</div>
            </div>
        </div>
    </div>
</template>
