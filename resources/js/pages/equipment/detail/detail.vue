<template>
    <Head title="Equipment Detail" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ equipment.equipment_description || 'N/A' }}
                        </h1>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            @click="isQrOpen = true"
                            aria-label="Open QR code"
                        >
                            <QrCode class="h-4 w-4" />
                        </Button>
                    </div>
                    <p class="text-muted-foreground">
                        #{{ equipment.equipment_number }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ regionalName }} - {{ plantName }} - {{ stationName }}
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
                                v-model="rangeValue"
                                :number-of-months="2"
                            />
                        </PopoverContent>
                    </Popover>
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Monitoring
                    </Button>
                </div>
            </div>

            <Tabs default-value="running">
                <TabsList class="grid w-fit grid-cols-3">
                    <TabsTrigger value="running">Running Time</TabsTrigger>
                    <TabsTrigger value="workorders">Work Orders</TabsTrigger>
                    <TabsTrigger value="material">Material</TabsTrigger>
                </TabsList>

                <TabsContent value="running" class="space-y-6 pt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5" />
                                Running Time Analysis
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RunningTimeChart
                                :data="equipment.recent_running_times"
                                :subtitle="`${formatDate(dateRange.start)} - ${formatDate(dateRange.end)}`"
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" />
                                Running Times Data
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RunningTimeTable
                                :equipment-number="props.equipmentNumber"
                                :date-range="dateRange"
                            />
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="workorders" class="pt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Work Orders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-muted-foreground">
                                Coming soon...
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="material" class="pt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Material</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-muted-foreground">
                                Coming soon...
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
        <QrShare
            :open="isQrOpen"
            :qrcode="qrcode"
            @update:open="(v) => (isQrOpen = v)"
            @print="printQr"
        />
    </AppLayout>
</template>

<script setup>
import RunningTimeTable from '@/components/tables/running-time/RunningTimeTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { Head, router } from '@inertiajs/vue3';
import { parseDate } from '@internationalized/date';
import { useQRCode } from '@vueuse/integrations/useQRCode';
import axios from 'axios';
import Highcharts from 'highcharts';
import { ArrowLeft, BarChart3, Calendar, Clock, QrCode } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import QrShare from './components/QrShare.vue';
import RunningTimeChart from './components/RunningTimeChart.vue';

const props = defineProps({
    equipmentNumber: {
        type: String,
        required: true,
    },
});

const equipment = ref({
    equipment_number: '',
    equipment_description: '',
    company_code: '',
    object_number: '',
    point: '',
    plant: null,
    station: null,
    cumulative_running_hours: 0,
    recent_running_times: [],
});

const chartContainer = ref(null);
const chart = ref(null);

// QR Code state
const isQrOpen = ref(false);
const currentUrl = ref(
    typeof window !== 'undefined' ? window.location.href : '',
);
const qrcode = useQRCode(currentUrl, {
    errorCorrectionLevel: 'H',
    margin: 3,
});

const printQr = () => {
    const qrSrc = qrcode?.value;
    if (!qrSrc) return;
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    printWindow.document.write(
        `<!doctype html><html><head><title>QR Code</title><style>body{margin:0} .container{display:flex;align-items:center;justify-content:center;height:100vh;padding:16px;} img{width:320px;height:320px}@media print{img{width:320px;height:320px}}</style></head><body><div class="container"><img src="${qrSrc}" alt="QR Code" /></div><script>window.onload=()=>{window.focus();window.print();window.close();};<\/script></body></html>`,
    );
    printWindow.document.close();
};

const breadcrumbs = computed(() => [
    {
        title: 'Monitoring',
        href: '/monitoring',
    },
    {
        title: 'Equipment',
        href: '/equipment',
    },
    {
        title: equipment.value.equipment_number || props.equipmentNumber,
        href: '#',
    },
]);

const dateRangeStore = useDateRangeStore();
const defaultStart = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
    .toISOString()
    .split('T')[0];
const defaultEnd = new Date().toISOString().split('T')[0];
const initialStart = dateRangeStore.start || defaultStart;
const initialEnd = dateRangeStore.end || defaultEnd;
const dateRange = ref({
    start: initialStart,
    end: initialEnd,
});
const popoverOpen = ref(false);
const rangeValue = ref({
    start: dateRange.value.start ? parseDate(dateRange.value.start) : undefined,
    end: dateRange.value.end ? parseDate(dateRange.value.end) : undefined,
});

const isRangeEmpty = computed(
    () => !rangeValue.value.start && !rangeValue.value.end,
);
const rangeDisplay = computed(() => {
    if (!rangeValue.value.start && !rangeValue.value.end)
        return 'Select date range';
    if (rangeValue.value.start && rangeValue.value.end)
        return `${rangeValue.value.start.toString()} - ${rangeValue.value.end.toString()}`;
    if (rangeValue.value.start) return rangeValue.value.start.toString();
    return 'Select date range';
});

// Location display helpers
const regionalName = computed(
    () => equipment.value?.plant?.regional?.name || 'N/A',
);
const plantName = computed(() => equipment.value?.plant?.name || 'N/A');
const stationName = computed(
    () => equipment.value?.station?.description || 'N/A',
);

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
    if (!value || value === 0) return 'N/A';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

watch(
    rangeValue,
    (val) => {
        const startStr = val?.start?.toString?.();
        const endStr = val?.end?.toString?.();
        if (startStr && endStr) {
            dateRange.value = { start: startStr, end: endStr };
            dateRangeStore.setRange({ start: startStr, end: endStr });
            popoverOpen.value = false;
            fetchEquipmentDetail();
        }
    },
    { deep: true },
);

const fetchEquipmentDetail = async () => {
    const params = new URLSearchParams();
    if (dateRange.value.start)
        params.append('date_start', dateRange.value.start);
    if (dateRange.value.end) params.append('date_end', dateRange.value.end);
    const { data } = await axios.get(
        `/api/equipment/${props.equipmentNumber}?${params}`,
    );
    equipment.value = data.equipment;
};

const createChart = () => {
    if (!chartContainer.value || !equipment.value.recent_running_times?.length)
        return;

    // Prepare chart data
    const chartData = equipment.value.recent_running_times.map((item) => ({
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

onMounted(async () => {
    await fetchEquipmentDetail();
    createChart();
});

watch(
    () => equipment.value.recent_running_times,
    () => {
        createChart();
    },
    { deep: true },
);
</script>
