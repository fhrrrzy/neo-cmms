<script setup>
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { DateFormatter, getLocalTimeZone } from '@internationalized/date';
import axios from 'axios';
import {
    Boxes,
    Calendar as CalendarIcon,
    Factory,
    Globe2,
    Wrench,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
});

const value = ref();

// Stats state
const loading = ref(false);
const error = ref('');
const stats = ref({
    total_regions: 0,
    total_plants: 0,
    total_equipment: 0,
    total_materials_unique_per_plant: 0,
});

const fetchStats = async () => {
    loading.value = true;
    error.value = '';
    try {
        // Expected response shape:
        // { total_regions, total_plants, total_equipment, total_materials_unique_per_plant }
        const { data } = await axios.get('/api/stats/overview');
        if (data) {
            stats.value.total_regions = Number(data.total_regions) || 0;
            stats.value.total_plants = Number(data.total_plants) || 0;
            stats.value.total_equipment = Number(data.total_equipment) || 0;
            stats.value.total_materials_unique_per_plant =
                Number(data.total_materials_unique_per_plant) || 0;
        }
    } catch (e) {
        // Fallback: try separate endpoints if available; ignore failures silently
        try {
            const [regions, plants, equipments, materials] = await Promise.all([
                axios
                    .get('/api/regions/count')
                    .catch(() => ({ data: { count: 0 } })),
                axios
                    .get('/api/plants/count')
                    .catch(() => ({ data: { count: 0 } })),
                axios
                    .get('/api/equipment/count')
                    .catch(() => ({ data: { count: 0 } })),
                axios
                    .get('/api/materials/unique-per-plant/count')
                    .catch(() => ({ data: { count: 0 } })),
            ]);
            stats.value.total_regions = Number(regions?.data?.count) || 0;
            stats.value.total_plants = Number(plants?.data?.count) || 0;
            stats.value.total_equipment = Number(equipments?.data?.count) || 0;
            stats.value.total_materials_unique_per_plant =
                Number(materials?.data?.count) || 0;
        } catch {
            error.value = 'Failed to load stats';
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
});

const navigateToRegions = () => {
    router.visit('/regions');
};

const navigateToPabrik = () => {
    router.visit('/pabrik');
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Stats cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-4 md:gap-4">
                <!-- Regions -->
                <Card
                    class="cursor-pointer transition-colors hover:bg-accent"
                    @click="navigateToRegions"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Region</CardTitle
                        >
                        <Globe2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{
                                loading
                                    ? '—'
                                    : stats.total_regions.toLocaleString(
                                        'id-ID',
                                    )
                            }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Jumlah regional terdaftar
                        </p>
                    </CardContent>
                </Card>

                <!-- Plants/Factories -->
                <Card
                    class="cursor-pointer transition-colors hover:bg-accent"
                    @click="navigateToPabrik"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Factory</CardTitle
                        >
                        <Factory class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{
                                loading
                                    ? '—'
                                    : stats.total_plants.toLocaleString('id-ID')
                            }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Jumlah pabrik/plant aktif
                        </p>
                    </CardContent>
                </Card>

                <!-- Equipment -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Equipment</CardTitle>
                        <Wrench class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{
                                loading
                                    ? '—'
                                    : stats.total_equipment.toLocaleString(
                                        'id-ID',
                                    )
                            }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Total equipment terdata
                        </p>
                    </CardContent>
                </Card>

                <!-- Materials unique per plant -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Material (unique/plant)</CardTitle>
                        <Boxes class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{
                                loading
                                    ? '—'
                                    : stats.total_materials_unique_per_plant.toLocaleString(
                                        'id-ID',
                                    )
                            }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Unik per plant (material sama di plant berbeda
                            dihitung)
                        </p>
                    </CardContent>
                </Card>
            </div>


        </div>
    </AppLayout>
</template>
