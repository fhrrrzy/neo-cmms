<template>
    <Head title="Equipment Detail" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Equipment Detail
                    </h1>
                    <p class="text-muted-foreground">
                        Equipment Number: {{ props.equipment.equipment_number }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Popover v-model:open="popoverOpen">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="[
                                    'w-[280px] justify-start text-left font-normal',
                                    isRangeEmpty ? 'text-muted-foreground' : '',
                                ]"
                            >
                                <Calendar class="mr-2 h-4 w-4" />
                                {{ rangeDisplay }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <RangeCalendar
                                v-model="uiRange"
                                :number-of-months="2"
                                @update:model-value="handleUiRangeChange"
                            />
                        </PopoverContent>
                    </Popover>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Monitoring
                    </Button>
                </div>
            </div>

            <!-- Basic Information Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Settings class="h-5 w-5" />
                        Basic Information
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                    >
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Equipment Number
                            </label>
                            <p class="font-mono text-sm">
                                {{ props.equipment.equipment_number }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Description
                            </label>
                            <p class="text-sm">
                                {{
                                    props.equipment.equipment_description ||
                                    'N/A'
                                }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Company Code
                            </label>
                            <Badge variant="secondary">
                                {{ props.equipment.company_code || 'N/A' }}
                            </Badge>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Object Number
                            </label>
                            <p class="font-mono text-sm">
                                {{ props.equipment.object_number || 'N/A' }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Point
                            </label>
                            <p class="text-sm">
                                {{ props.equipment.point || 'N/A' }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Cumulative Running Hours
                            </label>
                            <p class="text-sm font-semibold">
                                {{
                                    formatNumber(
                                        props.equipment
                                            .cumulative_running_hours,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Location Information Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MapPin class="h-5 w-5" />
                        Location Information
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Plant
                            </label>
                            <p class="text-sm">
                                {{ props.equipment.plant?.name || 'N/A' }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Station
                            </label>
                            <p class="text-sm">
                                {{
                                    props.equipment.station?.description ||
                                    'N/A'
                                }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Running Time Chart -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BarChart3 class="h-5 w-5" />
                        Running Time Analysis
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.equipment.recent_running_times?.length > 0"
                    >
                        <div ref="chartContainer" class="w-full"></div>
                    </div>
                    <div v-else class="py-8 text-center text-muted-foreground">
                        <BarChart3 class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p>
                            No running times data available for the selected
                            period
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Running Times Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Running Times Data
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.equipment.recent_running_times?.length > 0"
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right"
                                        >Counter Reading</TableHead
                                    >
                                    <TableHead class="text-right"
                                        >Running Hours</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(time, index) in props.equipment
                                        .recent_running_times"
                                    :key="index"
                                >
                                    <TableCell class="font-medium">
                                        {{ formatDate(time.date) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ formatNumber(time.counter_reading) }}
                                    </TableCell>
                                    <TableCell class="text-right font-mono">
                                        {{ formatNumber(time.running_hours) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="py-8 text-center text-muted-foreground">
                        <Calendar class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p>No running times data available</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Highcharts from 'highcharts';
import {
    ArrowLeft,
    BarChart3,
    Calendar,
    Clock,
    MapPin,
    Settings,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    equipmentNumber: {
        type: String,
        required: true,
    },
    equipment: {
        type: Object,
        required: true,
    },
});

const chartContainer = ref(null);
const chart = ref(null);

const breadcrumbs = computed(() => [
    {
        title: 'Monitoring',
        href: '/monitoring',
    },
    {
        title: props.equipment.equipment_number,
        href: '#',
    },
]);

const dateRange = ref({
    start:
        props.equipment.date_range?.start ||
        new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
    end:
        props.equipment.date_range?.end ||
        new Date().toISOString().split('T')[0],
});
const popoverOpen = ref(false);
const uiRange = ref({
    from: dateRange.value.start ? new Date(dateRange.value.start) : null,
    to: dateRange.value.end ? new Date(dateRange.value.end) : null,
});

const isRangeEmpty = computed(() => !uiRange.value.from && !uiRange.value.to);
const rangeDisplay = computed(() => {
    if (!uiRange.value.from && !uiRange.value.to) return 'Select date range';
    const formatStr = (d) => d.toISOString().split('T')[0];
    const fromStr = uiRange.value.from ? formatStr(uiRange.value.from) : '';
    const toStr = uiRange.value.to ? formatStr(uiRange.value.to) : '';
    return `${fromStr} - ${toStr}`;
});

const goBack = () => {
    router.visit('/monitoring');
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatNumber = (value) => {
    if (!value || value === 0) return 'No data';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

const handleUiRangeChange = (newRange) => {
    uiRange.value = newRange;
    const start = newRange.from ? new Date(newRange.from) : null;
    const end = newRange.to ? new Date(newRange.to) : null;
    const toStr = (d) =>
        `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const startStr = start ? toStr(start) : null;
    const endStr = end ? toStr(end) : null;
    if (startStr && endStr) {
        dateRange.value = { start: startStr, end: endStr };
        popoverOpen.value = false;
        router.visit(`/equipment/${props.equipmentNumber}`, {
            data: { date_start: startStr, date_end: endStr },
            preserveState: false,
        });
    }
};

const createChart = () => {
    if (!chartContainer.value || !props.equipment.recent_running_times?.length)
        return;

    // Prepare chart data
    const chartData = props.equipment.recent_running_times.map((item) => ({
        x: new Date(item.date).getTime(),
        counterReading: parseFloat(item.counter_reading) || 0,
        runningHours: parseFloat(item.running_hours) || 0,
    }));

    // Destroy existing chart
    if (chart.value) {
        chart.value.destroy();
    }

    // Create new chart
    chart.value = Highcharts.chart(chartContainer.value, {
        chart: {
            type: 'line',
            height: 400,
        },
        title: {
            text: 'Running Time Analysis',
        },
        subtitle: {
            text: `${formatDate(dateRange.value.start)} - ${formatDate(dateRange.value.end)}`,
        },
        xAxis: {
            type: 'datetime',
            title: {
                text: 'Date',
            },
        },
        yAxis: [
            {
                title: {
                    text: 'Counter Reading',
                    style: {
                        color: Highcharts.getOptions().colors[0],
                    },
                },
                labels: {
                    style: {
                        color: Highcharts.getOptions().colors[0],
                    },
                },
            },
            {
                title: {
                    text: 'Running Hours',
                    style: {
                        color: Highcharts.getOptions().colors[1],
                    },
                },
                labels: {
                    style: {
                        color: Highcharts.getOptions().colors[1],
                    },
                },
                opposite: true,
            },
        ],
        tooltip: {
            shared: true,
            crosshairs: true,
            formatter: function () {
                let tooltip = `<b>${Highcharts.dateFormat('%Y-%m-%d', this.x)}</b><br/>`;
                this.points.forEach((point) => {
                    tooltip += `${point.series.name}: <b>${formatNumber(point.y)}</b><br/>`;
                });
                return tooltip;
            },
        },
        legend: {
            layout: 'vertical',
            align: 'left',
            x: 80,
            verticalAlign: 'top',
            y: 55,
            floating: true,
            backgroundColor:
                Highcharts.defaultOptions.legend.backgroundColor ||
                'rgba(255,255,255,0.25)',
        },
        series: [
            {
                name: 'Counter Reading',
                type: 'line',
                yAxis: 0,
                data: chartData.map((item) => [item.x, item.counterReading]),
                color: Highcharts.getOptions().colors[0],
                marker: {
                    enabled: true,
                    radius: 4,
                },
            },
            {
                name: 'Running Hours',
                type: 'line',
                yAxis: 1,
                data: chartData.map((item) => [item.x, item.runningHours]),
                color: Highcharts.getOptions().colors[1],
                marker: {
                    enabled: true,
                    radius: 4,
                },
            },
        ],
        responsive: {
            rules: [
                {
                    condition: {
                        maxWidth: 500,
                    },
                    chartOptions: {
                        legend: {
                            floating: false,
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom',
                            x: 0,
                            y: 0,
                        },
                    },
                },
            ],
        },
    });
};

onMounted(() => {
    createChart();
});

watch(
    () => props.equipment.recent_running_times,
    () => {
        createChart();
    },
    { deep: true },
);
</script>
