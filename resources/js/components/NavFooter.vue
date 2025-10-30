<script setup>
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
import { ref } from 'vue';
// Import GlobalSearch dialog singleton (if it exposes open via $globalSearch or similar)
import GlobalSearch from '@/components/GlobalSearch.vue';

const globalSearchOpen = ref(false);
const openGlobalSearch = () => {
    globalSearchOpen.value = true;
};
const closeGlobalSearch = () => {
    globalSearchOpen.value = false;
};

// All footer nav items are declared here
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
                    <SidebarMenuButton
                        :class="[
                            'text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100',
                            item.class || '',
                        ]"
                        as-child
                    >
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
        <!-- Global Search Modal -->
        <GlobalSearch
            v-if="globalSearchOpen"
            :open="globalSearchOpen"
            @close="closeGlobalSearch"
        />
    </SidebarGroup>
</template>
