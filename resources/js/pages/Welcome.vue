<template>
    <div
        class="min-h-screen overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white"
    >
        <!-- Navigation -->
        <nav
            class="fixed top-0 z-50 w-full border-b border-slate-800/50 bg-slate-950/50 backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <div
                    class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-2xl font-bold text-transparent"
                >
                    CMMS
                </div>
                <div class="hidden items-center gap-8 md:flex">
                    <a
                        href="#"
                        class="text-slate-300 transition hover:text-white"
                        >Product</a
                    >
                    <a
                        href="#"
                        class="text-slate-300 transition hover:text-white"
                        >Features</a
                    >
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        :href="login()"
                        class="text-slate-300 transition hover:text-white"
                    >
                        Log in
                    </Link>
                    <button
                        class="rounded-full bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-2 font-semibold transition hover:shadow-lg hover:shadow-cyan-500/50"
                    >
                        Get Started
                    </button>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative px-6 pt-32 pb-20">
            <div class="mx-auto max-w-7xl">
                <!-- Animated Background Elements -->
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                >
                    <motion.div
                        :initial="{ opacity: 0, scale: 0.8 }"
                        :animate="{ opacity: 0.1, scale: 1 }"
                        :transition="{ duration: 1.5, ease: 'easeOut' }"
                        class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-cyan-500 blur-3xl"
                    />
                    <motion.div
                        :initial="{ opacity: 0, scale: 0.8 }"
                        :animate="{ opacity: 0.1, scale: 1 }"
                        :transition="{
                            duration: 1.5,
                            delay: 0.2,
                            ease: 'easeOut',
                        }"
                        class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-blue-500 blur-3xl"
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
                            class="mb-6 inline-block rounded-full border border-slate-700 bg-slate-800/50 px-4 py-2"
                        >
                            <span class="text-sm text-cyan-400"
                                >Real-time Equipment Monitoring</span
                            >
                        </div>

                        <h1
                            class="mb-6 text-5xl leading-tight font-bold md:text-6xl"
                        >
                            <span
                                class="bg-gradient-to-r from-cyan-400 via-blue-400 to-cyan-300 bg-clip-text text-transparent"
                            >
                                See Every Machine
                            </span>
                            <br />
                            <span class="text-white"
                                >Keep Production Running</span
                            >
                        </h1>

                        <p class="mb-8 text-lg leading-relaxed text-slate-300">
                            Monitor equipment health in real-time, predict
                            maintenance needs, and streamline your operations
                            with our intelligent CMMS platform built for modern
                            plants.
                        </p>

                        <div class="flex flex-col gap-4 sm:flex-row">
                            <motion.button
                                :whileHover="{ scale: 1.05 }"
                                :whileTap="{ scale: 0.95 }"
                                class="rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 px-8 py-3 font-semibold transition hover:shadow-lg hover:shadow-cyan-500/50"
                            >
                                Start Monitoring
                            </motion.button>
                            <motion.button
                                :whileHover="{ scale: 1.05 }"
                                :whileTap="{ scale: 0.95 }"
                                class="rounded-lg border border-slate-600 px-8 py-3 font-semibold transition hover:border-slate-400"
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
                                <div class="text-3xl font-bold text-cyan-400">
                                    98%
                                </div>
                                <p class="text-sm text-slate-400">
                                    Uptime Achieved
                                </p>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-cyan-400">
                                    50%
                                </div>
                                <p class="text-sm text-slate-400">
                                    Maintenance Reduction
                                </p>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-cyan-400">
                                    24/7
                                </div>
                                <p class="text-sm text-slate-400">
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
                            class="relative overflow-hidden rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-800 to-slate-900 p-8"
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
                                        class="rounded-lg border border-slate-600/50 bg-slate-700/30 p-4"
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
                                            class="h-2 w-full rounded-full bg-slate-600/30"
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
                                                class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-500"
                                            />
                                        </div>
                                        <p class="mt-2 text-xs text-slate-400">
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
                                        class="rounded-lg border border-slate-600/50 bg-slate-700/30 p-4"
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
                                            class="h-2 w-full rounded-full bg-slate-600/30"
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
                                                class="h-full rounded-full bg-gradient-to-r from-yellow-500 to-orange-500"
                                            />
                                        </div>
                                        <p class="mt-2 text-xs text-slate-400">
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
                                        class="rounded-lg border border-slate-600/50 bg-slate-700/30 p-4"
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
                                            class="h-2 w-full rounded-full bg-slate-600/30"
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
                                                class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-500"
                                            />
                                        </div>
                                        <p class="mt-2 text-xs text-slate-400">
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
            class="relative border-t border-slate-800 px-6 py-20"
        >
            <div class="mx-auto max-w-7xl">
                <h2 class="mb-16 text-center text-4xl font-bold">
                    <span
                        class="bg-gradient-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent"
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
                        class="rounded-xl border border-slate-700/50 bg-slate-800/50 p-8 transition hover:border-cyan-500/30"
                    >
                        <div
                            class="mb-4 h-12 w-12 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600"
                        />
                        <h3 class="mb-3 text-xl font-semibold">
                            {{ feature }}
                        </h3>
                        <p class="text-slate-400">
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
                <p class="mb-8 text-xl text-slate-300">
                    Join leading plants and manufacturers using CMMS to optimize
                    operations.
                </p>
                <motion.button
                    :whileHover="{ scale: 1.05 }"
                    :whileTap="{ scale: 0.95 }"
                    class="rounded-lg bg-gradient-to-r from-cyan-500 to-blue-600 px-10 py-4 text-lg font-semibold transition hover:shadow-lg hover:shadow-cyan-500/50"
                >
                    Start Your Free Trial
                </motion.button>
            </div>
        </motion.div>
    </div>
</template>

<script setup>
import { login } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { motion } from 'motion-v';
const features = [
    'Real-time Monitoring',
    'Predictive Maintenance',
    'Team Collaboration',
];
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
