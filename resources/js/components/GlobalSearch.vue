<script setup>
import {
    CommandDialog,
    CommandEmpty,
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
import { router } from '@inertiajs/vue3';
import { useMagicKeys, useDebounceFn } from '@vueuse/core';
import {
    Activity,
    LayoutGrid,
    Monitor,
    Palette,
    Settings,
    User,
    Cpu,
    SearchX,
    MapPin,
    Factory,
} from 'lucide-vue-next';
import { onUnmounted, ref, watch, computed } from 'vue';
import axios from 'axios';

const open = ref(false);
const searchQuery = ref('');
const equipmentResults = ref([]);
const regionalResults = ref([]);
const pabrikResults = ref([]);
const isLoadingEquipment = ref(false);
const isLoadingRegional = ref(false);
const isLoadingPabrik = ref(false);

// Global routes for search
const pages = [
    {
        name: 'Dashboard',
        route: dashboard(),
        icon: LayoutGrid,
    },
    {
        name: 'Monitoring',
        route: monitoring(),
        icon: Monitor,
    },
    {
        name: 'Jam Jalan Summary',
        route: jamJalanSummary(),
        icon: Activity,
    },
];

const settingsPages = [
    {
        name: 'Profile Settings',
        route: '/settings/profile',
        icon: User,
    },
    {
        name: 'Password Settings',
        route: '/settings/password',
        icon: Settings,
    },
    {
        name: 'Appearance Settings',
        route: '/settings/appearance',
        icon: Palette,
    },
];

const handleNavigate = (route) => {
    open.value = false;
    router.visit(route);
};

// Search equipment via API with debounce
const searchEquipment = async (query) => {
    if (!query || query.length < 2) {
        equipmentResults.value = [];
        return;
    }

    isLoadingEquipment.value = true;
    try {
        const response = await axios.get('/api/equipment/search', {
            params: {
                query: query,
                limit: 5,
            },
        });
        // Force Vue to re-render by creating new array
        equipmentResults.value = [...(response.data.data || [])];
    } catch (error) {
        console.error('Equipment search error:', error);
        equipmentResults.value = [];
    } finally {
        isLoadingEquipment.value = false;
    }
};

// Search regional via API
const searchRegional = async (query) => {
    if (!query || query.length < 2) {
        regionalResults.value = [];
        return;
    }

    isLoadingRegional.value = true;
    try {
        const response = await axios.get('/api/regions', {
            params: {
                search: query,
            },
        });
        // Limit to 5 results
        regionalResults.value = (response.data.data || []).slice(0, 5);
    } catch (error) {
        console.error('Regional search error:', error);
        regionalResults.value = [];
    } finally {
        isLoadingRegional.value = false;
    }
};

// Search pabrik via API
const searchPabrik = async (query) => {
    if (!query || query.length < 2) {
        pabrikResults.value = [];
        return;
    }

    isLoadingPabrik.value = true;
    try {
        const response = await axios.get('/api/pabrik', {
            params: {
                search: query,
            },
        });
        // Limit to 5 results
        pabrikResults.value = (response.data.data || []).slice(0, 5);
    } catch (error) {
        console.error('Pabrik search error:', error);
        pabrikResults.value = [];
    } finally {
        isLoadingPabrik.value = false;
    }
};

// Debounced search to avoid excessive API calls
const debouncedSearch = useDebounceFn((query) => {
    searchEquipment(query);
    searchRegional(query);
    searchPabrik(query);
}, 300);

// Handle search input (works for both typing and pasting)
const handleSearchInput = (value) => {
    searchQuery.value = value;
    debouncedSearch(value);
};

// Watch search query changes as fallback
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery);
});

// Navigate to equipment detail page
const navigateToEquipment = (uuid) => {
    open.value = false;
    router.visit(`/equipment/${uuid}`);
};

// Navigate to regional detail page
const navigateToRegional = (id) => {
    open.value = false;
    router.visit(`/regions/${id}`);
};

// Navigate to pabrik detail page
const navigateToPabrik = (id) => {
    open.value = false;
    router.visit(`/pabrik/${id}`);
};

// Compute filtered static results based on search query
const filteredPages = computed(() => {
    if (!searchQuery.value) return pages;
    const query = searchQuery.value.toLowerCase();
    return pages.filter(page => page.name.toLowerCase().includes(query));
});

const filteredSettings = computed(() => {
    if (!searchQuery.value) return settingsPages;
    const query = searchQuery.value.toLowerCase();
    return settingsPages.filter(page => page.name.toLowerCase().includes(query));
});

// Check if there are any results at all
const hasAnyResults = computed(() => {
    if (!searchQuery.value) return true; // Don't show empty when no search query
    if (isLoadingEquipment.value || isLoadingRegional.value || isLoadingPabrik.value) return true; // Don't show empty while loading
    return filteredPages.value.length > 0 ||
        filteredSettings.value.length > 0 ||
        (searchQuery.value.length >= 2 && (
            equipmentResults.value.length > 0 ||
            regionalResults.value.length > 0 ||
            pabrikResults.value.length > 0
        ));
});

// Use VueUse's useMagicKeys for ESC key only
// (Ctrl+K / Cmd+K is handled manually to prevent browser default)
const keys = useMagicKeys();

// Watch for ESC key to close
watch(keys.Escape, (pressed) => {
    if (pressed) {
        open.value = false;
    }
});

// Reset search when dialog closes
watch(open, (isOpen) => {
    if (!isOpen) {
        searchQuery.value = '';
        equipmentResults.value = [];
        regionalResults.value = [];
        pabrikResults.value = [];
    }
});

// Manual event listener to prevent browser default search and open dialog
const handleKeyDown = (e) => {
    // Prevent browser's default Ctrl+K or Cmd+K search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        e.stopPropagation();
        // Open the search dialog
        open.value = !open.value;
    }
};

// Add event listener on mount
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeyDown, true);
}

// Cleanup on unmount
onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeyDown, true);
    }
});

defineExpose({
    open: () => (open.value = true),
});
</script>

<template>
    <CommandDialog :open="open" @update:open="open = $event" :force-z-index="9999">
        <CommandInput placeholder="Type to search..." v-model="searchQuery" @update:model-value="handleSearchInput" />
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
                            No pages, settings, or equipment match "{{ searchQuery }}"
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </div>

            <template v-else>
                <CommandGroup v-if="filteredPages.length > 0" heading="Pages">
                    <CommandItem v-for="page in filteredPages" :key="page.route" :value="page.name"
                        @select="handleNavigate(page.route)">
                        <component :is="page.icon" class="mr-2 h-4 w-4" />
                        <span>{{ page.name }}</span>
                    </CommandItem>
                </CommandGroup>

                <CommandGroup v-if="filteredSettings.length > 0" heading="Settings">
                    <CommandItem v-for="page in filteredSettings" :key="page.route" :value="page.name"
                        @select="handleNavigate(page.route)">
                        <component :is="page.icon" class="mr-2 h-4 w-4" />
                        <span>{{ page.name }}</span>
                    </CommandItem>
                </CommandGroup>

                <CommandSeparator
                    v-if="searchQuery.length >= 2 && (filteredPages.length > 0 || filteredSettings.length > 0)" />

                <!-- Regional Section -->
                <template v-if="searchQuery.length >= 2 && (isLoadingRegional || regionalResults.length > 0)">
                    <div class="overflow-hidden p-1 text-foreground">
                        <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                            Regional
                        </div>

                        <!-- Loading Skeleton -->
                        <div v-if="isLoadingRegional" class="space-y-2 px-2 py-3">
                            <div v-for="i in 3" :key="i" class="flex items-center gap-3">
                                <Skeleton class="h-4 w-4 rounded" />
                                <div class="flex-1 space-y-1.5">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <div v-else v-for="regional in regionalResults" :key="regional.id"
                            @click="navigateToRegional(regional.id)"
                            class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                            role="option">
                            <MapPin class="h-4 w-4 shrink-0" />
                            <div class="flex flex-col gap-1 flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ regional.name }}</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                        Regional #{{ regional.no }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ regional.category }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Pabrik Section -->
                <template v-if="searchQuery.length >= 2 && (isLoadingPabrik || pabrikResults.length > 0)">
                    <div class="overflow-hidden p-1 text-foreground">
                        <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                            Pabrik
                        </div>

                        <!-- Loading Skeleton -->
                        <div v-if="isLoadingPabrik" class="space-y-2 px-2 py-3">
                            <div v-for="i in 3" :key="i" class="flex items-center gap-3">
                                <Skeleton class="h-4 w-4 rounded" />
                                <div class="flex-1 space-y-1.5">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <div v-else v-for="pabrik in pabrikResults" :key="pabrik.id"
                            @click="navigateToPabrik(pabrik.id)"
                            class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                            role="option">
                            <Factory class="h-4 w-4 shrink-0" />
                            <div class="flex flex-col gap-1 flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ pabrik.name }}</span>
                                    <span v-if="pabrik.regional_name"
                                        class="text-xs px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                        {{ pabrik.regional_name }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground">#{{ pabrik.plant_code }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Equipment Section -->
                <template v-if="searchQuery.length >= 2 && (isLoadingEquipment || equipmentResults.length > 0)">
                    <div class="overflow-hidden p-1 text-foreground">
                        <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                            Equipment
                        </div>

                        <!-- Loading Skeleton -->
                        <div v-if="isLoadingEquipment" class="space-y-2 px-2 py-3">
                            <div v-for="i in 3" :key="i" class="flex items-center gap-3">
                                <Skeleton class="h-4 w-4 rounded" />
                                <div class="flex-1 space-y-1.5">
                                    <Skeleton class="h-4 w-24" />
                                    <Skeleton class="h-3 w-full" />
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <div v-else v-for="equipment in equipmentResults" :key="equipment.uuid"
                            @click="navigateToEquipment(equipment.uuid)"
                            class="relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                            role="option">
                            <Cpu class="h-4 w-4 shrink-0" />
                            <div class="flex flex-col gap-1 flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ equipment.equipment_number }}</span>
                                    <span v-if="equipment.plant"
                                        class="text-xs px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                        {{ equipment.plant.name }}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground line-clamp-1">{{
                                    equipment.equipment_description
                                }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </CommandList>
    </CommandDialog>
</template>
