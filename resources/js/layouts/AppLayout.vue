<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';

const props = defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout vaul-drawer-wrapper :breadcrumbs="breadcrumbs">
        <div class="flex-1 p-4">
            <slot />
        </div>
    </AppLayout>
</template>
