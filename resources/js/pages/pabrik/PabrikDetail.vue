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
    Activity,
    ArrowLeft,
    Building2,
    ChevronLeft,
    ChevronRight,
    ClipboardCheck,
    FileText,
    Gauge,
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
const plant = ref(null);
const stats = ref(null);
const equipment = ref({
    data: [],
    total: 0,
    per_page: 25,
    current_page: 1,
    last_page: 1,
});
const error = ref(null);

// Pagination state
const currentPage = ref(1);
const perPage = ref(25);

const breadcrumbs = computed(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Pabrik',
        href: '/pabrik',
    },
    {
        title: plant.value?.name || 'Loading...',
        href: '#',
    },
]);

const fetchPlantDetail = async (page = 1) => {
    loading.value = true;
    error.value = null;
    notFound.value = false;
    try {
        const { data } = await axios.get(`/api/pabrik/${props.id}`, {
            params: {
                per_page: perPage.value,
                page: page,
            },
        });
        plant.value = data.plant;
        stats.value = data.stats;
        equipment.value = data.equipment;
        currentPage.value = data.equipment.current_page;
    } catch (e) {
        if (e.response?.status === 404) {
            notFound.value = true;
        } else {
            error.value = 'Failed to load plant data';
        }
        console.error('Error fetching plant detail:', e);
    } finally {
        loading.value = false;
    }
};

const navigateToEquipment = (equipmentUuid) => {
    router.visit(`/equipment/${equipmentUuid}`);
};

const goBack = () => {
    router.visit('/pabrik');
};

const navigateToRegional = () => {
    if (plant.value?.regional_id) {
        router.visit(`/regions/${plant.value.regional_id}`);
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= equipment.value.last_page) {
        fetchPlantDetail(page);
    }
};

const nextPage = () => {
    if (currentPage.value < equipment.value.last_page) {
        goToPage(currentPage.value + 1);
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        goToPage(currentPage.value - 1);
    }
};

onMounted(() => {
    fetchPlantDetail();
});
</script>

<template>
    <Head :title="plant?.name || 'Plant Detail'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-6">
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div class="w-full space-y-2">
                        <Skeleton class="h-8 w-2/3" />
                        <Skeleton class="h-4 w-40" />
                        <Skeleton class="h-3 w-56" />
                        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <Skeleton
                                v-for="i in 6"
                                :key="i"
                                class="h-10 w-full"
                            />
                        </div>
                    </div>
                    <div class="hidden w-72 md:block">
                        <Skeleton class="h-10 w-full" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6">
                    <Skeleton v-for="i in 6" :key="i" class="h-24 w-full" />
                </div>
                <div>
                    <Skeleton class="h-10 w-64" />
                    <div class="mt-4 space-y-3">
                        <Skeleton
                            v-for="i in 8"
                            :key="i"
                            class="h-12 w-full"
                        />
                    </div>
                </div>
            </div>

            <!-- Not Found State -->
            <div
                v-else-if="notFound"
                class="flex min-h-[calc(100vh-15rem)] items-center justify-center px-6"
            >
                <div class="space-y-4 text-center">
                    <p
                        class="text-4xl font-semibold text-primary sm:text-2xl md:text-5xl"
                    >
                        Plant not found
                    </p>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Pabrik List
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <template v-else>
                <!-- Header Section -->
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
                >
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ plant.name }}
                        </h1>
                        <p class="text-muted-foreground">
                            #{{ plant.plant_code }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <button
                                class="hover:underline"
                                @click="navigateToRegional"
                            >
                                {{ plant.regional_name }}
                            </button>
                        </p>

                        <!-- Plant Details Grid -->
                        <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <div class="space-y-1">
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Installed Capacity
                                </p>
                                <p class="text-sm font-semibold">
                                    {{
                                        plant.kaps_terpasang
                                            ? plant.kaps_terpasang.toLocaleString(
                                                  'id-ID',
                                              )
                                            : 'N/A'
                                    }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Unit Count
                                </p>
                                <p class="text-sm font-semibold">
                                    {{ plant.unit || 'N/A' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Status
                                </p>
                                <Badge
                                    :variant="
                                        plant.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        plant.is_active ? 'Active' : 'Inactive'
                                    }}
                                </Badge>
                            </div>
                            <div class="space-y-1">
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Bunch Press
                                </p>
                                <Badge
                                    :variant="
                                        plant.instalasi_bunch_press
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        plant.instalasi_bunch_press
                                            ? 'Installed'
                                            : 'Not Installed'
                                    }}
                                </Badge>
                            </div>
                            <div class="space-y-1">
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Cofiring
                                </p>
                                <Badge
                                    :variant="
                                        plant.cofiring
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{ plant.cofiring ? 'Yes' : 'No' }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 md:flex-nowrap">
                        <Button
                            variant="outline"
                            class="w-full md:w-auto"
                            @click="goBack"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Pabrik
                        </Button>
                    </div>
                </div>

                <!-- Statistics Cards Section -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6">
                    <!-- Total Equipment -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Wrench class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Total Equipment</CardTitle
                            >
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
                                Equipment count
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Stations -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            <CardTitle class="text-sm">Total Stations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.total_stations.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Stations in plant
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total Work Orders -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Total Work Orders</CardTitle
                            >
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

                    <!-- Active Work Orders -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <ClipboardCheck class="h-5 w-5" />
                            <CardTitle class="text-sm"
                                >Active Work Orders</CardTitle
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    stats.active_work_orders.toLocaleString(
                                        'id-ID',
                                    )
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Currently active
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Installed Capacity -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Gauge class="h-5 w-5" />
                            <CardTitle class="text-sm">Capacity</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">
                                {{
                                    plant.kaps_terpasang
                                        ? plant.kaps_terpasang.toLocaleString(
                                              'id-ID',
                                          )
                                        : '—'
                                }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Installed capacity
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Equipment Utilization (placeholder) -->
                    <Card>
                        <CardHeader class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            <CardTitle class="text-sm">Utilization</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">—</div>
                            <p class="text-xs text-muted-foreground">
                                Equipment usage
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Equipment List Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Wrench class="h-5 w-5" />
                            Equipment List
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Empty State -->
                        <div v-if="equipment.data.length === 0" class="py-8">
                            <Empty>
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <Wrench />
                                    </EmptyMedia>
                                    <EmptyTitle>No Equipment</EmptyTitle>
                                    <EmptyDescription
                                        >No equipment found in this
                                        plant</EmptyDescription
                                    >
                                </EmptyHeader>
                            </Empty>
                        </div>

                        <!-- Mobile: Card Layout -->
                        <div v-else class="space-y-4 md:hidden">
                            <Card
                                v-for="item in equipment.data"
                                :key="item.uuid"
                                class="cursor-pointer transition-colors hover:bg-accent"
                                @click="navigateToEquipment(item.uuid)"
                            >
                                <CardContent class="p-4">
                                    <div class="space-y-2">
                                        <div>
                                            <p class="font-semibold">
                                                {{ item.equipment_description }}
                                            </p>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                #{{ item.equipment_number }}
                                            </p>
                                        </div>
                                        <div
                                            class="grid grid-cols-2 gap-2 text-sm"
                                        >
                                            <div>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Station
                                                </p>
                                                <p class="font-medium">
                                                    {{ item.station_description }}
                                                </p>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    Type
                                                </p>
                                                <p class="font-medium">
                                                    {{ item.equipment_type }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            class="border-t pt-2 text-sm text-muted-foreground"
                                        >
                                            Running Hours:
                                            <span class="font-medium">{{
                                                item.latest_running_hours
                                            }}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Desktop: Table Layout -->
                        <div
                            v-if="equipment.data.length > 0"
                            class="hidden overflow-x-auto md:block"
                        >
                            <table class="w-full">
                                <thead>
                                    <tr
                                        class="border-b bg-muted text-muted-foreground"
                                    >
                                        <th
                                            class="px-4 py-3 text-left text-sm font-medium"
                                        >
                                            Equipment Number
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-sm font-medium"
                                        >
                                            Description
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-sm font-medium"
                                        >
                                            Station
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-sm font-medium"
                                        >
                                            Type
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right text-sm font-medium"
                                        >
                                            Running Hours
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in equipment.data"
                                        :key="item.uuid"
                                        class="cursor-pointer border-b transition-colors hover:bg-muted/50"
                                        @click="navigateToEquipment(item.uuid)"
                                    >
                                        <td class="px-4 py-3 font-medium">
                                            {{ item.equipment_number }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ item.equipment_description }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ item.station_description }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ item.equipment_type }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ item.latest_running_hours }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="equipment.data.length > 0"
                            class="mt-4 flex items-center justify-between border-t pt-4"
                        >
                            <div class="text-sm text-muted-foreground">
                                Showing {{ (currentPage - 1) * perPage + 1 }} to
                                {{
                                    Math.min(
                                        currentPage * perPage,
                                        equipment.total,
                                    )
                                }}
                                of {{ equipment.total }} equipment
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="currentPage === 1"
                                    @click="prevPage"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    Previous
                                </Button>
                                <div class="text-sm">
                                    Page {{ currentPage }} of
                                    {{ equipment.last_page }}
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="
                                        currentPage === equipment.last_page
                                    "
                                    @click="nextPage"
                                >
                                    Next
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </AppLayout>
</template>
