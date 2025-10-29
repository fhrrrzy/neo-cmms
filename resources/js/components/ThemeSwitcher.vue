<script setup lang="ts">
import { useTheme, type Theme } from '@/composables/useTheme';
import { Check } from 'lucide-vue-next';

const { currentTheme, updateTheme } = useTheme();

const themes: { value: Theme; label: string; description: string; colors: string[] }[] = [
    {
        value: 'default',
        label: 'Default',
        description: 'Default theme with balanced colors',
        colors: ['#5D4E37', '#FFB84D', '#8B7355', '#654321'],
    },
    {
        value: 'amber-minimal',
        label: 'Amber Minimal',
        description: 'Clean and modern with warm amber accents',
        colors: ['#F59E0B', '#FCD34D', '#B45309', '#78350F'],
    },
    {
        value: 'supabase',
        label: 'Supabase',
        description: 'Fresh green theme inspired by Supabase',
        colors: ['#3ECF8E', '#2DD4BF', '#10B981', '#059669'],
    },
    {
        value: 'twitter',
        label: 'Twitter',
        description: 'Classic blue theme inspired by Twitter/X',
        colors: ['#1DA1F2', '#60A5FA', '#3B82F6', '#2563EB'],
    },
    {
        value: 'solar-dusk',
        label: 'Solar Dusk',
        description: 'Warm sunset-inspired color palette',
        colors: ['#F97316', '#FB923C', '#EA580C', '#C2410C'],
    },
    {
        value: 'vintage-paper',
        label: 'Vintage Paper',
        description: 'Classic sepia tones for a timeless look',
        colors: ['#92400E', '#A16207', '#78350F', '#451A03'],
    },
];
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <button v-for="theme in themes" :key="theme.value" @click="updateTheme(theme.value)" :class="[
                'group relative flex flex-col overflow-hidden rounded-lg border-2 p-4 text-left transition-all',
                currentTheme === theme.value
                    ? 'border-primary bg-primary/5 shadow-md'
                    : 'border-border bg-card hover:border-primary/50 hover:shadow-sm',
            ]">
                <!-- Selected indicator -->
                <div v-if="currentTheme === theme.value"
                    class="absolute right-3 top-3 rounded-full bg-primary p-1 text-primary-foreground">
                    <Check class="h-4 w-4" />
                </div>

                <!-- Theme name -->
                <h3 class="mb-1 text-base font-semibold">
                    {{ theme.label }}
                </h3>

                <!-- Description -->
                <p class="mb-4 text-xs text-muted-foreground">
                    {{ theme.description }}
                </p>

                <!-- Color palette preview -->
                <div class="mt-auto flex gap-1.5">
                    <div v-for="(color, index) in theme.colors" :key="index" class="h-8 flex-1 rounded"
                        :style="{ backgroundColor: color }" />
                </div>
            </button>
        </div>

        <!-- Info text -->
        <p class="text-sm text-muted-foreground">
            Select a theme to customize the appearance of your application. The theme will be saved and applied across
            all your sessions.
        </p>
    </div>
</template>
