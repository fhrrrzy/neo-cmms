<script setup>
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { dashboard, jamJalanSummary, monitoring } from '@/routes';
import { router } from '@inertiajs/vue3';
import { useMagicKeys } from '@vueuse/core';
import {
    Activity,
    LayoutGrid,
    Monitor,
    Palette,
    Settings,
    User,
} from 'lucide-vue-next';
import { onUnmounted, ref, watch } from 'vue';

const open = ref(false);

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

// Use VueUse's useMagicKeys for ESC key only
// (Ctrl+K / Cmd+K is handled manually to prevent browser default)
const keys = useMagicKeys();

// Watch for ESC key to close
watch(keys.Escape, (pressed) => {
    if (pressed) {
        open.value = false;
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
    <CommandDialog :open="open" @update:open="open = $event">
        <CommandInput placeholder="Type to search..." />
        <CommandList>
            <CommandEmpty>No results found.</CommandEmpty>

            <CommandGroup heading="Pages">
                <CommandItem
                    v-for="page in pages"
                    :key="page.route"
                    :value="page.name"
                    @select="handleNavigate(page.route)"
                >
                    <component :is="page.icon" class="mr-2 h-4 w-4" />
                    <span>{{ page.name }}</span>
                </CommandItem>
            </CommandGroup>

            <CommandGroup heading="Settings">
                <CommandItem
                    v-for="page in settingsPages"
                    :key="page.route"
                    :value="page.name"
                    @select="handleNavigate(page.route)"
                >
                    <component :is="page.icon" class="mr-2 h-4 w-4" />
                    <span>{{ page.name }}</span>
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>

<style>
/* Ensure global search dialog is always on top of sheets */
[data-slot='dialog-overlay'] {
    z-index: 1000 !important;
}
[data-slot='dialog-content'] {
    z-index: 1005 !important;
}
</style>
