<template>
    <Head title="Equipment Detail" />

    <!-- Guest view: render without App shell -->
    <div v-if="props.isGuest" class="p-4">
        <EquipmentContent
            :equipment="equipment"
            :loading="loading"
            :not-found="notFound"
            :uuid="props.uuid"
            :equipment-number="equipment.equipment_number"
            :date-range="dateRange"
            :range-value="rangeValue"
            :popover-open="popoverOpen"
            :show-back-button="false"
            @open-qr="isQrOpen = true"
            @go-back="goBack"
            @update:range-value="rangeValue = $event"
            @update:popover-open="popoverOpen = $event"
        />
        <QrShare
            :open="isQrOpen"
            :qrcode="qrcode"
            :description="equipment.equipment_description"
            @update:open="(v) => (isQrOpen = v)"
            @print="printQr"
        />
    </div>

    <!-- Authenticated view: keep App shell -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <EquipmentContent
            :equipment="equipment"
            :loading="loading"
            :not-found="notFound"
            :uuid="props.uuid"
            :equipment-number="equipment.equipment_number"
            :date-range="dateRange"
            :range-value="rangeValue"
            :popover-open="popoverOpen"
            :show-back-button="true"
            @open-qr="isQrOpen = true"
            @go-back="goBack"
            @update:range-value="rangeValue = $event"
            @update:popover-open="popoverOpen = $event"
        />
        <QrShare
            :open="isQrOpen"
            :qrcode="qrcode"
            :description="equipment.equipment_description"
            @update:open="(v) => (isQrOpen = v)"
            @print="printQr"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { Head, router } from '@inertiajs/vue3';
import { parseDate } from '@internationalized/date';
import { useQRCode } from '@vueuse/integrations/useQRCode';
import axios from 'axios';
import Highcharts from 'highcharts';
import { computed, onMounted, ref, watch } from 'vue';
import EquipmentContent from './components/EquipmentContent.vue';
import QrShare from './components/QrShare.vue';

const props = defineProps({
    uuid: {
        type: String,
        required: true,
    },
    isGuest: {
        type: Boolean,
        default: false,
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

const loading = ref(false);
const notFound = ref(false);

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
        `<!doctype html><html><head><title>QR Code</title><style>body{margin:0} .container{display:flex;align-items:center;justify-content:center;height:100vh;padding:16px;} .content{display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center} img{width:320px;height:320px} p{margin:0;white-space:pre-line;color:#000}@media print{img{width:320px;height:320px}}</style></head><body><div class="container"><div class="content"><img src="${qrSrc}" alt="QR Code" /><p>${equipment.value?.equipment_description || ''}</p></div></div><script>window.onload=()=>{window.focus();window.print();window.close();};<\/script></body></html>`,
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
        title: equipment.value.equipment_number || props.uuid,
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
    loading.value = true;
    notFound.value = false;
    try {
        const { data } = await axios.get(
            `/api/equipment/${props.uuid}?${params}`,
        );
        equipment.value = data.equipment;
    } catch (err) {
        if (err?.response?.status === 404) {
            notFound.value = true;
            equipment.value = {
                equipment_number: '',
                equipment_description: '',
                company_code: '',
                object_number: '',
                point: '',
                plant: null,
                station: null,
                cumulative_running_hours: 0,
                recent_running_times: [],
            };
        }
        // otherwise leave as loading false and let UI handle gracefully
    } finally {
        loading.value = false;
    }
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
