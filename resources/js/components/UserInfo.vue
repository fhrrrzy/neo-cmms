<script setup>
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { computed, onMounted, ref } from 'vue';
import BoringAvatar from 'vue-boring-avatars';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    showEmail: {
        type: Boolean,
        default: false,
    },
});

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);

const colors = ref(['#92A1C6', '#146A7C', '#F0AB3D']);

onMounted(() => {
    const rootStyles = getComputedStyle(document.documentElement);
    colors.value = [
        rootStyles.getPropertyValue('--secondary').trim() || '#146A7C',
        rootStyles.getPropertyValue('--accent').trim() || '#F0AB3D',
        rootStyles.getPropertyValue('--primary').trim() || '#92A1C6',
    ];
});
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar" :alt="user.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            <BoringAvatar
                :name="user.name"
                variant="beam"
                square
                :colors="colors"
            />
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
