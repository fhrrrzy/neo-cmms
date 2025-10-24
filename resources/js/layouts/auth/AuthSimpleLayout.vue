<script setup>
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Plasma from '@/components/blocks/Backgrounds/Plasma/Plasma.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div
        class="relative flex h-svh flex-col items-center justify-center overflow-hidden"
    >
        <Plasma
            class="hidden md:block"
            color="#ff6b35"
            :speed="1.2"
            direction="reverse"
            :scale="0.9"
            :opacity="0.8"
            :mouseInteractive="false"
        />
        <div
            class="absolute top-1/2 left-1/2 w-full max-w-md -translate-x-1/2 -translate-y-1/2 rounded-lg border bg-accent p-10 md:bg-transparent md:backdrop-blur-3xl md:backdrop-brightness-[0.2]"
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
