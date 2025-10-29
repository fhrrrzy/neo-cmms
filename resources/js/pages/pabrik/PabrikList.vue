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
import { ArrowLeft, Building2, Factory } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Pabrik',
        href: '#',
    },
];

// Component state
const loading = ref(false);
const plants = ref([]);
const error = ref(null);

const fetchPlants = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/pabrik');
        plants.value = data.data || [];
    } catch (e) {
        error.value = 'Failed to load pabrik data';
        console.error('Error fetching plants:', e);
    } finally {
        loading.value = false;
    }
};

const navigateToPlantDetail = (plantId) => {
    router.visit(`/pabrik/${plantId}`);
};

const goBack = () => {
    router.visit('/dashboard');
};

onMounted(() => {
    fetchPlants();
});
</script>

<template>
    <Head title="Pabrik" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Page Header -->
            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Pabrik</h1>
                    <p class="text-muted-foreground">
                        All plants across regions
                    </p>
                </div>
                <Button
                    variant="outline"
                    class="w-full md:w-auto"
                    @click="goBack"
                >
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Dashboard
                </Button>
            </div>

            <!-- Loading State -->
            <div
                v-if="loading"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <Skeleton
                    v-for="i in 6"
                    :key="i"
                    class="h-40 w-full rounded-lg"
                />
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="py-12">
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Building2 />
                        </EmptyMedia>
                        <EmptyTitle>Error Loading Data</EmptyTitle>
                        <EmptyDescription>{{ error }}</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </div>

            <!-- Empty State -->
            <div v-else-if="plants.length === 0" class="py-12">
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Building2 />
                        </EmptyMedia>
                        <EmptyTitle>No Pabrik Data</EmptyTitle>
                        <EmptyDescription
                            >No pabrik data available</EmptyDescription
                        >
                    </EmptyHeader>
                </Empty>
            </div>

            <!-- Pabrik Cards Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="plant in plants"
                    :key="plant.id"
                    class="cursor-pointer transition-colors hover:bg-accent"
                    @click="navigateToPlantDetail(plant.id)"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-lg font-medium">{{
                            plant.name
                        }}</CardTitle>
                        <Factory class="h-5 w-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground"
                                    >Plant Code</span
                                >
                                <span class="text-sm font-semibold">{{
                                    plant.plant_code
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground"
                                    >Regional</span
                                >
                                <span
                                    class="text-sm font-semibold"
                                    :title="plant.regional_name"
                                >
                                    {{
                                        plant.regional_name.length > 20
                                            ? plant.regional_name.substring(
                                                  0,
                                                  20,
                                              ) + '...'
                                            : plant.regional_name
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground"
                                    >Status</span
                                >
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
                            <div
                                class="flex items-center justify-between border-t pt-2"
                            >
                                <div class="flex items-center gap-2">
                                    <Factory
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-xs text-muted-foreground"
                                        >Equipment</span
                                    >
                                </div>
                                <span class="text-lg font-bold">{{
                                    plant.equipment_count
                                }}</span>
                            </div>
                            <div
                                v-if="plant.kaps_terpasang"
                                class="flex items-center justify-between text-xs text-muted-foreground"
                            >
                                <span>Capacity</span>
                                <span>{{
                                    plant.kaps_terpasang.toLocaleString(
                                        'id-ID',
                                    )
                                }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
