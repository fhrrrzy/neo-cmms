<script setup>
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { Button } from '@/components/ui/button';
import { Kbd, KbdGroup } from '@/components/ui/kbd';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const globalSearchRef = ref(null);

const handleSearchClick = () => {
    if (globalSearchRef.value) {
        globalSearchRef.value.open();
    }
};

// Detect platform for shortcut hint
const isMac =
    typeof navigator !== 'undefined'
        ? /Mac|iPhone|iPad|iPod/i.test(navigator.platform || '') ||
          /Mac/i.test(navigator.userAgent || '')
        : false;
const modKeyLabel = isMac ? '⌘' : 'Ctrl';
</script>

<template>
    <header
        class="sticky top-0 z-50 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex w-full items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <SidebarTrigger class="-ml-1" />
                <template v-if="breadcrumbs && breadcrumbs.length > 0">
                    <Breadcrumbs :breadcrumbs="breadcrumbs" />
                </template>
            </div>

            <div class="flex items-center gap-2">
                <!-- Small screens: ghost, icon-only -->
                <Button
                    variant="ghost"
                    class="inline-flex h-9 items-center px-2 md:hidden"
                    @click="handleSearchClick"
                >
                    <Search class="h-4 w-4" />
                </Button>

                <!-- md and up: outline, with label and shortcut -->
                <Button
                    variant="outline"
                    class="hidden h-9 items-center gap-2 px-3 md:inline-flex"
                    @click="handleSearchClick"
                >
                    <Search class="h-4 w-4" />
                    <span>Search</span>
                    <KbdGroup>
                        <Kbd>{{ modKeyLabel }}</Kbd>
                        <Kbd>K</Kbd>
                    </KbdGroup>
                </Button>
            </div>
        </div>

        <GlobalSearch ref="globalSearchRef" />
    </header>
</template>
