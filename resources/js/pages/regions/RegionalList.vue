<script setup>
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
import { ArrowLeft, Factory, Globe2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Regional',
        href: '#',
    },
];

// Component state
const loading = ref(false);
const regions = ref([]);
const error = ref(null);

const fetchRegions = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/regions');
        regions.value = data || [];
    } catch (e) {
        error.value = 'Failed to load regional data';
        console.error('Error fetching regions:', e);
    } finally {
        loading.value = false;
    }
};

const navigateToRegionDetail = (regionUuid) => {
    router.visit(`/regions/${regionUuid}`);
};

const goBack = () => {
    router.visit('/dashboard');
};

onMounted(() => {
    fetchRegions();
});
</script>

<template>
    <Head title="Regional" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Page Header -->
            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Regional</h1>
                    <p class="text-muted-foreground">
                        Regional data across the organization
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
                    class="h-32 w-full rounded-lg"
                />
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="py-12">
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Globe2 />
                        </EmptyMedia>
                        <EmptyTitle>Error Loading Data</EmptyTitle>
                        <EmptyDescription>{{ error }}</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </div>

            <!-- Empty State -->
            <div v-else-if="regions.length === 0" class="py-12">
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Globe2 />
                        </EmptyMedia>
                        <EmptyTitle>No Regional Data</EmptyTitle>
                        <EmptyDescription
                            >No regional data available</EmptyDescription
                        >
                    </EmptyHeader>
                </Empty>
            </div>

            <!-- Regional Cards Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="region in regions"
                    :key="region.uuid"
                    class="cursor-pointer transition-colors hover:bg-accent"
                    @click="navigateToRegionDetail(region.uuid)"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-lg font-medium">{{
                            region.name
                        }}</CardTitle>
                        <Globe2 class="h-5 w-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground"
                                    >Category</span
                                >
                                <span class="text-sm font-semibold">{{
                                    region.category
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground"
                                    >Regional No.</span
                                >
                                <span class="text-sm font-semibold">{{
                                    region.no
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between border-t pt-2"
                            >
                                <div class="flex items-center gap-2">
                                    <Factory
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-xs text-muted-foreground"
                                        >Plants</span
                                    >
                                </div>
                                <span class="text-lg font-bold">{{
                                    region.plants_count
                                }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
