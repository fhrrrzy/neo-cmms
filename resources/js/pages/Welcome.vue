<template>
    <div class="min-h-screen overflow-hidden bg-background text-foreground">
        <div class="absolute inset-0 brightness-[0.3]">
            <DarkVeil
                :hue-shift="themeHue"
                :noise-intensity="0"
                :scanline-intensity="0"
                :speed="0.5"
                :scanline-frequency="0"
                :warp-amount="0"
                :resolution-scale="1"
            />
        </div>
        <!-- Navigation -->
        <nav
            class="fixed top-0 z-50 w-full border-b border-border bg-background/50 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <div
                    class="bg-gradient-to-r from-primary to-primary/70 bg-clip-text text-2xl font-bold text-transparent"
                >
                    CMMS
                </div>
                <div class="hidden items-center gap-8 md:flex">
                    <a
                        href="#"
                        class="text-muted-foreground transition hover:text-foreground"
                        >Product</a
                    >
                    <a
                        href="#"
                        class="text-muted-foreground transition hover:text-foreground"
                        >Features</a
                    >
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        :href="login()"
                        class="text-muted-foreground transition hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="login()"
                        class="rounded-full bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:shadow-lg hover:shadow-primary/50"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative min-h-dvh px-6 pt-32 pb-20">
            <div class="mx-auto max-w-7xl">
                <!-- Animated Background Elements -->
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                >
                    <motion.div
                        :initial="{ opacity: 0, scale: 0.8 }"
                        :animate="{ opacity: 0.1, scale: 1 }"
                        :transition="{ duration: 1.5, ease: 'easeOut' }"
                        class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-primary blur-3xl"
                    />
                    <motion.div
                        :initial="{ opacity: 0, scale: 0.8 }"
                        :animate="{ opacity: 0.1, scale: 1 }"
                        :transition="{
                            duration: 1.5,
                            delay: 0.2,
                            ease: 'easeOut',
                        }"
                        class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-primary/80 blur-3xl"
                    />
                </div>

                <!-- Main Content -->
                <div
                    class="relative z-10 grid items-center gap-12 md:grid-cols-2"
                >
                    <!-- Left Content -->
                    <motion.div
                        :initial="{ opacity: 0, y: 20 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.8, ease: 'easeOut' }"
                    >
                        <div
                            class="mb-6 inline-block rounded-full border border-border bg-muted/50 px-4 py-2"
                        >
                            <span class="text-sm text-primary"
                                >Real-time Equipment Monitoring</span
                            >
                        </div>

                        <h1
                            class="mb-6 text-5xl leading-tight font-bold md:text-6xl"
                        >
                            <span
                                class="bg-gradient-to-r from-primary via-primary/80 to-primary/60 bg-clip-text text-transparent"
                            >
                                See Every Machine
                            </span>
                            <br />
                            <span class="text-foreground"
                                >Keep Production Running</span
                            >
                        </h1>

                        <p
                            class="mb-8 text-lg leading-relaxed text-muted-foreground"
                        >
                            Monitor equipment health in real-time, predict
                            maintenance needs, and streamline your operations
                            with our intelligent CMMS platform built for modern
                            plants.
                        </p>

                        <div class="flex flex-col gap-4 sm:flex-row">
                            <motion.button
                                :whileHover="{ scale: 1.05 }"
                                :whileTap="{ scale: 0.95 }"
                                class="rounded-lg bg-primary px-8 py-3 font-semibold text-primary-foreground transition hover:shadow-lg hover:shadow-primary/50"
                            >
                                Start Monitoring
                            </motion.button>
                            <motion.button
                                :whileHover="{ scale: 1.05 }"
                                :whileTap="{ scale: 0.95 }"
                                class="rounded-lg border border-border px-8 py-3 font-semibold transition hover:border-primary/50"
                            >
                                View Demo
                            </motion.button>
                        </div>

                        <!-- Stats -->
                        <motion.div
                            :initial="{ opacity: 0, y: 20 }"
                            :animate="{ opacity: 1, y: 0 }"
                            :transition="{ duration: 0.8, delay: 0.3 }"
                            class="mt-12 grid grid-cols-3 gap-6"
                        >
                            <div>
                                <div class="text-3xl font-bold text-primary">
                                    98%
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Uptime Achieved
                                </p>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-primary">
                                    50%
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Maintenance Reduction
                                </p>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-primary">
                                    24/7
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Real-time Monitoring
                                </p>
                            </div>
                        </motion.div>
                    </motion.div>

                    <!-- Right Visual -->
                    <motion.div
                        :initial="{ opacity: 0, x: 40 }"
                        :animate="{ opacity: 1, x: 0 }"
                        :transition="{ duration: 0.8, delay: 0.2 }"
                        class="relative"
                    >
                        <div
                            class="relative overflow-hidden rounded-2xl border border-border bg-gradient-to-br from-card to-muted p-8"
                        >
                            <!-- Animated Grid Background -->
                            <div class="absolute inset-0 opacity-10">
                                <div class="bg-grid-pattern absolute inset-0" />
                            </div>

                            <!-- Dashboard Preview -->
                            <motion.div
                                :animate="{ y: [0, -10, 0] }"
                                :transition="{
                                    duration: 4,
                                    repeat: Infinity,
                                    ease: 'easeInOut',
                                }"
                                class="relative z-10"
                            >
                                <div class="space-y-4">
                                    <!-- Equipment Card 1 -->
                                    <motion.div
                                        :initial="{ opacity: 0, y: 20 }"
                                        :animate="{ opacity: 1, y: 0 }"
                                        :transition="{
                                            duration: 0.6,
                                            delay: 0.4,
                                        }"
                                        class="rounded-lg border border-border bg-muted/30 p-4"
                                    >
                                        <div
                                            class="mb-3 flex items-center justify-between"
                                        >
                                            <span class="font-semibold"
                                                >Pump Unit A</span
                                            >
                                            <motion.div
                                                :animate="{
                                                    scale: [1, 1.2, 1],
                                                }"
                                                :transition="{
                                                    duration: 2,
                                                    repeat: Infinity,
                                                }"
                                                class="h-3 w-3 rounded-full bg-green-500"
                                            />
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-muted"
                                        >
                                            <motion.div
                                                :animate="{
                                                    width: [
                                                        '20%',
                                                        '85%',
                                                        '20%',
                                                    ],
                                                }"
                                                :transition="{
                                                    duration: 3,
                                                    repeat: Infinity,
                                                }"
                                                class="h-full rounded-full bg-primary"
                                            />
                                        </div>
                                        <p
                                            class="mt-2 text-xs text-muted-foreground"
                                        >
                                            Operating at 78% capacity
                                        </p>
                                    </motion.div>

                                    <!-- Equipment Card 2 -->
                                    <motion.div
                                        :initial="{ opacity: 0, y: 20 }"
                                        :animate="{ opacity: 1, y: 0 }"
                                        :transition="{
                                            duration: 0.6,
                                            delay: 0.5,
                                        }"
                                        class="rounded-lg border border-border bg-muted/30 p-4"
                                    >
                                        <div
                                            class="mb-3 flex items-center justify-between"
                                        >
                                            <span class="font-semibold"
                                                >Motor B</span
                                            >
                                            <motion.div
                                                :animate="{
                                                    scale: [1, 1.2, 1],
                                                }"
                                                :transition="{
                                                    duration: 2,
                                                    repeat: Infinity,
                                                    delay: 0.3,
                                                }"
                                                class="h-3 w-3 rounded-full bg-yellow-500"
                                            />
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-muted"
                                        >
                                            <motion.div
                                                :animate="{
                                                    width: [
                                                        '60%',
                                                        '95%',
                                                        '60%',
                                                    ],
                                                }"
                                                :transition="{
                                                    duration: 3,
                                                    repeat: Infinity,
                                                }"
                                                class="h-full rounded-full bg-destructive"
                                            />
                                        </div>
                                        <p
                                            class="mt-2 text-xs text-muted-foreground"
                                        >
                                            Maintenance recommended soon
                                        </p>
                                    </motion.div>

                                    <!-- Equipment Card 3 -->
                                    <motion.div
                                        :initial="{ opacity: 0, y: 20 }"
                                        :animate="{ opacity: 1, y: 0 }"
                                        :transition="{
                                            duration: 0.6,
                                            delay: 0.6,
                                        }"
                                        class="rounded-lg border border-border bg-muted/30 p-4"
                                    >
                                        <div
                                            class="mb-3 flex items-center justify-between"
                                        >
                                            <span class="font-semibold"
                                                >Compressor C</span
                                            >
                                            <motion.div
                                                :animate="{
                                                    scale: [1, 1.2, 1],
                                                }"
                                                :transition="{
                                                    duration: 2,
                                                    repeat: Infinity,
                                                    delay: 0.6,
                                                }"
                                                class="h-3 w-3 rounded-full bg-green-500"
                                            />
                                        </div>
                                        <div
                                            class="h-2 w-full rounded-full bg-muted"
                                        >
                                            <motion.div
                                                :animate="{
                                                    width: [
                                                        '45%',
                                                        '70%',
                                                        '45%',
                                                    ],
                                                }"
                                                :transition="{
                                                    duration: 3,
                                                    repeat: Infinity,
                                                }"
                                                class="h-full rounded-full bg-primary"
                                            />
                                        </div>
                                        <p
                                            class="mt-2 text-xs text-muted-foreground"
                                        >
                                            Operating normally
                                        </p>
                                    </motion.div>
                                </div>
                            </motion.div>
                        </div>
                    </motion.div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <motion.div
            :initial="{ opacity: 0 }"
            :whileInView="{ opacity: 1 }"
            :transition="{ duration: 0.8 }"
            class="relative border-t border-border px-6 py-20"
        >
            <div class="mx-auto max-w-7xl">
                <h2 class="mb-16 text-center text-4xl font-bold">
                    <span
                        class="bg-gradient-to-r from-primary to-primary/70 bg-clip-text text-transparent"
                    >
                        Powerful Features
                    </span>
                </h2>

                <div class="grid gap-8 md:grid-cols-3">
                    <motion.div
                        v-for="(feature, i) in features"
                        :key="i"
                        :initial="{ opacity: 0, y: 20 }"
                        :whileInView="{ opacity: 1, y: 0 }"
                        :transition="{ duration: 0.6, delay: i * 0.1 }"
                        :whileHover="{ y: -5 }"
                        class="rounded-xl border border-border bg-card p-8 transition hover:border-primary/30"
                    >
                        <div class="mb-4 h-12 w-12 rounded-lg bg-primary" />
                        <h3 class="mb-3 text-xl font-semibold">
                            {{ feature }}
                        </h3>
                        <p class="text-muted-foreground">
                            Monitor equipment health and performance metrics in
                            real-time with our advanced analytics dashboard.
                        </p>
                    </motion.div>
                </div>
            </div>
        </motion.div>

        <!-- CTA Section -->
        <motion.div
            :initial="{ opacity: 0 }"
            :whileInView="{ opacity: 1 }"
            :transition="{ duration: 0.8 }"
            class="relative px-6 py-20"
        >
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="mb-6 text-4xl font-bold">
                    Ready to Transform Your Maintenance?
                </h2>
                <p class="mb-8 text-xl text-muted-foreground">
                    Join leading plants and manufacturers using CMMS to optimize
                    operations.
                </p>
                <motion.button
                    :whileHover="{ scale: 1.05 }"
                    :whileTap="{ scale: 0.95 }"
                    class="rounded-lg bg-primary px-10 py-4 text-lg font-semibold text-primary-foreground transition hover:shadow-lg hover:shadow-primary/50"
                >
                    Start Your Free Trial
                </motion.button>
            </div>
        </motion.div>
    </div>
</template>

<script setup>
import DarkVeil from '@/components/blocks/Backgrounds/DarkVeil/DarkVeil.vue';
import { login } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import { onMounted, ref } from 'vue';

const features = [
    'Real-time Monitoring',
    'Predictive Maintenance',
    'Team Collaboration',
];

const themeHue = ref(0);

// Extract hue from CSS variable
const extractHueFromTheme = () => {
    const root = document.documentElement;
    const primaryColor = getComputedStyle(root)
        .getPropertyValue('--primary')
        .trim();

    // Parse HSL value - handles both "hue sat% light%" and "hsl(hue sat% light%)" formats
    const hslMatch = primaryColor.match(/(?:hsl\()?([\d.]+)/);

    if (hslMatch && hslMatch[1]) {
        const hue = parseFloat(hslMatch[1]);
        // The base DarkVeil pattern has warm tones (~30-40°)
        // Calculate the shift needed to reach the target hue
        themeHue.value = hue - 30;
    }
};

onMounted(() => {
    extractHueFromTheme();

    // Re-extract hue when theme changes (listen for theme changes)
    const observer = new MutationObserver(() => {
        extractHueFromTheme();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
});
</script>

<style scoped>
.bg-grid-pattern {
    background-image:
        linear-gradient(
            0deg,
            transparent 24%,
            rgba(148, 163, 184, 0.05) 25%,
            rgba(148, 163, 184, 0.05) 26%,
            transparent 27%,
            transparent 74%,
            rgba(148, 163, 184, 0.05) 75%,
            rgba(148, 163, 184, 0.05) 76%,
            transparent 77%,
            transparent
        ),
        linear-gradient(
            90deg,
            transparent 24%,
            rgba(148, 163, 184, 0.05) 25%,
            rgba(148, 163, 184, 0.05) 26%,
            transparent 27%,
            transparent 74%,
            rgba(148, 163, 184, 0.05) 75%,
            rgba(148, 163, 184, 0.05) 76%,
            transparent 77%,
            transparent
        );
    background-size: 50px 50px;
}
</style>
