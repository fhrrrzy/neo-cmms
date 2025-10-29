<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    Factory,
    FileText,
    Wrench,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    id: {
        type: Number,
        required: true,
    },
});

// Component state
const loading = ref(false);
const notFound = ref(false);
const region = ref(null);
const stats = ref(null);
const plants = ref([]);
const error = ref(null);

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Regional',
        href: '/regions',
    },
    {
        title: region.value?.name || 'Loading...',
        href: '#',
    },
]);

const fetchRegionDetail = async () => {
    loading.value = true;
    error.value = null;
    notFound.value = false;
    try {
        const { data } = await axios.get(`/api/regions/${props.id}`);
        region.value = data.region;
        stats.value = data.stats;
        plants.value = data.plants || [];
    } catch (e) {
        if (e.response?.status === 404) {
            notFound.value = true;
        } else {
            error.value = 'Failed to load regional data';
        }
        console.error('Error fetching region detail:', e);
    } finally {
        loading.value = false;
    }
};

const navigateToPlant = (plantId) => {
    router.visit(`/pabrik/${plantId}`);
};

const goBack = () => {
    router.visit('/regions');
};

onMounted(() => {
    fetchRegionDetail();
});
</script>

<template>

    <Head :title="region?.name || 'Regional Detail'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="w-full space-y-2">
                        <Skeleton class="h-8 w-2/3" />
                        <Skeleton class="h-4 w-40" />
                        <Skeleton class="h-3 w-56" />
                        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <Skeleton v-for="i in 4" :key="i" class="h-10 w-full" />
                        </div>
                    </div>
                    <div class="hidden w-72 md:block">
                        <Skeleton class="h-10 w-full" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <Skeleton v-for="i in 4" :key="i" class="h-24 w-full" />
                </div>
                <div>
                    <Skeleton class="h-10 w-64" />
                    <div class="mt-4 space-y-3">
                        <Skeleton v-for="i in 5" :key="i" class="h-12 w-full" />
                    </div>
                </div>
            </div>

            <!-- Not Found State -->
            <div v-else-if="notFound" class="flex min-h-[calc(100vh-15rem)] items-center justify-center px-6">
                <div class="space-y-4 text-center">
                    <p class="text-4xl font-semibold text-primary sm:text-2xl md:text-5xl">
                        Region not found
                    </p>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Regional List
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <template v-else-if="region && stats">
                <!-- Header Section -->
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ region.name }}
                        </h1>
                        <p class="text-muted-foreground">
                            Regional #{{ region.no }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Category: {{ region.category }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 md:flex-nowrap">
                        <Button variant="outline" class="w-full md:w-auto" @click="goBack">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Regional
                        </Button>
                    </div>
                </div>

                <!-- Statistics Cards Section -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <!-- Total Plants -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Factory class="h-5 w-5" />
                            <CardTitle class="text-sm">Total Plants</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{ stats.total_plants.toLocaleString('id-ID') }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Plants in region
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Equipment -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Wrench class="h-5 w-5" />
                            <CardTitle class="text-sm">Total Equipment</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_equipment.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Across all plants
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Work Orders -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            <CardTitle class="text-sm">Total Work Orders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_work_orders.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                All work orders
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Active Plants -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5" />
                            <CardTitle class="text-sm">Active Plants</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.active_plants.toLocaleString('id-ID')
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Currently active
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Plants Data Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Factory class="h-5 w-5" />
                            Plants in This Region
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Empty State -->
                        <div v-if="plants.length === 0" class="py-8">
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Factory />
                                    </EmptyMedia>
                                    <EmptyTitle>No Plants</EmptyTitle>
                                    <EmptyDescription>No plants found in this
                                        region</EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        </div>

                        <!-- Mobile: Card Layout -->
                        <div v-else class="space-y-4 md:hidden">
                            <Card v-for="plant in plants" :key="plant.id"
                                class="cursor-pointer transition-colors hover:bg-accent"
                                @click="navigateToPlant(plant.id)">
                                <CardContent class="p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-1">
                                            <p class="font-semibold">
                                                {{ plant.name }}
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                #{{ plant.plant_code }}
                                            </p>
                                            <div class="flex items-center gap-2">
                                                <Badge :variant="plant.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                                    ">
                                                    {{
                                                        plant.is_active
                                                            ? 'Active'
                                                            : 'Inactive'
                                                    }}
                                                </Badge>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold">
                                                {{ plant.equipment_count }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                Equipment
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Desktop: Table Layout -->
                        <div v-if="plants.length > 0" class="hidden overflow-x-auto md:block">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b bg-muted text-muted-foreground">
                                        <th class="px-4 py-3 text-left text-sm font-medium">
                                            Plant Name
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">
                                            Plant Code
                                        </th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">
                                            Status
                                        </th>
                                        <th class="px-4 py-3 text-right text-sm font-medium">
                                            Equipment Count
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="plant in plants" :key="plant.id"
                                        class="cursor-pointer border-b transition-colors hover:bg-muted/50"
                                        @click="navigateToPlant(plant.id)">
                                        <td class="px-4 py-3 font-medium">
                                            {{ plant.name }}
                                        </td>
                                        <td class="px-4 py-3 text-muted-foreground">
                                            {{ plant.plant_code }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="plant.is_active
                                                ? 'default'
                                                : 'secondary'
                                                ">
                                                {{
                                                    plant.is_active
                                                        ? 'Active'
                                                        : 'Inactive'
                                                }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ plant.equipment_count }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </AppLayout>
</template>
