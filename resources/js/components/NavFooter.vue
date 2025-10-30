<script setup>
import GlobalSearch from '@/components/GlobalSearch.vue';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import syncLog from '@/routes/sync-log';
import { Link } from '@inertiajs/vue3';
import { Logs, Search } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const globalSearchOpen = ref(false);
const openGlobalSearch = () => {
    globalSearchOpen.value = true;
};
const closeGlobalSearch = () => {
    globalSearchOpen.value = false;
};

// Handle ctrl+k globally
const handleGlobalSearchShortcut = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        e.stopPropagation();
        globalSearchOpen.value = !globalSearchOpen.value;
    }
};
onMounted(() => {
    window.addEventListener('keydown', handleGlobalSearchShortcut, true);
});
onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalSearchShortcut, true);
});

const footerNavItems = [
    {
        title: 'Sync Log',
        href: syncLog.index(),
        icon: Logs,
    },
    {
        title: 'Search',
        icon: Search,
        onClick: openGlobalSearch,
        class: 'hidden md:flex',
    },
];
</script>

<template>
    <SidebarGroup :class="'group-data-[collapsible=icon]:p-0'">
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem
                    v-for="item in footerNavItems"
                    :key="item.title"
                >
                    <SidebarMenuButton :class="[item.class || '']" as-child>
                        <button
                            v-if="item.onClick"
                            @click="item.onClick"
                            type="button"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </button>
                        <Link v-else :href="toUrl(item.href)">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
        <GlobalSearch
            :open="globalSearchOpen"
            @update:open="globalSearchOpen = $event"
            @close="closeGlobalSearch"
        />
    </SidebarGroup>
</template>
