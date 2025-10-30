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
import { getLocalTimeZone, parseDate } from '@internationalized/date';
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

// Filter visibility state for mobile (always visible on desktop)
const FILTER_VISIBILITY_KEY = 'monitoring_filter_visible_mobile';
const isFilterVisible = ref(
    localStorage.getItem(FILTER_VISIBILITY_KEY) !== 'false', // Default to true
);

// Watch for changes and save to localStorage
watch(isFilterVisible, (newValue) => {
    localStorage.setItem(FILTER_VISIBILITY_KEY, String(newValue));
});

const toggleMobileFilter = () => {
    isFilterVisible.value = !isFilterVisible.value;
};

const regions = ref([]);
const plants = ref([]);
const stations = ref([]);
const equipmentTypes = ref([]);
const loadingRegions = ref(false);
const loadingPlants = ref(false);
const loadingStations = ref(false);
const loadingTypes = ref(false);

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
    station_codes: Array.isArray(props.filters?.station_codes)
        ? [...props.filters.station_codes]
        : [],
    equipment_types: Array.isArray(props.filters?.equipment_types)
        ? [...props.filters.equipment_types]
        : [],
    search: props.filters?.search || '',
});

// Initialize from localStorage if available
const dateRange = useDateRangeStore();

// sync from pinia store into local on mount (unless disabled)
onMounted(() => {
    if (props.disableStore) {
        // Don't load from store - use props directly
        return;
    }

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
const typeOpen = ref(false);

// Search states
const regionalSearch = ref('');
const plantSearch = ref('');
const stationSearch = ref('');
const typeSearch = ref('');
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
    () => localFilters.value.station_codes,
    (newVal) => {
        console.log('Station codes changed:', newVal);
    },
    { deep: true },
);

watch(
    () => localFilters.value.equipment_types,
    (newVal) => {
        console.log('Equipment types changed:', newVal);
    },
    { deep: true },
);

// Apply filters on demand via button
const applyFilters = async () => {
    await nextTick();

    // Validate and auto-select all if none selected
    validateAndAutoSelect();

    // Filter plant selection to only include plants from selected regional
    const selectedRegionalIds = localFilters.value.regional_ids || [];
    let filteredPlantIds = localFilters.value.plant_ids || [];

    if (selectedRegionalIds.length > 0) {
        // Only keep plants that belong to the selected regional
        filteredPlantIds = filteredPlantIds.filter((plantId) => {
            const plant = plants.value.find((p) => p.id === plantId);
            return plant && selectedRegionalIds.includes(plant.regional_id);
        });
    }

    // Prepare the final filter data
    const finalFilters = {
        ...localFilters.value,
        plant_ids: filteredPlantIds,
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

// Validation function to ensure at least one option is selected
const validateAndAutoSelect = () => {
    console.log('validateAndAutoSelect called - checking all filters');

    // Auto-select all regions if none selected (only if not hidden)
    if (
        !props.hideRegional &&
        (localFilters.value.regional_ids || []).length === 0 &&
        regions.value.length > 0
    ) {
        console.log('Auto-selecting all regions');
        selectAllRegions();
    }

    // Auto-select all plants if none selected (only if not hidden)
    if (
        !props.hidePlant &&
        (localFilters.value.plant_ids || []).length === 0 &&
        plants.value.length > 0
    ) {
        console.log('Auto-selecting all plants');
        selectAllPlants();
    }

    // Auto-select all stations if none selected
    if (
        (localFilters.value.station_codes || []).length === 0 &&
        stations.value.length > 0
    ) {
        console.log('Auto-selecting all stations');
        selectAllStations();
    }

    // Auto-select all equipment types if none selected
    if (
        (localFilters.value.equipment_types || []).length === 0 &&
        equipmentTypes.value.length > 0
    ) {
        console.log('Auto-selecting all equipment types');
        selectAllEquipmentTypes();
    }
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

// Define unique station codes and descriptions
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

// Define equipment types with ID mapping
const equipmentTypeOptions = ref([
    { id: 1, label: 'Mesin Produksi' },
    { id: 2, label: 'Kendaraan' },
    { id: 3, label: 'Alat dan Utilitas' },
    { id: 4, label: 'IT & Telekomunikasi' },
    { id: 5, label: 'Aset PMN' },
]);

// No need to fetch stations from API - use predefined station types
const fetchStations = async () => {
    loadingStations.value = true;
    try {
        // Use predefined station types instead of API call
        stations.value = stationTypes.value;
    } catch (error) {
        console.error('Error setting stations:', error);
        stations.value = [];
    } finally {
        loadingStations.value = false;
    }
};

// No need to fetch equipment types from API - use predefined types
const fetchEquipmentTypes = async () => {
    loadingTypes.value = true;
    try {
        // Use predefined equipment types (just the IDs)
        equipmentTypes.value = equipmentTypeOptions.value.map(t => t.id);
    } catch (error) {
        console.error('Error setting equipment types:', error);
        equipmentTypes.value = [];
    } finally {
        loadingTypes.value = false;
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

    // Clean up plant selection to only include plants from selected regional
    cleanupPlantSelection();
};

// Function to clean up plant selection based on regional selection
const cleanupPlantSelection = () => {
    const selectedRegionalIds = localFilters.value.regional_ids || [];
    const currentPlantIds = localFilters.value.plant_ids || [];

    if (selectedRegionalIds.length > 0) {
        // Only keep plants that belong to the selected regional
        const validPlantIds = currentPlantIds.filter((plantId) => {
            const plant = plants.value.find((p) => p.id === plantId);
            return plant && selectedRegionalIds.includes(plant.regional_id);
        });

        // Update plant selection if it changed
        if (validPlantIds.length !== currentPlantIds.length) {
            localFilters.value = {
                ...localFilters.value,
                plant_ids: validPlantIds,
            };
        }
    }
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

const toggleStation = async (stationCode) => {
    console.log('toggleStation', stationCode);

    // Ensure array exists
    if (!Array.isArray(localFilters.value.station_codes)) {
        localFilters.value.station_codes = [];
    }

    const currentCodes = [...localFilters.value.station_codes];
    const index = currentCodes.indexOf(stationCode);

    if (index > -1) {
        // Remove
        currentCodes.splice(index, 1);
    } else {
        // Add
        currentCodes.push(stationCode);
    }

    // Create new array reference for reactivity
    localFilters.value = {
        ...localFilters.value,
        station_codes: currentCodes,
    };
};

const toggleEquipmentType = async (equipmentType) => {
    console.log('toggleEquipmentType', equipmentType);

    // Ensure array exists
    if (!Array.isArray(localFilters.value.equipment_types)) {
        localFilters.value.equipment_types = [];
    }

    const currentTypes = [...localFilters.value.equipment_types];
    const index = currentTypes.indexOf(equipmentType);

    if (index > -1) {
        // Remove
        currentTypes.splice(index, 1);
    } else {
        // Add
        currentTypes.push(equipmentType);
    }

    // Create new array reference for reactivity
    localFilters.value = {
        ...localFilters.value,
        equipment_types: currentTypes,
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

        localFilters.value = {
            ...localFilters.value,
            plant_ids: remainingPlantIds,
        };
    }
};

const onPlantChecked = async (plantId, checked) => {
    console.log('onPlantChecked', plantId, checked);
    const currentlySelected = isPlantSelected(plantId);

    if (checked && !currentlySelected) {
        await togglePlant(plantId);
    } else if (!checked && currentlySelected) {
        // Allow complete deselection - no forced minimum
        await togglePlant(plantId);
    }

    // Regional selection is independent - no automatic regional updates
};

const onStationChecked = async (stationCode, checked) => {
    console.log('onStationChecked', stationCode, checked);
    const currentlySelected = isStationSelected(stationCode);
    if (checked && !currentlySelected) {
        await toggleStation(stationCode);
    } else if (!checked && currentlySelected) {
        // Allow complete deselection - no forced minimum
        await toggleStation(stationCode);
    }
};

const onEquipmentTypeChecked = async (equipmentType, checked) => {
    console.log('onEquipmentTypeChecked', equipmentType, checked);
    const currentlySelected = isEquipmentTypeSelected(equipmentType);
    if (checked && !currentlySelected) {
        await toggleEquipmentType(equipmentType);
    } else if (!checked && currentlySelected) {
        // Allow complete deselection - no forced minimum
        await toggleEquipmentType(equipmentType);
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

const stationModel = (stationCode) =>
    computed({
        get() {
            return isStationSelected(stationCode);
        },
        async set(val) {
            await onStationChecked(stationCode, !!val);
        },
    });

const equipmentTypeModel = (equipmentType) =>
    computed({
        get() {
            return isEquipmentTypeSelected(equipmentType);
        },
        async set(val) {
            await onEquipmentTypeChecked(equipmentType, !!val);
        },
    });

const isRegionalSelected = (regionalId) => {
    return (localFilters.value.regional_ids || []).includes(regionalId);
};

const isPlantSelected = (plantId) => {
    return (localFilters.value.plant_ids || []).includes(plantId);
};

const isStationSelected = (stationCode) => {
    return (localFilters.value.station_codes || []).includes(stationCode);
};

const isEquipmentTypeSelected = (equipmentType) => {
    return (localFilters.value.equipment_types || []).includes(equipmentType);
};

// Computed properties for display labels
const regionalLabel = computed(() => {
    const count = (localFilters.value.regional_ids || []).length;
    const totalRegions = regions.value.length;

    if (count === 0) {
        return 'Regional';
    } else if (count === totalRegions) {
        return 'Regional';
    } else if (count === 1) {
        return (
            regions.value.find(
                (r) => r.id === localFilters.value.regional_ids[0],
            )?.name || 'Regional'
        );
    } else {
        return `${count} Regional dipilih`;
    }
});

const plantLabel = computed(() => {
    const selectedRegionalIds = localFilters.value.regional_ids || [];
    const allSelectedPlantIds = localFilters.value.plant_ids || [];

    // Get only the selected plants that are visible in the current regional filter
    const visibleSelectedPlants = filteredPlants.value.filter((plant) =>
        allSelectedPlantIds.includes(plant.id),
    );
    const visibleSelectedCount = visibleSelectedPlants.length;
    const totalVisiblePlants = filteredPlants.value.length;

    // If regional is selected but no plants are available, show appropriate message
    if (selectedRegionalIds.length > 0 && totalVisiblePlants === 0) {
        return 'Pilih Regional terlebih dahulu';
    }

    if (visibleSelectedCount === 0) {
        return 'Pabrik';
    } else if (
        visibleSelectedCount === totalVisiblePlants &&
        totalVisiblePlants > 0
    ) {
        return 'Pabrik';
    } else if (visibleSelectedCount === 1) {
        return visibleSelectedPlants[0]?.name || 'Pabrik';
    } else {
        return `${visibleSelectedCount} Pabrik dipilih`;
    }
});

const stationLabel = computed(() => {
    const count = (localFilters.value.station_codes || []).length;
    const totalStations = stations.value.length;

    if (count === 0) {
        return 'Stasiun';
    } else if (count === totalStations) {
        return 'Stasiun';
    } else if (count === 1) {
        return (
            stations.value.find(
                (s) => s.code === localFilters.value.station_codes[0],
            )?.description || 'Stasiun'
        );
    } else {
        return `${count} Stasiun dipilih`;
    }
});

const equipmentTypeLabel = computed(() => {
    const count = (localFilters.value.equipment_types || []).length;
    const totalTypes = equipmentTypes.value.length;

    if (count === 0) {
        return 'Tipe';
    } else if (count === totalTypes) {
        return 'Tipe';
    } else if (count === 1) {
        const typeId = localFilters.value.equipment_types[0];
        const typeObj = equipmentTypeOptions.value.find(t => t.id === typeId);
        return typeObj?.label || 'Tipe';
    } else {
        return `${count} Tipe dipilih`;
    }
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

    // Only show plants from selected regional
    const selectedRegionalIds = localFilters.value.regional_ids || [];
    if (selectedRegionalIds.length > 0) {
        filtered = filtered.filter((p) =>
            selectedRegionalIds.includes(p.regional_id),
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

    // Filter by search only - backend will handle plant-based filtering
    filtered = search
        ? filtered.filter((s) => s.description.toLowerCase().includes(search))
        : filtered;

    // Sort by description (name) in ascending order
    return filtered.sort((a, b) => a.description.localeCompare(b.description));
});

const filteredEquipmentTypes = computed(() => {
    const search = typeSearch.value.toLowerCase();
    let filtered = equipmentTypeOptions.value;

    // Filter by search on label
    return search
        ? filtered.filter((t) => t.label.toLowerCase().includes(search))
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
        station_codes: [],
        equipment_types: [],
    };

    // Clear search states
    regionalSearch.value = '';
    plantSearch.value = '';
    stationSearch.value = '';
    typeSearch.value = '';

    // Close all dropdowns
    regionalOpen.value = false;
    plantOpen.value = false;
    stationOpen.value = false;
    typeOpen.value = false;

    await nextTick();
};

// Individual filter reset functions
const resetRegional = async () => {
    localFilters.value = {
        ...localFilters.value,
        regional_ids: [],
        plant_ids: [], // Also reset plants when resetting regional
    };
    regionalSearch.value = '';
    regionalOpen.value = false;

    // Trigger reactivity by creating new object reference
    await nextTick();
    localFilters.value = { ...localFilters.value };
};

const resetPlant = async () => {
    localFilters.value = {
        ...localFilters.value,
        regional_ids: [], // Also reset regional since they're bound together
        plant_ids: [],
    };
    plantSearch.value = '';
    plantOpen.value = false;
    regionalSearch.value = '';
    regionalOpen.value = false;

    // Trigger reactivity by creating new object reference
    await nextTick();
    localFilters.value = { ...localFilters.value };
};

const resetStation = async () => {
    localFilters.value = {
        ...localFilters.value,
        station_codes: [],
    };
    stationSearch.value = '';
    stationOpen.value = false;

    // Trigger reactivity by creating new object reference
    await nextTick();
    localFilters.value = { ...localFilters.value };
};

const resetEquipmentType = async () => {
    localFilters.value = {
        ...localFilters.value,
        equipment_types: [],
    };
    typeSearch.value = '';
    typeOpen.value = false;

    // Trigger reactivity by creating new object reference
    await nextTick();
    localFilters.value = { ...localFilters.value };
};

// Removed fetchAllStations; stations now depend on selected plants only

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

// Select all/deselect all functions
const selectAllRegions = () => {
    const allRegionIds = regions.value.map((r) => r.id);
    localFilters.value = {
        ...localFilters.value,
        regional_ids: allRegionIds,
    };
};

const deselectAllRegions = () => {
    // Allow complete deselection - no forced minimum
    // Use direct assignment to avoid triggering unnecessary reactive updates
    localFilters.value.regional_ids = [];
    localFilters.value.plant_ids = []; // Also clear plants since regions are cleared
};

const selectAllPlants = () => {
    const allPlantIds = plants.value.map((p) => p.id);
    localFilters.value = {
        ...localFilters.value,
        plant_ids: allPlantIds,
    };
};

const deselectAllPlants = () => {
    // Allow complete deselection - no forced minimum
    // Use direct assignment to avoid triggering unnecessary reactive updates
    localFilters.value.plant_ids = [];
    // Regional selection remains independent - do not reset regional_ids
};

const selectAllStations = () => {
    const allStationCodes = stations.value.map((s) => s.code);
    localFilters.value = {
        ...localFilters.value,
        station_codes: allStationCodes,
    };
};

const deselectAllStations = () => {
    // Allow complete deselection - no forced minimum
    // Use direct assignment to avoid triggering unnecessary reactive updates
    localFilters.value.station_codes = [];
};

const selectAllEquipmentTypes = () => {
    const allTypeIds = equipmentTypeOptions.value.map(t => t.id);
    localFilters.value = {
        ...localFilters.value,
        equipment_types: allTypeIds,
    };
};

const deselectAllEquipmentTypes = () => {
    // Allow complete deselection - no forced minimum
    // Use direct assignment to avoid triggering unnecessary reactive updates
    localFilters.value.equipment_types = [];
};

// "Only" functions - select only the specified option
const selectOnlyRegional = (regionalId) => {
    localFilters.value = {
        ...localFilters.value,
        regional_ids: [regionalId],
    };
};

const selectOnlyPlant = (plantId) => {
    localFilters.value = {
        ...localFilters.value,
        plant_ids: [plantId],
    };
};

const selectOnlyStation = (stationCode) => {
    localFilters.value = {
        ...localFilters.value,
        station_codes: [stationCode],
    };
};

const selectOnlyEquipmentType = (equipmentType) => {
    localFilters.value = {
        ...localFilters.value,
        equipment_types: [equipmentType],
    };
};

onMounted(async () => {
    // Fetch all initial data
    await fetchRegions();
    await fetchPlants();
    await fetchStations();
    await fetchEquipmentTypes();

    // Set all options as selected by default if no filters are provided or if arrays are empty
    const hasRegionalSelection =
        props.filters?.regional_ids?.length > 0 ||
        localFilters.value.regional_ids?.length > 0;
    const hasPlantSelection =
        props.filters?.plant_ids?.length > 0 ||
        localFilters.value.plant_ids?.length > 0;
    const hasStationSelection =
        props.filters?.station_codes?.length > 0 ||
        localFilters.value.station_codes?.length > 0;
    const hasEquipmentTypeSelection =
        props.filters?.equipment_types?.length > 0 ||
        localFilters.value.equipment_types?.length > 0;

    if (!props.hideRegional && !hasRegionalSelection) {
        selectAllRegions();
    }
    if (!props.hidePlant && !hasPlantSelection) {
        selectAllPlants();
    }
    if (!hasStationSelection) {
        selectAllStations();
    }
    if (!hasEquipmentTypeSelection) {
        selectAllEquipmentTypes();
    }

    // Final validation to ensure everything is selected if nothing was selected
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
                <span class="mr-2">{{ isFilterVisible ? 'Hide Filters' : 'Show Filters' }}</span>
                <ChevronUp v-if="isFilterVisible" class="h-4 w-4" />
                <ChevronDown v-else class="h-4 w-4" />
            </Button>
        </div>

        <!-- Filters Row with transition (wraps on small screens) -->
        <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-show="isFilterVisible" class="sm:block">
                <div class="flex flex-wrap items-end gap-4">
                    <!-- Regional Filter -->
                    <div v-if="!hideRegional" class="w-full space-y-2 sm:w-auto">
                        <Popover v-model:open="regionalOpen" @update:open="
                            (open) => {
                                if (!open) {
                                    console.log(
                                        'Regional popover closed - validating selections',
                                    );
                                    validateAndAutoSelect();
                                }
                            }
                        ">
                            <PopoverTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    <div class="flex items-center">
                                        <MapPin class="mr-2 h-4 w-4 shrink-0" />
                                        <div class="mr-2 h-4 w-px bg-border"></div>
                                        {{ regionalLabel }}
                                    </div>
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[300px] p-0" :side="'bottom'" :align="'start'" :side-offset="4"
                                :avoid-collisions="true" :collision-boundary="'viewport'" :sticky="'partial'">
                                <div class="flex flex-col">
                                    <div class="relative flex w-full items-center border-b">
                                        <input v-model="regionalSearch" type="text" placeholder="Cari Regional..."
                                            class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none" />
                                        <span class="absolute inset-y-0 left-0 flex items-center justify-center px-3">
                                            <Search class="h-4 w-4 text-muted-foreground" />
                                        </span>
                                        <button v-if="regionalSearch" @click="regionalSearch = ''"
                                            class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <ScrollArea class="h-[200px]">
                                        <div class="p-2">
                                            <div v-if="filteredRegions.length === 0"
                                                class="py-6 text-center text-sm text-muted-foreground">
                                                No regional found.
                                            </div>
                                            <div v-for="region in filteredRegions" :key="region.id"
                                                class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent">
                                                <Checkbox :id="`regional-${region.id}`" :model-value="isRegionalSelected(region.id)
                                                    " @update:model-value="
                                                (val) =>
                                                    onRegionalChecked(
                                                        region.id,
                                                        val,
                                                    )
                                            " />
                                                <label :for="`regional-${region.id}`"
                                                    class="flex-1 cursor-pointer text-sm">
                                                    {{ region.name }}
                                                </label>
                                                <button
                                                    class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                    @click="
                                                        selectOnlyRegional(region.id)
                                                        ">
                                                    Only
                                                </button>
                                            </div>
                                        </div>
                                    </ScrollArea>
                                    <div class="flex gap-2 border-t p-2">
                                        <Button variant="outline" size="sm" class="flex-1" @click="selectAllRegions">
                                            Select All
                                        </Button>
                                        <Button variant="outline" size="sm" class="flex-1" @click="deselectAllRegions">
                                            Deselect All
                                        </Button>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Plant Filter -->
                    <div v-if="!hidePlant" class="w-full space-y-2 sm:w-auto">
                        <Popover v-model:open="plantOpen" @update:open="
                            (open) => {
                                if (!open) {
                                    console.log(
                                        'Plant popover closed - validating selections',
                                    );
                                    validateAndAutoSelect();
                                }
                            }
                        ">
                            <PopoverTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    <div class="flex items-center">
                                        <Building2 class="mr-2 h-4 w-4 shrink-0" />
                                        <div class="mr-2 h-4 w-px bg-border"></div>
                                        {{ plantLabel }}
                                    </div>
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[280px] p-0" :side="'bottom'" :align="'start'" :side-offset="4"
                                :avoid-collisions="true" :collision-boundary="'viewport'" :sticky="'partial'">
                                <div class="flex flex-col">
                                    <div class="relative flex w-full items-center border-b">
                                        <input v-model="plantSearch" type="text" placeholder="Cari Pabrik..."
                                            class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none" />
                                        <span class="absolute inset-y-0 left-0 flex items-center justify-center px-3">
                                            <Search class="h-4 w-4 text-muted-foreground" />
                                        </span>
                                        <button v-if="plantSearch" @click="plantSearch = ''"
                                            class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <ScrollArea class="h-[200px]">
                                        <div class="p-2">
                                            <div v-if="filteredPlants.length === 0"
                                                class="py-6 text-center text-sm text-muted-foreground">
                                                No plant found.
                                            </div>
                                            <div v-for="plant in filteredPlants" :key="plant.id"
                                                class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent">
                                                <Checkbox :id="`plant-${plant.id}`" :model-value="isPlantSelected(plant.id)
                                                    " @update:model-value="
                                                (val) =>
                                                    onPlantChecked(
                                                        plant.id,
                                                        val,
                                                    )
                                            " />
                                                <label :for="`plant-${plant.id}`" class="flex-1 cursor-pointer text-sm">
                                                    {{ plant.name }}
                                                </label>
                                                <button
                                                    class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                    @click="selectOnlyPlant(plant.id)">
                                                    Only
                                                </button>
                                            </div>
                                        </div>
                                    </ScrollArea>
                                    <div class="flex gap-2 border-t p-2">
                                        <Button variant="outline" size="sm" class="flex-1" @click="selectAllPlants">
                                            Select All
                                        </Button>
                                        <Button variant="outline" size="sm" class="flex-1" @click="deselectAllPlants">
                                            Deselect All
                                        </Button>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Station Filter -->
                    <div class="w-full space-y-2 sm:w-auto">
                        <Popover v-model:open="stationOpen" @update:open="
                            (open) => {
                                if (!open) {
                                    console.log(
                                        'Station popover closed - validating selections',
                                    );
                                    validateAndAutoSelect();
                                }
                            }
                        ">
                            <PopoverTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    <div class="flex items-center">
                                        <Map class="mr-2 h-4 w-4 shrink-0" />
                                        <div class="mr-2 h-4 w-px bg-border"></div>
                                        {{ stationLabel }}
                                    </div>
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[280px] p-0" :side="'bottom'" :align="'start'" :side-offset="4"
                                :avoid-collisions="true" :collision-boundary="'viewport'" :sticky="'partial'">
                                <div class="flex flex-col">
                                    <div class="relative flex w-full items-center border-b">
                                        <input v-model="stationSearch" type="text" placeholder="Cari Stasiun..."
                                            class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none" />
                                        <span class="absolute inset-y-0 left-0 flex items-center justify-center px-3">
                                            <Search class="h-4 w-4 text-muted-foreground" />
                                        </span>
                                        <button v-if="stationSearch" @click="stationSearch = ''"
                                            class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <ScrollArea class="h-[200px]">
                                        <div class="p-2">
                                            <div v-if="filteredStations.length === 0"
                                                class="py-6 text-center text-sm text-muted-foreground">
                                                No station found.
                                            </div>
                                            <div v-for="station in filteredStations" :key="station.code"
                                                class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent">
                                                <Checkbox :id="`station-${station.code}`" :model-value="isStationSelected(station.code)
                                                    " @update:model-value="
                                                (val) =>
                                                    onStationChecked(
                                                        station.code,
                                                        val,
                                                    )
                                            " />
                                                <label :for="`station-${station.code}`"
                                                    class="flex-1 cursor-pointer text-sm">
                                                    {{ station.description }}
                                                </label>
                                                <button
                                                    class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                    @click="
                                                        selectOnlyStation(station.code)
                                                        ">
                                                    Only
                                                </button>
                                            </div>
                                        </div>
                                    </ScrollArea>
                                    <div class="flex gap-2 border-t p-2">
                                        <Button variant="outline" size="sm" class="flex-1" @click="selectAllStations">
                                            Select All
                                        </Button>
                                        <Button variant="outline" size="sm" class="flex-1" @click="deselectAllStations">
                                            Deselect All
                                        </Button>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Equipment Type Filter -->
                    <div class="w-full space-y-2 sm:w-auto">
                        <Popover v-model:open="typeOpen" @update:open="
                            (open) => {
                                if (!open) {
                                    console.log(
                                        'Equipment type popover closed - validating selections',
                                    );
                                    validateAndAutoSelect();
                                }
                            }
                        ">
                            <PopoverTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    <div class="flex items-center">
                                        <Tag class="mr-2 h-4 w-4 shrink-0" />
                                        <div class="mr-2 h-4 w-px bg-border"></div>
                                        {{ equipmentTypeLabel }}
                                    </div>
                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[280px] p-0" :side="'bottom'" :align="'start'" :side-offset="4"
                                :avoid-collisions="true" :collision-boundary="'viewport'" :sticky="'partial'">
                                <div class="flex flex-col">
                                    <div class="relative flex w-full items-center border-b">
                                        <input v-model="typeSearch" type="text" placeholder="Cari Tipe..."
                                            class="h-10 w-full border-0 bg-transparent px-10 text-sm focus:ring-0 focus:outline-none" />
                                        <span class="absolute inset-y-0 left-0 flex items-center justify-center px-3">
                                            <Search class="h-4 w-4 text-muted-foreground" />
                                        </span>
                                        <button v-if="typeSearch" @click="typeSearch = ''"
                                            class="absolute inset-y-0 right-0 flex items-center justify-center px-3 hover:text-foreground">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <ScrollArea class="h-[200px]">
                                        <div class="p-2">
                                            <div v-if="
                                                filteredEquipmentTypes.length === 0
                                            " class="py-6 text-center text-sm text-muted-foreground">
                                                No tipe found.
                                            </div>
                                            <div v-for="typeObj in filteredEquipmentTypes" :key="typeObj.id"
                                                class="group flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent">
                                                <Checkbox :id="`type-${typeObj.id}`" :model-value="isEquipmentTypeSelected(
                                                    typeObj.id,
                                                )
                                                    " @update:model-value="
                                                (val) =>
                                                    onEquipmentTypeChecked(
                                                        typeObj.id,
                                                        val,
                                                    )
                                            " />
                                                <label :for="`type-${typeObj.id}`"
                                                    class="flex-1 cursor-pointer text-sm">
                                                    {{ typeObj.label }}
                                                </label>
                                                <button
                                                    class="px-1 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 hover:text-foreground"
                                                    @click="
                                                        selectOnlyEquipmentType(
                                                            typeObj.id,
                                                        )
                                                        ">
                                                    Only
                                                </button>
                                            </div>
                                        </div>
                                    </ScrollArea>
                                    <div class="flex gap-2 border-t p-2">
                                        <Button variant="outline" size="sm" class="flex-1"
                                            @click="selectAllEquipmentTypes">
                                            Select All
                                        </Button>
                                        <Button variant="outline" size="sm" class="flex-1"
                                            @click="deselectAllEquipmentTypes">
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
                                <Button variant="outline" :class="[
                                    'w-full justify-start text-left font-normal',
                                    isRangeEmpty() ? 'text-muted-foreground' : '',
                                ]">
                                    <Clock class="h-4 w-4" />
                                    <div class="h-4 w-px bg-border"></div>
                                    <span class="truncate">{{ rangeDisplay() }}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" :side="'bottom'" :align="'start'" :side-offset="4"
                                :avoid-collisions="true" :collision-boundary="'viewport'" :sticky="'partial'">
                                <RangeCalendar v-model="rangeValue" :number-of-months="2"
                                    @update:value="handleRangeUpdate" />
                            </PopoverContent>
                        </Popover>
                    </div>

                    <!-- Search Input -->
                    <div v-if="!hideSearch" class="flex-1 space-y-2">
                        <div class="relative">
                            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input v-model="localFilters.search" :placeholder="searchPlaceholder"
                                class="h-9 pl-8 pr-8" />
                            <Button v-if="localFilters.search" variant="ghost" size="sm"
                                class="absolute right-1 top-1 h-7 w-7 p-0" @click="localFilters.search = ''">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Apply Button -->
                    <div class="w-full sm:w-auto">
                        <Button variant="default" class="w-full sm:w-auto" @click="applyFilters">
                            Apply Filters
                        </Button>
                    </div>

                    <!-- Column Toggle (icon only) -->
                    <div class="w-full sm:w-auto hidden md:block">
                        <DataTableViewOptions :table="table" />
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
