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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Stats cards -->
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-4 md:gap-4"
            >
                <!-- Regions -->
                <Card>
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
                <Card>
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
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Equipment</CardTitle
                        >
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
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Material (unique/plant)</CardTitle
                        >
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

            <div class="grid grid-cols-3">
                <a href="#" class="group relative block h-64 sm:h-80 lg:h-96">
                    <span
                        class="absolute inset-0 border-2 border-dashed border-black dark:border-zinc-700"
                    ></span>

                    <div
                        class="relative flex h-full transform items-end border-2 border-black bg-white transition-transform group-hover:-translate-x-2 group-hover:-translate-y-2 dark:bg-zinc-900"
                    >
                        <div
                            class="p-4 pt-0! transition-opacity group-hover:absolute group-hover:opacity-0 sm:p-6 lg:p-8"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-10 sm:size-12"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>

                            <h2 class="mt-4 text-xl font-medium sm:text-2xl">
                                Go around the world
                            </h2>
                        </div>

                        <div
                            class="absolute p-4 opacity-0 transition-opacity group-hover:relative group-hover:opacity-100 sm:p-6 lg:p-8"
                        >
                            <h3 class="mt-4 text-xl font-medium sm:text-2xl">
                                Go around the world
                            </h3>

                            <p class="mt-4 text-sm sm:text-base">
                                Lorem ipsum dolor sit amet consectetur
                                adipisicing elit. Cupiditate, praesentium
                                voluptatem omnis atque culpa repellendus.
                            </p>

                            <p class="mt-8 font-bold">Read more</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Demo: Working Popover + Calendar -->
            <div class="flex items-center justify-start">
                <template>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="[
                                    'w-[280px] justify-start text-left font-normal',
                                    !value ? 'text-muted-foreground' : '',
                                ]"
                            >
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{
                                    value
                                        ? df.format(
                                              value.toDate(getLocalTimeZone()),
                                          )
                                        : 'Pick a date'
                                }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <Calendar v-model="value" initial-focus />
                        </PopoverContent>
                    </Popover>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
