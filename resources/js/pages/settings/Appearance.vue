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

                <!-- Theme Preview -->
                <!-- <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-medium flex items-center gap-2">
                            <Palette class="h-4 w-4" />
                            Theme Preview
                        </h3>
                        <p class="text-sm text-muted-foreground">See how your current theme looks across different UI
                            elements</p>
                    </div>
                    <div class="rounded-lg border border-border bg-card p-6">
                        <div class="space-y-6">
                            <!-- Preview header -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-lg font-semibold">Dashboard Preview</h4>
                        <p class="text-sm text-muted-foreground">{{ currentTheme }} theme • {{ appearance }}
                            mode</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">
                            Primary Action
                        </button>
                        <button
                            class="rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground">
                            Secondary
                        </button>
                        <button
                            class="rounded-md border border-destructive bg-destructive/10 px-4 py-2 text-sm font-medium text-destructive shadow-sm hover:bg-destructive/20">
                            Destructive
                        </button>
                    </div>
                </div>

                <!-- Stats cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Stat 1 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-muted-foreground">Total Equipment</p>
                            <TrendingUp class="h-4 w-4 text-primary" />
                        </div>
                        <p class="mt-2 text-2xl font-bold">1,284</p>
                        <p class="mt-1 text-xs text-primary">+12.5% from last month</p>
                    </div>

                    <!-- Stat 2 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-muted-foreground">Active Users</p>
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                        <p class="mt-2 text-2xl font-bold">342</p>
                        <p class="mt-1 text-xs text-muted-foreground">24 online now</p>
                    </div>

                    <!-- Stat 3 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-muted-foreground">Work Orders</p>
                            <Clock class="h-4 w-4 text-primary" />
                        </div>
                        <p class="mt-2 text-2xl font-bold">56</p>
                        <p class="mt-1 text-xs text-destructive">8 overdue</p>
                    </div>

                    <!-- Stat 4 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-muted-foreground">Completion Rate</p>
                            <Star class="h-4 w-4 text-primary" />
                        </div>
                        <p class="mt-2 text-2xl font-bold">94.2%</p>
                        <p class="mt-1 text-xs text-primary">Excellent performance</p>
                    </div>
                </div>

                <!-- Info cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <!-- Card 1 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-primary/10 p-2">
                                <Bell class="h-5 w-5 text-primary" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Notifications</p>
                                <p class="text-xs text-muted-foreground">12 new messages</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-primary/10 p-2">
                                <MessageSquare class="h-5 w-5 text-primary" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Messages</p>
                                <p class="text-xs text-muted-foreground">5 unread</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-full bg-primary/10 p-2">
                                <Settings class="h-5 w-5 text-primary" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Settings</p>
                                <p class="text-xs text-muted-foreground">Recently updated</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts section -->
                <div class="space-y-3">
                    <h5 class="text-sm font-medium">Alert Styles</h5>
                    <div class="grid grid-cols-1 gap-3">
                        <!-- Success alert -->
                        <div class="flex items-start gap-3 rounded-lg border border-primary/20 bg-primary/5 p-3">
                            <CheckCircle class="h-5 w-5 flex-shrink-0 text-primary" />
                            <div class="flex-1">
                                <p class="text-sm font-medium">Success</p>
                                <p class="text-xs text-muted-foreground">Your changes have been saved
                                    successfully.</p>
                            </div>
                        </div>

                        <!-- Warning alert -->
                        <div class="flex items-start gap-3 rounded-lg border border-yellow-500/20 bg-yellow-500/5 p-3">
                            <AlertCircle class="h-5 w-5 flex-shrink-0 text-yellow-600 dark:text-yellow-500" />
                            <div class="flex-1">
                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Warning
                                </p>
                                <p class="text-xs text-yellow-700/80 dark:text-yellow-400/80">Please review
                                    this action before proceeding.</p>
                            </div>
                        </div>

                        <!-- Error alert -->
                        <div
                            class="flex items-start gap-3 rounded-lg border border-destructive/20 bg-destructive/5 p-3">
                            <XCircle class="h-5 w-5 flex-shrink-0 text-destructive" />
                            <div class="flex-1">
                                <p class="text-sm font-medium text-destructive">Error</p>
                                <p class="text-xs text-destructive/80">An error occurred while processing
                                    your request.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data table -->
                <div class="space-y-3">
                    <h5 class="text-sm font-medium">Data Table</h5>
                    <div class="rounded-md border border-border">
                        <table class="w-full">
                            <thead class="border-b border-border bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                        Equipment</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                        Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                        Location</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground">
                                        Last Check</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr class="hover:bg-muted/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">Machine A-101</p>
                                            <p class="text-xs text-muted-foreground">Production Line 1</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                            <CheckCircle class="h-3 w-3" />
                                            Active
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Building A, Floor 2
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">2 hours ago</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-primary/10">
                                            View Details
                                            <ChevronRight class="h-3 w-3" />
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-muted/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">Machine B-204</p>
                                            <p class="text-xs text-muted-foreground">Production Line 2</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-yellow-500/10 px-2.5 py-0.5 text-xs font-medium text-yellow-600 dark:text-yellow-500">
                                            <Clock class="h-3 w-3" />
                                            Maintenance
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Building B, Floor 1
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">1 day ago</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-primary/10">
                                            View Details
                                            <ChevronRight class="h-3 w-3" />
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-muted/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">Machine C-305</p>
                                            <p class="text-xs text-muted-foreground">Production Line 3</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-medium text-destructive">
                                            <XCircle class="h-3 w-3" />
                                            Offline
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Building C, Floor 3
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">3 days ago</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-primary/10">
                                            View Details
                                            <ChevronRight class="h-3 w-3" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form elements -->
                <div class="space-y-3">
                    <h5 class="text-sm font-medium">Form Elements</h5>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Input field -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Text Input</label>
                            <input type="text" placeholder="Enter text..."
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary" />
                        </div>

                        <!-- Select field -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Select Dropdown</label>
                            <select
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                <option>Option 1</option>
                                <option>Option 2</option>
                                <option>Option 3</option>
                            </select>
                        </div>

                        <!-- Textarea -->
                        <div class="space-y-2 sm:col-span-2">
                            <label class="text-sm font-medium">Textarea</label>
                            <textarea placeholder="Enter description..." rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
