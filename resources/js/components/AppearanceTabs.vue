<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun, Check } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();

const modes = [
    {
        value: 'light',
        Icon: Sun,
        label: 'Light',
        description: 'Light mode with bright colors'
    },
    {
        value: 'dark',
        Icon: Moon,
        label: 'Dark',
        description: 'Dark mode with muted colors'
    },
    {
        value: 'system',
        Icon: Monitor,
        label: 'System',
        description: 'Follows your system preference'
    },
] as const;
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <button v-for="{ value, Icon, label, description } in modes" :key="value" @click="updateAppearance(value)"
            :class="[
                'group relative flex flex-col items-center justify-center rounded-lg border-2 p-6 text-center transition-all',
                appearance === value
                    ? 'border-primary bg-primary/5 shadow-md'
                    : 'border-border bg-card hover:border-primary/50 hover:shadow-sm',
            ]">
            <!-- Selected indicator -->
            <div v-if="appearance === value"
                class="absolute right-3 top-3 rounded-full bg-primary p-1 text-primary-foreground">
                <Check class="h-4 w-4" />
            </div>

            <!-- Icon -->
            <component :is="Icon" :class="[
                'mb-3 h-8 w-8 transition-colors',
                appearance === value ? 'text-primary' : 'text-muted-foreground',
            ]" />

            <!-- Label -->
            <h3 class="mb-1 text-base font-semibold">
                {{ label }}
            </h3>

            <!-- Description -->
            <p class="text-xs text-muted-foreground">
                {{ description }}
            </p>
        </button>
    </div>
</template>
