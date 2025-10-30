<script setup lang="js">
import DataTableViewOptions from '@/components/tables/monitoring/DataTableViewOptions.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { parseDate } from '@internationalized/date';
import axios from 'axios';
import {
    Building2,
    ChevronDown,
    ChevronsUpDown,
    ChevronUp,
    Clock,
    Map,
    MapPin,
    Search,
    Tag,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    disableStore: {
        type: Boolean,
        default: false,
    },
    hideRegional: {
        type: Boolean,
        default: false,
    },
    hidePlant: {
        type: Boolean,
        default: false,
    },
    hideSearch: {
        type: Boolean,
        default: false,
    },
    searchPlaceholder: {
        type: String,
        default: 'Search equipment...',
    },
    table: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['filter-change']);

const FILTER_VISIBILITY_KEY = 'monitoring_filter_visible_mobile';
const isFilterVisibleMobile = ref(
    localStorage.getItem(FILTER_VISIBILITY_KEY) !== 'false',
);

watch(isFilterVisibleMobile, (val) =>
    localStorage.setItem(FILTER_VISIBILITY_KEY, String(val)),
);

const toggleMobileFilter = () =>
    (isFilterVisibleMobile.value = !isFilterVisibleMobile.value);

const regions = ref([]);
const plants = ref([]);
const stations = ref([]);
const equipmentTypes = ref([]);
const localFilters = ref({
    date_range: props.filters?.date_range || {
        start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
        end: new Date().toISOString().split('T')[0],
    },
    regional_uuids: Array.isArray(props.filters?.regional_uuids)
        ? [...props.filters.regional_uuids]
        : [],
    plant_uuids: Array.isArray(props.filters?.plant_uuids)
        ? [...props.filters.plant_uuids]
        : [],
    station_codes: Array.isArray(props.filters?.station_codes)
        ? [...props.filters.station_codes]
        : [],
    equipment_types: Array.isArray(props.filters?.equipment_types)
        ? [...props.filters.equipment_types]
        : [],
    search: props.filters?.search || '',
});

const dateRange = useDateRangeStore();

const regionalOpen = ref(false);
const plantOpen = ref(false);
const stationOpen = ref(false);
const typeOpen = ref(false);
const regionalSearch = ref('');
const plantSearch = ref('');
const stationSearch = ref('');
const typeSearch = ref('');
const datePopoverOpen = ref(false);
const rangeValue = ref({
    start: props.filters?.date_range?.start
        ? parseDate(props.filters.date_range.start)
        : undefined,
    end: props.filters?.date_range?.end
        ? parseDate(props.filters.date_range.end)
        : undefined,
});
const isRangeEmpty = () => !rangeValue.value.start && !rangeValue.value.end;
const rangeDisplay = () => {
    if (!rangeValue.value.start && !rangeValue.value.end) return 'Pick a date';
    if (rangeValue.value.start && rangeValue.value.end)
        return `${rangeValue.value.start.toString()} - ${rangeValue.value.end.toString()}`;
    return rangeValue.value.start
        ? rangeValue.value.start.toString()
        : 'Pick a date';
};

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

const applyFilters = async () => {
    await nextTick();
    validateAndAutoSelect();

    const selectedRegionalUuids = localFilters.value.regional_uuids || [];
    const filteredPlantUuids =
        selectedRegionalUuids.length > 0
            ? (localFilters.value.plant_uuids || []).filter((plantUuid) => {
                  const plant = plants.value.find((p) => p.uuid === plantUuid);
                  return (
                      plant &&
                      selectedRegionalUuids.includes(plant.regional_uuid)
                  );
              })
            : localFilters.value.plant_uuids || [];

    const finalFilters = {
        ...localFilters.value,
        plant_uuids: filteredPlantUuids,
    };

    if (
        !props.disableStore &&
        finalFilters?.date_range?.start &&
        finalFilters?.date_range?.end
    ) {
        dateRange.setRange(finalFilters.date_range);
    }

    emit('filter-change', finalFilters);
};

const validateAndAutoSelect = () => {
    if (
        !props.hideRegional &&
        !(localFilters.value.regional_uuids || []).length &&
        regions.value.length
    )
        selectAllRegions();
    if (
        !props.hidePlant &&
        !(localFilters.value.plant_uuids || []).length &&
        plants.value.length
    )
        selectAllPlants();
    if (
        !(localFilters.value.station_codes || []).length &&
        stations.value.length
    )
        selectAllStations();
    if (
        !(localFilters.value.equipment_types || []).length &&
        equipmentTypes.value.length
    )
        selectAllEquipmentTypes();
};

const fetchRegions = async () => {
    try {
        regions.value = (await axios.get('/api/regions')).data;
    } catch (error) {
        console.error('Error fetching regions:', error);
    }
};

const fetchPlants = async () => {
    try {
        plants.value = (await axios.get('/api/plants')).data;
    } catch (error) {
        console.error('Error fetching plants:', error);
    }
};

const stationTypes = ref([
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
]);

const equipmentTypeOptions = ref([
    { id: 1, label: 'Mesin Produksi' },
    { id: 2, label: 'Kendaraan' },
    { id: 3, label: 'Alat dan Utilitas' },
    { id: 4, label: 'IT & Telekomunikasi' },
    { id: 5, label: 'Aset PMN' },
]);

const fetchStations = () => (stations.value = stationTypes.value);
const fetchEquipmentTypes = () =>
    (equipmentTypes.value = equipmentTypeOptions.value.map((t) => t.id));

const toggleRegional = (regionalUuid) => {
    const currentUuids = [...(localFilters.value.regional_uuids || [])];
    const index = currentUuids.indexOf(regionalUuid);
    index > -1
        ? currentUuids.splice(index, 1)
        : currentUuids.push(regionalUuid);
    localFilters.value = {
        ...localFilters.value,
        regional_uuids: currentUuids,
    };
    cleanupPlantSelection();
};

const cleanupPlantSelection = () => {
    const selectedRegionalUuids = localFilters.value.regional_uuids || [];
    const currentPlantUuids = localFilters.value.plant_uuids || [];
    if (selectedRegionalUuids.length > 0) {
        const validPlantUuids = currentPlantUuids.filter((plantUuid) => {
            const plant = plants.value.find((p) => p.uuid === plantUuid);
            return plant && selectedRegionalUuids.includes(plant.regional_uuid);
        });
        if (validPlantUuids.length !== currentPlantUuids.length) {
            localFilters.value = {
                ...localFilters.value,
                plant_uuids: validPlantUuids,
            };
        }
    }
};

const toggleArray = (key, value) => {
    const current = [...(localFilters.value[key] || [])];
    const index = current.indexOf(value);
    index > -1 ? current.splice(index, 1) : current.push(value);
    localFilters.value = { ...localFilters.value, [key]: current };
};

const togglePlant = (plantUuid) => toggleArray('plant_uuids', plantUuid);
const toggleStation = (stationCode) =>
    toggleArray('station_codes', stationCode);
const toggleEquipmentType = (equipmentType) =>
    toggleArray('equipment_types', equipmentType);

const onRegionalChecked = (regionalUuid, checked) => {
    const currentlySelected = isRegionalSelected(regionalUuid);
    const plantUuidsForRegional = getPlantUuidsForRegional(regionalUuid);

    if (checked && !currentlySelected) {
        toggleRegional(regionalUuid);
        const currentPlantUuids = new Set(localFilters.value.plant_uuids || []);
        plantUuidsForRegional.forEach((uuid) => currentPlantUuids.add(uuid));
        localFilters.value = {
            ...localFilters.value,
            plant_uuids: Array.from(currentPlantUuids),
        };
    } else if (!checked && currentlySelected) {
        toggleRegional(regionalUuid);
        const toRemove = new Set(plantUuidsForRegional);
        localFilters.value = {
            ...localFilters.value,
            plant_uuids: (localFilters.value.plant_uuids || []).filter(
                (uuid) => !toRemove.has(uuid),
            ),
        };
    }
};

const onPlantChecked = (plantUuid, checked) => {
    if (checked !== isPlantSelected(plantUuid)) togglePlant(plantUuid);
};

const onStationChecked = (stationCode, checked) => {
    if (checked !== isStationSelected(stationCode)) toggleStation(stationCode);
};

const onEquipmentTypeChecked = (equipmentType, checked) => {
    if (checked !== isEquipmentTypeSelected(equipmentType))
        toggleEquipmentType(equipmentType);
};

const isRegionalSelected = (regionalUuid) =>
    (localFilters.value.regional_uuids || []).includes(regionalUuid);
const isPlantSelected = (plantUuid) =>
    (localFilters.value.plant_uuids || []).includes(plantUuid);
const isStationSelected = (stationCode) =>
    (localFilters.value.station_codes || []).includes(stationCode);
const isEquipmentTypeSelected = (equipmentType) =>
    (localFilters.value.equipment_types || []).includes(equipmentType);

const regionalLabel = computed(() => {
    const count = (localFilters.value.regional_uuids || []).length;
    if (count === 0 || count === regions.value.length) return 'Regional';
    if (count === 1)
        return (
            regions.value.find(
                (r) => r.uuid === localFilters.value.regional_uuids[0],
            )?.name || 'Regional'
        );
    return `${count} Regional dipilih`;
});

const plantLabel = computed(() => {
    const selectedRegionalUuids = localFilters.value.regional_uuids || [];
    const visibleSelectedPlants = filteredPlants.value.filter((plant) =>
        (localFilters.value.plant_uuids || []).includes(plant.uuid),
    );
    const count = visibleSelectedPlants.length;
    const total = filteredPlants.value.length;
    if (selectedRegionalUuids.length > 0 && !total)
        return 'Pilih Regional terlebih dahulu';
    if (!count || (count === total && total > 0)) return 'Pabrik';
    if (count === 1) return visibleSelectedPlants[0]?.name || 'Pabrik';
    return `${count} Pabrik dipilih`;
});

const stationLabel = computed(() => {
    const count = (localFilters.value.station_codes || []).length;
    if (!count || count === stations.value.length) return 'Stasiun';
    if (count === 1)
        return (
            stations.value.find(
                (s) => s.code === localFilters.value.station_codes[0],
            )?.description || 'Stasiun'
        );
    return `${count} Stasiun dipilih`;
});

const equipmentTypeLabel = computed(() => {
    const count = (localFilters.value.equipment_types || []).length;
    if (!count || count === equipmentTypes.value.length) return 'Tipe';
    if (count === 1)
        return (
            equipmentTypeOptions.value.find(
                (t) => t.id === localFilters.value.equipment_types[0],
            )?.label || 'Tipe'
        );
    return `${count} Tipe dipilih`;
});

const filteredRegions = computed(() => {
    const search = regionalSearch.value.toLowerCase();
    return search
        ? regions.value.filter((r) => r.name.toLowerCase().includes(search))
        : regions.value;
});

const filteredPlants = computed(() => {
    const search = plantSearch.value.toLowerCase();
    const selectedRegionalUuids = localFilters.value.regional_uuids || [];
    let filtered = selectedRegionalUuids.length
        ? plants.value.filter((p) =>
              selectedRegionalUuids.includes(p.regional_uuid),
          )
        : plants.value;
    return search
        ? filtered.filter((p) => p.name.toLowerCase().includes(search))
        : filtered;
});

const filteredStations = computed(() => {
    const search = stationSearch.value.toLowerCase();
    const filtered = search
        ? stations.value.filter((s) =>
              s.description.toLowerCase().includes(search),
          )
        : stations.value;
    return filtered.sort((a, b) => a.description.localeCompare(b.description));
});

const filteredEquipmentTypes = computed(() => {
    const search = typeSearch.value.toLowerCase();
    return search
        ? equipmentTypeOptions.value.filter((t) =>
              t.label.toLowerCase().includes(search),
          )
        : equipmentTypeOptions.value;
});

const getPlantUuidsForRegional = (regionalUuid) =>
    plants.value
        .filter((p) => p.regional_uuid === regionalUuid)
        .map((p) => p.uuid);

const selectAllRegions = () =>
    (localFilters.value = {
        ...localFilters.value,
        regional_uuids: regions.value.map((r) => r.uuid),
    });
const deselectAllRegions = () => {
    localFilters.value.regional_uuids = [];
    localFilters.value.plant_uuids = [];
};
const selectAllPlants = () =>
    (localFilters.value = {
        ...localFilters.value,
        plant_uuids: plants.value.map((p) => p.uuid),
    });
const deselectAllPlants = () => (localFilters.value.plant_uuids = []);
const selectAllStations = () =>
    (localFilters.value = {
        ...localFilters.value,
        station_codes: stations.value.map((s) => s.code),
    });
const deselectAllStations = () => (localFilters.value.station_codes = []);
const selectAllEquipmentTypes = () =>
    (localFilters.value = {
        ...localFilters.value,
        equipment_types: equipmentTypeOptions.value.map((t) => t.id),
    });
const deselectAllEquipmentTypes = () =>
    (localFilters.value.equipment_types = []);

const selectOnlyRegional = (regionalUuid) =>
    (localFilters.value = {
        ...localFilters.value,
        regional_uuids: [regionalUuid],
    });
const selectOnlyPlant = (plantUuid) =>
    (localFilters.value = { ...localFilters.value, plant_uuids: [plantUuid] });
const selectOnlyStation = (stationCode) =>
    (localFilters.value = {
        ...localFilters.value,
        station_codes: [stationCode],
    });
const selectOnlyEquipmentType = (equipmentType) =>
    (localFilters.value = {
        ...localFilters.value,
        equipment_types: [equipmentType],
    });

onMounted(async () => {
    await Promise.all([fetchRegions(), fetchPlants()]);
    fetchStations();
    fetchEquipmentTypes();

    if (!props.disableStore && dateRange.start && dateRange.end) {
        localFilters.value.date_range = {
            start: dateRange.start,
            end: dateRange.end,
        };
        rangeValue.value = {
            start: parseDate(dateRange.start),
            end: parseDate(dateRange.end),
        };
    }

    const hasRegional =
        props.filters?.regional_uuids?.length ||
        localFilters.value.regional_uuids?.length;
    const hasPlant =
        props.filters?.plant_uuids?.length ||
        localFilters.value.plant_uuids?.length;
    const hasStation =
        props.filters?.station_codes?.length ||
        localFilters.value.station_codes?.length;
    const hasType =
        props.filters?.equipment_types?.length ||
        localFilters.value.equipment_types?.length;

    if (!props.hideRegional && !hasRegional) selectAllRegions();
    if (!props.hidePlant && !hasPlant) selectAllPlants();
    if (!hasStation) selectAllStations();
    if (!hasType) selectAllEquipmentTypes();

    validateAndAutoSelect();
    await nextTick();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Mobile Filter Toggle Button (only visible on small screens) -->
        <div class="flex items-center justify-between sm:hidden">
            <h3 class="text-sm font-medium">Filters</h3>
            <Button variant="outline" size="sm" @click="toggleMobileFilter">
                <span class="mr-2">{{
                    isFilterVisibleMobile ? 'Hide Filters' : 'Show Filters'
                }}</span>
                <ChevronUp v-if="isFilterVisibleMobile" class="h-4 w-4" />
                <ChevronDown v-else class="h-4 w-4" />
            </Button>
        </div>

        <!-- Filters Row with transition (wraps on small screens) -->
        <!-- Always visible on desktop (sm and up), toggleable on mobile -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-show="isFilterVisibleMobile" class="sm:!block">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-wrap gap-4">
                        <!-- Regional Filter -->
                        <div
                            v-if="!hideRegional"
                            class="w-full space-y-2 sm:w-auto"
                        >
                            <Popover
                                v-model:open="regionalOpen"
                                @update:open="
                                    (open) => !open && validateAndAutoSelect()
                                "
                            >
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-between"
                                    >
                                        <div class="flex items-center">
                                            <MapPin
                                                class="mr-2 h-4 w-4 shrink-0"
                                            />
                                            <div
                                                class="mr-2 h-4 w-px bg-border"
                                            ></div>
                                            {{ regionalLabel }}
                                        </div>
                                        <ChevronsUpDown
                                            class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[300px] p-0"
                                    :side="'bottom'"
                                    :align="'start'"
                                    :side-offset="4"
                                    :avoid-collisions="true"
                                    :collision-boundary="'viewport'"
                                    :sticky="'partial'"
                                >
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
                                                    v-if="
                                                        filteredRegions.length ===
                                                        0
                                                    "
                                                    class="py-6 text-center text-sm text-muted-foreground"
                                                >
                                                    No regional found.
                                                </div>
                                                <div
                                                    v-for="region in filteredRegions"
                                                    :key="region.uuid"
                                                    class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                                >
                                                    <Checkbox
                                                        :id="`regional-${region.uuid}`"
                                                        :model-value="
                                                            isRegionalSelected(
                                                                region.uuid,
                                                            )
                                                        "
                                                        @update:model-value="
                                                            (val) =>
                                                                onRegionalChecked(
                                                                    region.uuid,
                                                                    val,
                                                                )
                                                        "
                                                    />
                                                    <label
                                                        :for="`regional-${region.uuid}`"
                                                        class="flex-1 cursor-pointer text-sm"
                                                    >
                                                        {{ region.name }}
                                                    </label>
                                                    <button
                                                        class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                        @click="
                                                            selectOnlyRegional(
                                                                region.uuid,
                                                            )
                                                        "
                                                    >
                                                        Only
                                                    </button>
                                                </div>
                                            </div>
                                        </ScrollArea>
                                        <div class="flex gap-2 border-t p-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="selectAllRegions"
                                            >
                                                Select All
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="deselectAllRegions"
                                            >
                                                Deselect All
                                            </Button>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Plant Filter -->
                        <div
                            v-if="!hidePlant"
                            class="w-full space-y-2 sm:w-auto"
                        >
                            <Popover
                                v-model:open="plantOpen"
                                @update:open="
                                    (open) => !open && validateAndAutoSelect()
                                "
                            >
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-between"
                                    >
                                        <div class="flex items-center">
                                            <Building2
                                                class="mr-2 h-4 w-4 shrink-0"
                                            />
                                            <div
                                                class="mr-2 h-4 w-px bg-border"
                                            ></div>
                                            {{ plantLabel }}
                                        </div>
                                        <ChevronsUpDown
                                            class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[280px] p-0"
                                    :side="'bottom'"
                                    :align="'start'"
                                    :side-offset="4"
                                    :avoid-collisions="true"
                                    :collision-boundary="'viewport'"
                                    :sticky="'partial'"
                                >
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
                                                    v-if="
                                                        filteredPlants.length ===
                                                        0
                                                    "
                                                    class="py-6 text-center text-sm text-muted-foreground"
                                                >
                                                    No plant found.
                                                </div>
                                                <div
                                                    v-for="plant in filteredPlants"
                                                    :key="plant.uuid"
                                                    class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                                >
                                                    <Checkbox
                                                        :id="`plant-${plant.uuid}`"
                                                        :model-value="
                                                            isPlantSelected(
                                                                plant.uuid,
                                                            )
                                                        "
                                                        @update:model-value="
                                                            (val) =>
                                                                onPlantChecked(
                                                                    plant.uuid,
                                                                    val,
                                                                )
                                                        "
                                                    />
                                                    <label
                                                        :for="`plant-${plant.uuid}`"
                                                        class="flex-1 cursor-pointer text-sm"
                                                    >
                                                        {{ plant.name }}
                                                    </label>
                                                    <button
                                                        class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                        @click="
                                                            selectOnlyPlant(
                                                                plant.uuid,
                                                            )
                                                        "
                                                    >
                                                        Only
                                                    </button>
                                                </div>
                                            </div>
                                        </ScrollArea>
                                        <div class="flex gap-2 border-t p-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="selectAllPlants"
                                            >
                                                Select All
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="deselectAllPlants"
                                            >
                                                Deselect All
                                            </Button>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Station Filter -->
                        <div class="w-full space-y-2 sm:w-auto">
                            <Popover
                                v-model:open="stationOpen"
                                @update:open="
                                    (open) => !open && validateAndAutoSelect()
                                "
                            >
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-between"
                                    >
                                        <div class="flex items-center">
                                            <Map
                                                class="mr-2 h-4 w-4 shrink-0"
                                            />
                                            <div
                                                class="mr-2 h-4 w-px bg-border"
                                            ></div>
                                            {{ stationLabel }}
                                        </div>
                                        <ChevronsUpDown
                                            class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[280px] p-0"
                                    :side="'bottom'"
                                    :align="'start'"
                                    :side-offset="4"
                                    :avoid-collisions="true"
                                    :collision-boundary="'viewport'"
                                    :sticky="'partial'"
                                >
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
                                                    v-if="
                                                        filteredStations.length ===
                                                        0
                                                    "
                                                    class="py-6 text-center text-sm text-muted-foreground"
                                                >
                                                    No station found.
                                                </div>
                                                <div
                                                    v-for="station in filteredStations"
                                                    :key="station.code"
                                                    class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                                >
                                                    <Checkbox
                                                        :id="`station-${station.code}`"
                                                        :model-value="
                                                            isStationSelected(
                                                                station.code,
                                                            )
                                                        "
                                                        @update:model-value="
                                                            (val) =>
                                                                onStationChecked(
                                                                    station.code,
                                                                    val,
                                                                )
                                                        "
                                                    />
                                                    <label
                                                        :for="`station-${station.code}`"
                                                        class="flex-1 cursor-pointer text-sm"
                                                    >
                                                        {{
                                                            station.description
                                                        }}
                                                    </label>
                                                    <button
                                                        class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                        @click="
                                                            selectOnlyStation(
                                                                station.code,
                                                            )
                                                        "
                                                    >
                                                        Only
                                                    </button>
                                                </div>
                                            </div>
                                        </ScrollArea>
                                        <div class="flex gap-2 border-t p-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="selectAllStations"
                                            >
                                                Select All
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="deselectAllStations"
                                            >
                                                Deselect All
                                            </Button>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Equipment Type Filter -->
                        <div class="w-full space-y-2 sm:w-auto">
                            <Popover
                                v-model:open="typeOpen"
                                @update:open="
                                    (open) => !open && validateAndAutoSelect()
                                "
                            >
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-between"
                                    >
                                        <div class="flex items-center">
                                            <Tag
                                                class="mr-2 h-4 w-4 shrink-0"
                                            />
                                            <div
                                                class="mr-2 h-4 w-px bg-border"
                                            ></div>
                                            {{ equipmentTypeLabel }}
                                        </div>
                                        <ChevronsUpDown
                                            class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[280px] p-0"
                                    :side="'bottom'"
                                    :align="'start'"
                                    :side-offset="4"
                                    :avoid-collisions="true"
                                    :collision-boundary="'viewport'"
                                    :sticky="'partial'"
                                >
                                    <div class="flex flex-col">
                                        <div
                                            class="relative flex w-full items-center border-b"
                                        >
                                            <input
                                                v-model="typeSearch"
                                                type="text"
                                                placeholder="Cari Tipe..."
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
                                                v-if="typeSearch"
                                                @click="typeSearch = ''"
                                                class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </div>
                                        <ScrollArea class="h-[200px]">
                                            <div class="p-2">
                                                <div
                                                    v-if="
                                                        filteredEquipmentTypes.length ===
                                                        0
                                                    "
                                                    class="py-6 text-center text-sm text-muted-foreground"
                                                >
                                                    No tipe found.
                                                </div>
                                                <div
                                                    v-for="typeObj in filteredEquipmentTypes"
                                                    :key="typeObj.id"
                                                    class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent"
                                                >
                                                    <Checkbox
                                                        :id="`type-${typeObj.id}`"
                                                        :model-value="
                                                            isEquipmentTypeSelected(
                                                                typeObj.id,
                                                            )
                                                        "
                                                        @update:model-value="
                                                            (val) =>
                                                                onEquipmentTypeChecked(
                                                                    typeObj.id,
                                                                    val,
                                                                )
                                                        "
                                                    />
                                                    <label
                                                        :for="`type-${typeObj.id}`"
                                                        class="flex-1 cursor-pointer text-sm"
                                                    >
                                                        {{ typeObj.label }}
                                                    </label>
                                                    <button
                                                        class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                        @click="
                                                            selectOnlyEquipmentType(
                                                                typeObj.id,
                                                            )
                                                        "
                                                    >
                                                        Only
                                                    </button>
                                                </div>
                                            </div>
                                        </ScrollArea>
                                        <div class="flex gap-2 border-t p-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="selectAllEquipmentTypes"
                                            >
                                                Select All
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="flex-1"
                                                @click="
                                                    deselectAllEquipmentTypes
                                                "
                                            >
                                                Deselect All
                                            </Button>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="w-full space-y-2 sm:w-auto">
                            <Popover v-model:open="datePopoverOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="[
                                            'w-full justify-start text-left font-normal',
                                            isRangeEmpty()
                                                ? 'text-muted-foreground'
                                                : '',
                                        ]"
                                    >
                                        <Clock class="h-4 w-4" />
                                        <div class="h-4 w-px bg-border"></div>
                                        <span class="truncate">{{
                                            rangeDisplay()
                                        }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-auto p-0"
                                    :side="'bottom'"
                                    :align="'start'"
                                    :side-offset="4"
                                    :avoid-collisions="true"
                                    :collision-boundary="'viewport'"
                                    :sticky="'partial'"
                                >
                                    <RangeCalendar
                                        v-model="rangeValue"
                                        :number-of-months="2"
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <div class="flex w-full flex-wrap gap-4 md:w-auto">
                        <!-- Search Input -->
                        <div v-if="!hideSearch" class="flex-1 space-y-2">
                            <div class="relative">
                                <Search
                                    class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground"
                                />
                                <Input
                                    v-model="localFilters.search"
                                    :placeholder="searchPlaceholder"
                                    class="h-9 pr-8 pl-8"
                                />
                                <Button
                                    v-if="localFilters.search"
                                    variant="ghost"
                                    size="sm"
                                    class="absolute top-1 right-1 h-7 w-7 p-0"
                                    @click="localFilters.search = ''"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Apply Button -->
                        <div class="w-full sm:w-auto">
                            <Button
                                variant="default"
                                class="w-full sm:w-auto"
                                @click="applyFilters"
                            >
                                Apply Filters
                            </Button>
                        </div>

                        <!-- Column Toggle (icon only) -->
                        <div class="hidden w-full sm:w-auto md:block">
                            <DataTableViewOptions :table="table" />
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
