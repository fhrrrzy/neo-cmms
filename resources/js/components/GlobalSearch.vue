<script setup>
import {
    CommandDialog,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Skeleton } from '@/components/ui/skeleton';
import { dashboard, jamJalanSummary, monitoring } from '@/routes';
import syncLog from '@/routes/sync-log';
import { router } from '@inertiajs/vue3';
import { useDebounceFn, useMagicKeys } from '@vueuse/core';
import axios from 'axios';
import {
    Activity,
    Cpu,
    Factory,
    LayoutGrid,
    Logs,
    MapPin,
    Monitor,
    Palette,
    SearchX,
    Settings,
    User,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ open: { type: Boolean, required: true } });
const emit = defineEmits(['update:open', 'close']);

const searchQuery = ref('');
const equipmentResults = ref([]);
const regionalResults = ref([]);
const pabrikResults = ref([]);
const isLoadingEquipment = ref(false);
const isLoadingRegional = ref(false);
const isLoadingPabrik = ref(false);

const pages = [
    { name: 'Dashboard', route: dashboard(), icon: LayoutGrid },
    { name: 'Monitoring', route: monitoring(), icon: Monitor },
    { name: 'Jam Jalan Summary', route: jamJalanSummary(), icon: Activity },
    { name: 'Sync Log', route: syncLog.index(), icon: Logs },
];
const settingsPages = [
    { name: 'Profile Settings', route: '/settings/profile', icon: User },
    { name: 'Password Settings', route: '/settings/password', icon: Settings },
    {
        name: 'Appearance Settings',
        route: '/settings/appearance',
        icon: Palette,
    },
];

const handleNavigate = (route) => {
    emit('update:open', false);
    emit('close');
    router.visit(route);
};

const searchEquipment = async (query) => {
    if (!query || query.length < 2) {
        equipmentResults.value = [];
        return;
    }
    isLoadingEquipment.value = true;
    try {
        const response = await axios.get('/api/equipment/search', {
            params: { query, limit: 5 },
        });
        equipmentResults.value = [...(response.data.data || [])];
    } catch (error) {
        equipmentResults.value = [];
    } finally {
        isLoadingEquipment.value = false;
    }
};
const searchRegional = async (query) => {
    if (!query || query.length < 2) {
        regionalResults.value = [];
        return;
    }
    isLoadingRegional.value = true;
    try {
        const response = await axios.get('/api/regions', {
            params: { search: query },
        });
        regionalResults.value = (response.data || []).slice(0, 5);
    } catch (error) {
        regionalResults.value = [];
    } finally {
        isLoadingRegional.value = false;
    }
};
const searchPabrik = async (query) => {
    if (!query || query.length < 2) {
        pabrikResults.value = [];
        return;
    }
    isLoadingPabrik.value = true;
    try {
        const response = await axios.get('/api/pabrik', {
            params: { search: query },
        });
        pabrikResults.value = (response.data.data || []).slice(0, 5);
    } catch (error) {
        pabrikResults.value = [];
    } finally {
        isLoadingPabrik.value = false;
    }
};
const debouncedSearch = useDebounceFn((query) => {
    searchEquipment(query);
    searchRegional(query);
    searchPabrik(query);
}, 300);
const handleSearchInput = (value) => {
    searchQuery.value = value;
    debouncedSearch(value);
};
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery);
});
const navigateToEquipment = (uuid) => {
    emit('update:open', false);
    emit('close');
    router.visit(`/equipment/${uuid}`);
};
const navigateToRegional = (id) => {
    emit('update:open', false);
    emit('close');
    router.visit(`/regions/${id}`);
};
const navigateToPabrik = (id) => {
    emit('update:open', false);
    emit('close');
    router.visit(`/pabrik/${id}`);
};
const filteredPages = computed(() => {
    if (!searchQuery.value) return pages;
    const query = searchQuery.value.toLowerCase();
    return pages.filter((page) => page.name.toLowerCase().includes(query));
});
const filteredSettings = computed(() => {
    if (!searchQuery.value) return settingsPages;
    const query = searchQuery.value.toLowerCase();
    return settingsPages.filter((page) =>
        page.name.toLowerCase().includes(query),
    );
});
const hasAnyResults = computed(() => {
    if (!searchQuery.value) return true;
    if (
        isLoadingEquipment.value ||
        isLoadingRegional.value ||
        isLoadingPabrik.value
    )
        return true;
    return (
        filteredPages.value.length > 0 ||
        filteredSettings.value.length > 0 ||
        (searchQuery.value.length >= 2 &&
            (equipmentResults.value.length > 0 ||
                regionalResults.value.length > 0 ||
                pabrikResults.value.length > 0))
    );
});
// Always close modal with Escape key
const keys = useMagicKeys();
watch(keys.Escape, (pressed) => {
    if (pressed && props.open) {
        emit('update:open', false);
        emit('close');
    }
});
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            searchQuery.value = '';
            equipmentResults.value = [];
            regionalResults.value = [];
            pabrikResults.value = [];
        }
    },
);
</script>

<template>
    <CommandDialog
        :open="props.open"
        @update:open="emit('update:open', $event)"
        :force-z-index="9999"
    >
        <CommandInput
            placeholder="Type to search..."
            v-model="searchQuery"
            @update:model-value="handleSearchInput"
        />
        <CommandList>
            <!-- Global Empty State - Show when nothing found anywhere -->
            <div v-if="!hasAnyResults" class="py-8">
                <Empty class="border-0">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <SearchX />
                        </EmptyMedia>
                        <EmptyTitle>No Results Found</EmptyTitle>
                        <EmptyDescription>
                            No pages, settings, or equipment match "{{
                                searchQuery
                            }}"
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </div>

            <template v-else>
                <CommandGroup v-if="filteredPages.length > 0" heading="Pages">
                    <CommandItem
                        v-for="page in filteredPages"
                        :key="page.route"
                        :value="page.name"
                        @select="handleNavigate(page.route)"
                    >
                        <component :is="page.icon" class="mr-2 h-4 w-4" />
                        <span>{{ page.name }}</span>
                    </CommandItem>
                </CommandGroup>

                <CommandGroup
                    v-if="filteredSettings.length > 0"
                    heading="Settings"
                >
                    <CommandItem
                        v-for="page in filteredSettings"
                        :key="page.route"
                        :value="page.name"
                        @select="handleNavigate(page.route)"
                    >
                        <component :is="page.icon" class="mr-2 h-4 w-4" />
                        <span>{{ page.name }}</span>
                    </CommandItem>
                </CommandGroup>

                <CommandSeparator
                    v-if="
                        searchQuery.length >= 2 &&
                        (filteredPages.length > 0 ||
                            filteredSettings.length > 0)
                    "
                />

                <!-- Regional Section -->
                <template
                    v-if="
                        searchQuery.length >= 2 &&
                        (isLoadingRegional || regionalResults.length > 0)
                    "
                >
                    <CommandGroup heading="Regional">
                        <!-- Loading Skeleton -->
                        <div
                            v-if="isLoadingRegional"
                            class="space-y-2 px-2 py-3"
                        >
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="flex items-center gap-3"
                            >
                                <Skeleton class="h-4 w-4 rounded" />
                                <div class="flex-1 space-y-1.5">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <CommandItem
                            v-else
                            v-for="regional in regionalResults"
                            :key="regional.id"
                            :value="`regional-${regional.id}-${regional.name}`"
                            @select="navigateToRegional(regional.id)"
                        >
                            <MapPin class="mr-2 h-4 w-4 shrink-0" />
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-normal">{{
                                        regional.name
                                    }}</span>
                                    <span
                                        class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                                    >
                                        Regional #{{ regional.no }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground">{{
                                    regional.category
                                }}</span>
                            </div>
                        </CommandItem>
                    </CommandGroup>
                </template>

                <!-- Pabrik Section -->
                <template
                    v-if="
                        searchQuery.length >= 2 &&
                        (isLoadingPabrik || pabrikResults.length > 0)
                    "
                >
                    <CommandGroup heading="Pabrik">
                        <!-- Loading Skeleton -->
                        <div v-if="isLoadingPabrik" class="space-y-2 px-2 py-3">
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="flex items-center gap-3"
                            >
                                <Skeleton class="h-4 w-4 rounded" />
                                <div class="flex-1 space-y-1.5">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <CommandItem
                            v-else
                            v-for="pabrik in pabrikResults"
                            :key="pabrik.id"
                            :value="`pabrik-${pabrik.id}-${pabrik.name}`"
                            @select="navigateToPabrik(pabrik.id)"
                        >
                            <Factory class="mr-2 h-4 w-4 shrink-0" />
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-normal">{{
                                        pabrik.name
                                    }}</span>
                                    <span
                                        v-if="pabrik.regional_name"
                                        class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ pabrik.regional_name }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground"
                                    >#{{ pabrik.plant_code }}</span
                                >
                            </div>
                        </CommandItem>
                    </CommandGroup>
                </template>

                <!-- Equipment Section -->
                <template
                    v-if="
                        searchQuery.length >= 2 &&
                        (isLoadingEquipment || equipmentResults.length > 0)
                    "
                >
                    <CommandGroup heading="Equipment">
                        <!-- Loading Skeleton -->
                        <div
                            v-if="isLoadingEquipment"
                            class="space-y-2 px-2 py-3"
                        >
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="flex items-center gap-3"
                            >
                                <Skeleton class="h-4 w-4 rounded" />
                                <div class="flex-1 space-y-1.5">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <CommandItem
                            v-else
                            v-for="equipment in equipmentResults"
                            :key="equipment.uuid"
                            :value="`equipment-${equipment.uuid}-${equipment.equipment_number}`"
                            @select="navigateToEquipment(equipment.uuid)"
                        >
                            <Cpu class="mr-2 h-4 w-4 shrink-0" />
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-normal">{{
                                        equipment.equipment_number
                                    }}</span>
                                    <span
                                        v-if="equipment.plant"
                                        class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ equipment.plant.name }}
                                    </span>
                                </div>
                                <span
                                    class="line-clamp-1 text-xs text-muted-foreground"
                                    >{{ equipment.equipment_description }}</span
                                >
                            </div>
                        </CommandItem>
                    </CommandGroup>
                </template>
            </template>
        </CommandList>
    </CommandDialog>
</template>
