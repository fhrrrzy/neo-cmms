<script setup>
import Highcharts from 'highcharts';
import HighchartsBoost from 'highcharts/modules/boost';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Initialize Boost module
typeof HighchartsBoost === 'function' && HighchartsBoost(Highcharts);

const props = defineProps({
    data: { type: Array, default: () => [] },
    subtitle: { type: String, default: '' },
});

const container = ref(null);
let chart = null;

// Dark mode detection (supports class and media query)
const isDarkMode = () => {
    if (
        typeof document !== 'undefined' &&
        document.documentElement.classList.contains('dark')
    ) {
        return true;
    }
    if (typeof window !== 'undefined' && window.matchMedia) {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    return false;
};

const getTheme = () => {
    const dark = isDarkMode();
    return dark
        ? {
              background: 'transparent',
              text: '#e5e7eb',
              mutedText: '#9ca3af',
              grid: '#374151',
              series: Highcharts.getOptions().colors || [
                  '#60a5fa',
                  '#f59e0b',
                  '#34d399',
                  '#f472b6',
              ],
          }
        : {
              background: 'transparent',
              text: '#111827',
              mutedText: '#6b7280',
              grid: '#e5e7eb',
              series: Highcharts.getOptions().colors || [
                  '#2563eb',
                  '#f59e0b',
                  '#10b981',
                  '#ec4899',
              ],
          };
};

const createChart = () => {
    if (!container.value || !props.data?.length) return;

    const chartData = props.data.map((item) => ({
        x: new Date(item.date).getTime(),
        counterReading: parseFloat(item.counter_reading) || 0,
        runningHours: parseFloat(item.running_hours) || 0,
    }));

    if (chart) chart.destroy();

    const theme = getTheme();
    chart = Highcharts.chart(container.value, {
        chart: { type: 'line', height: 400, backgroundColor: 'transparent' },
        boost: {
            useGPUTranslations: true,
            enabled: true,
            // Boost when there are more than 1000 points
            seriesThreshold: 10,
        },
        title: { text: 'Running Time Analysis', style: { color: theme.text } },
        subtitle: { text: props.subtitle, style: { color: theme.mutedText } },
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
                    text: 'Counter Reading',
                    style: { color: theme.series[0] },
                },
                labels: { style: { color: theme.series[0] } },
                gridLineColor: theme.grid,
            },
            {
                title: {
                    text: 'Running Hours',
                    style: { color: theme.series[1] },
                },
                labels: { style: { color: theme.series[1] } },
                opposite: true,
                gridLineColor: theme.grid,
            },
        ],
        tooltip: {
            shared: true,
            crosshairs: true,
            backgroundColor: isDarkMode() ? '#111827' : '#ffffff',
            borderColor: theme.grid,
            style: { color: theme.text },
        },
        legend: {
            layout: 'vertical',
            align: 'left',
            x: 80,
            verticalAlign: 'top',
            y: 55,
            floating: true,
            backgroundColor: 'transparent',
            itemStyle: { color: theme.text },
        },
        series: [
            {
                name: 'Counter Reading',
                type: 'line',
                yAxis: 0,
                data: chartData.map((i) => [i.x, i.counterReading]),
                color: theme.series[0],
                marker: { enabled: true, radius: 4 },
                boostThreshold: 500, // Boost when more than 500 points
                opacity: 0.5,
                states: {
                    hover: {
                        opacity: 1,
                    },
                },
            },
            {
                name: 'Running Hours',
                type: 'line',
                yAxis: 1,
                data: chartData.map((i) => [i.x, i.runningHours]),
                color: theme.series[1],
                marker: { enabled: true, radius: 4 },
                boostThreshold: 500, // Boost when more than 500 points
                opacity: 0.5,
                states: {
                    hover: {
                        opacity: 1,
                    },
                },
            },
        ],
        responsive: {
            rules: [
                {
                    condition: { maxWidth: 500 },
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
        credits: { enabled: false },
        accessibility: { enabled: false },
    });
};

onMounted(createChart);
onBeforeUnmount(() => {
    if (chart) chart.destroy();
});

watch(
    () => props.data,
    () => createChart(),
    { deep: true },
);

// Recreate chart on color scheme changes
if (typeof window !== 'undefined' && window.matchMedia) {
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    mq.addEventListener?.('change', () => createChart());
}
</script>

<template>
    <div ref="container" class="w-full"></div>
    <div
        v-if="!props.data?.length"
        class="py-8 text-center text-muted-foreground"
    >
        <p>No data</p>
    </div>
</template>
