<script setup>
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import Highcharts from 'highcharts';
import HighchartsBoost from 'highcharts/modules/boost';
import { TrendingUp } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Initialize Boost module
typeof HighchartsBoost === 'function' && HighchartsBoost(Highcharts);

const props = defineProps({
    data: { type: Array, default: () => [] },
    subtitle: { type: String, default: '' },
});

const container = ref(null);
let chart = null;

// Dark mode detection
const isDarkMode = () => {
    return document.documentElement.classList.contains('dark');
};

const getTheme = () => {
    const dark = isDarkMode();
    return dark
        ? {
              text: '#e5e7eb',
              mutedText: '#9ca3af',
              grid: '#374151',
              primary: '#60a5fa',
              secondary: '#f59e0b',
          }
        : {
              text: '#111827',
              mutedText: '#6b7280',
              grid: '#e5e7eb',
              primary: '#2563eb',
              secondary: '#f59e0b',
          };
};

// Transform data
const chartData = computed(() => {
    if (!props.data?.length) return [];
    return props.data.map((item) => ({
        x: new Date(item.date).getTime(),
        counterReading: parseFloat(item.counter_reading) || 0,
        runningHours: parseFloat(item.running_hours) || 0,
    }));
});

const createChart = () => {
    if (!container.value || !chartData.value.length) return;

    if (chart) {
        chart.destroy();
        chart = null;
    }

    const theme = getTheme();
    const dataLength = chartData.value.length;

    chart = Highcharts.chart(container.value, {
        chart: {
            type: 'line',
            height: 400,
            backgroundColor: 'transparent',
            animation: false,
        },
        boost: {
            useGPUTranslations: true,
            enabled: dataLength > 250,
        },
        title: {
            text: null, // or undefined
        },
        // subtitle: {
        //     text: props.subtitle,
        //     style: { color: theme.mutedText, fontSize: '14px' },
        // },
        xAxis: {
            type: 'datetime',
            title: { text: 'Date', style: { color: theme.text } },
            labels: { style: { color: theme.mutedText } },
            lineColor: theme.grid,
            tickColor: theme.grid,
        },
        yAxis: [
            {
                title: {
                    text: 'Running Hours',
                    style: { color: theme.primary },
                },
                labels: { style: { color: theme.text } },
                gridLineColor: theme.grid,
            },
            {
                title: {
                    text: 'Counter Reading',
                    style: { color: theme.secondary },
                },
                labels: { style: { color: theme.text } },
                opposite: true,
                gridLineColor: theme.grid,
            },
        ],
        tooltip: {
            shared: true,
            crosshairs: true,
            animation: false,
            backgroundColor: isDarkMode() ? '#1f2937' : '#ffffff',
            borderColor: theme.grid,
            style: { color: theme.text },
            formatter: function () {
                const date = Highcharts.dateFormat('%e %b %Y', this.x);
                const rhPoint = this.points?.find(
                    (p) => p.series.name === 'Running Hours',
                );
                const crPoint = this.points?.find(
                    (p) => p.series.name === 'Counter Reading',
                );
                const rhVal = rhPoint
                    ? Highcharts.numberFormat(rhPoint.y, 2)
                    : '-';
                const crVal = crPoint
                    ? Highcharts.numberFormat(crPoint.y, 2)
                    : '-';
                return `<b>${date}</b><br/>Running Hours: ${rhVal} Jam<br/>Counter Reading: ${crVal}`;
            },
        },
        legend: {
            enabled: true,
            itemStyle: { color: theme.text },
        },
        plotOptions: {
            series: {
                animation: false,
                turboThreshold: 0,
                states: {
                    hover: { enabled: true },
                    inactive: { enabled: false },
                },
            },
            line: {
                lineWidth: 2,
                shadow: false,
                marker: {
                    enabled: dataLength <= 100,
                    radius: 3,
                },
            },
        },
        series: [
            {
                name: 'Running Hours',
                type: 'line',
                yAxis: 0,
                data: chartData.value.map((i) => [i.x, i.runningHours]),
                color: theme.primary,
            },
            {
                name: 'Counter Reading',
                type: 'line',
                yAxis: 1,
                data: chartData.value.map((i) => [i.x, i.counterReading]),
                color: theme.secondary,
            },
        ],
        responsive: {
            rules: [
                {
                    condition: { maxWidth: 500 },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom',
                        },
                    },
                },
            ],
        },
        credits: { enabled: false },
        accessibility: { enabled: false },
    });
};

onMounted(() => {
    createChart();
});

onBeforeUnmount(() => {
    if (chart) {
        chart.destroy();
        chart = null;
    }
});

// Watch for data changes
watch(
    () => props.data,
    () => {
        createChart();
    },
);
</script>

<template>
    <div v-if="props.data?.length" ref="container" class="w-full"></div>
    <div v-else class="py-8">
        <Empty>
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <TrendingUp />
                </EmptyMedia>
                <EmptyTitle>No Chart Data</EmptyTitle>
                <EmptyDescription>
                    No running time data available to display
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    </div>
</template>
