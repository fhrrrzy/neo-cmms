<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import { useTheme } from '@/composables/useTheme';
import { useAppearance } from '@/composables/useAppearance';
import { Palette, Bell, MessageSquare, Settings, TrendingUp, Users, AlertCircle, CheckCircle, XCircle, Clock, Star, ChevronRight } from 'lucide-vue-next';

const { currentTheme } = useTheme();
const { appearance } = useAppearance();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />



                <!-- Dark Mode Toggle -->
                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-medium">Mode</h3>
                        <p class="text-sm text-muted-foreground">Choose your preferred display mode</p>
                    </div>
                    <AppearanceTabs />
                </div>

                <!-- Theme Selector -->
                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-medium">Theme</h3>
                        <p class="text-sm text-muted-foreground">Select a color theme for your interface</p>
                    </div>
                    <ThemeSwitcher />
                </div>


            </div>
        </SettingsLayout>
    </AppLayout>
</template>
