<script setup lang="ts">
import { useTheme, type Theme } from '@/composables/useTheme';
import { useAppearance } from '@/composables/useAppearance';
import { Check, Moon, Sun, Monitor } from 'lucide-vue-next';
import { computed } from 'vue';

const { currentTheme, updateTheme } = useTheme();
const { appearance } = useAppearance();

// Compute the current appearance icon and label
const appearanceInfo = computed(() => {
    switch (appearance.value) {
        case 'light':
            return { icon: Sun, label: 'Light mode' };
        case 'dark':
            return { icon: Moon, label: 'Dark mode' };
        case 'system':
            return { icon: Monitor, label: 'System preference' };
        default:
            return { icon: Monitor, label: 'System preference' };
    }
});

const themes: { value: Theme; label: string; description: string; colors: string[] }[] = [
    {
        value: 'default',
        label: 'Default',
        description: 'Default theme with balanced colors',
        colors: ['hsl(223.8136 0.0000% 9.0527%)', 'hsl(223.8136 0.0002% 96.0587%)', 'hsl(223.8136 0.0001% 89.8161%)', 'hsl(223.8136 0.0000% 63.0163%)'],
    },
    {
        value: 'amber-minimal',
        label: 'Amber Minimal',
        description: 'Clean and modern with warm amber accents',
        colors: ['hsl(37.6923 92.1260% 50.1961%)', 'hsl(32.1327 94.6188% 43.7255%)', 'hsl(25.9649 90.4762% 37.0588%)', 'hsl(22.7273 82.5000% 31.3725%)'],
    },
    {
        value: 'modern-minimal',
        label: 'Modern Minimal',
        description: 'Sleek blue minimalist design',
        colors: ['hsl(217.2193 91.2195% 59.8039%)', 'hsl(221.2121 83.1933% 53.3333%)', 'hsl(224.2781 76.3265% 48.0392%)', 'hsl(225.9310 70.7317% 40.1961%)'],
    },
    {
        value: 'nature',
        label: 'Nature',
        description: 'Fresh green nature-inspired palette',
        colors: ['hsl(142.0859 70.5628% 45.2941%)', 'hsl(160.1183 84.0796% 39.4118%)', 'hsl(161.3793 93.5484% 30.3922%)', 'hsl(162.9310 93.5484% 24.3137%)'],
    },
    {
        value: 'nothern-lights',
        label: 'Northern Lights',
        description: 'Aurora-inspired vibrant colors',
        colors: ['hsl(139.6552 52.7273% 43.1373%)', 'hsl(218.5401 79.1908% 66.0784%)', 'hsl(189.6350 81.0651% 66.8627%)', 'hsl(207.2727 44% 49.0196%)'],
    },
    {
        value: 'ocean-breeze',
        label: 'Ocean Breeze',
        description: 'Cool oceanic blue and teal tones',
        colors: ['hsl(199.6154 89.1304% 48.2353%)', 'hsl(187.5000 85.3659% 53.1373%)', 'hsl(188.0952 94.5055% 42.7451%)', 'hsl(197.1429 71.4286% 33.5294%)'],
    },
    {
        value: 'supabase',
        label: 'Supabase',
        description: 'Fresh green theme inspired by Supabase',
        colors: ['hsl(151.3274 66.8639% 66.8627%)', 'hsl(217.2193 91.2195% 59.8039%)', 'hsl(258.3117 89.5349% 66.2745%)', 'hsl(37.6923 92.1260% 50.1961%)'],
    },
    {
        value: 'twitter',
        label: 'Twitter',
        description: 'Classic blue theme inspired by Twitter/X',
        colors: ['hsl(203.8863 88.2845% 53.1373%)', 'hsl(159.7826 100% 36.0784%)', 'hsl(42.0290 92.8251% 56.2745%)', 'hsl(147.1429 78.5047% 41.9608%)'],
    },
    {
        value: 'solar-dusk',
        label: 'Solar Dusk',
        description: 'Warm sunset-inspired color palette',
        colors: ['hsl(25.9649 90.4762% 37.0588%)', 'hsl(25.0000 5.2632% 44.7059%)', 'hsl(35.4545 91.6667% 32.9412%)', 'hsl(40.6061 96.1165% 40.3922%)'],
    },
    {
        value: 'vintage-paper',
        label: 'Vintage Paper',
        description: 'Classic sepia tones for a timeless look',
        colors: ['hsl(30.0000 33.8710% 48.6275%)', 'hsl(31.3846 29.9539% 42.5490%)', 'hsl(33.6842 32.9480% 33.9216%)', 'hsl(29.1176 30.9091% 56.8627%)'],
    },
];
</script>

<template>
    <div class="space-y-4">
        <!-- Current appearance mode indicator -->
        <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/50 px-4 py-2.5">
            <component :is="appearanceInfo.icon" class="h-4 w-4 text-muted-foreground" />
            <div class="flex flex-col">
                <span class="text-sm font-medium">Current display mode</span>
                <span class="text-xs text-muted-foreground">{{ appearanceInfo.label }}</span>
            </div>
        </div>

        <!-- Theme grid -->
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
        <div class="space-y-2 rounded-lg border border-border bg-card p-4">
            <p class="text-sm font-medium">About themes</p>
            <p class="text-sm text-muted-foreground">
                Select a color theme to customize your interface. Each theme automatically adapts to your selected
                appearance mode (light/dark). The theme will be saved and applied across all your sessions.
            </p>
        </div>
    </div>
</template>
