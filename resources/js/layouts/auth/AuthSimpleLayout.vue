<script setup>
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Plasma from '@/components/blocks/Backgrounds/Plasma/Plasma.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    title: String,
    description: String,
});

// Get primary color from CSS variables
const primaryColor = ref('#ff6b35');

const getPrimaryColor = () => {
    if (typeof window === 'undefined') return '#ff6b35';

    // Get the computed style of the root element
    const rootStyles = getComputedStyle(document.documentElement);

    // Get the --primary CSS variable value
    const primaryHsl = rootStyles.getPropertyValue('--primary').trim();

    if (primaryHsl) {
        // Convert HSL to hex for the Plasma component
        // HSL format is typically "hue saturation% lightness%"
        const hslMatch = primaryHsl.match(/([\d.]+)\s+([\d.]+)%\s+([\d.]+)%/);

        if (hslMatch) {
            const h = parseFloat(hslMatch[1]);
            const s = parseFloat(hslMatch[2]) / 100;
            const l = parseFloat(hslMatch[3]) / 100;

            // Convert HSL to RGB
            const c = (1 - Math.abs(2 * l - 1)) * s;
            const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
            const m = l - c / 2;

            let r, g, b;
            if (h < 60) {
                [r, g, b] = [c, x, 0];
            } else if (h < 120) {
                [r, g, b] = [x, c, 0];
            } else if (h < 180) {
                [r, g, b] = [0, c, x];
            } else if (h < 240) {
                [r, g, b] = [0, x, c];
            } else if (h < 300) {
                [r, g, b] = [x, 0, c];
            } else {
                [r, g, b] = [c, 0, x];
            }

            // Convert to hex
            const toHex = (value) => {
                const hex = Math.round((value + m) * 255).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            };

            return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
        }
    }

    return '#ff6b35'; // Fallback color
};

onMounted(() => {
    primaryColor.value = getPrimaryColor();

    // Watch for theme changes
    const observer = new MutationObserver(() => {
        primaryColor.value = getPrimaryColor();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});
</script>

<template>
    <div
        class="relative flex h-dvh flex-col items-center justify-center overflow-hidden"
    >
        <Plasma
            class="hidden md:block"
            :color="primaryColor"
            :speed="1.2"
            direction="reverse"
            :scale="0.9"
            :opacity="0.8"
            :mouseInteractive="false"
        />
        <div
            class="absolute top-1/2 left-1/2 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border bg-accent p-10 md:bg-transparent md:backdrop-blur-[100px] dark:md:backdrop-brightness-[0.2]"
        >
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
