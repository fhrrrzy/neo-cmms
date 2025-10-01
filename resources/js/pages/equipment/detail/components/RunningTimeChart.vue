<script setup>
import Highcharts from 'highcharts';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    subtitle: { type: String, default: '' },
});

const container = ref(null);
let chart = null;

const createChart = () => {
    if (!container.value || !props.data?.length) return;

    const chartData = props.data.map((item) => ({
        x: new Date(item.date).getTime(),
        counterReading: parseFloat(item.counter_reading) || 0,
        runningHours: parseFloat(item.running_hours) || 0,
    }));

    if (chart) chart.destroy();

    chart = Highcharts.chart(container.value, {
        chart: { type: 'line', height: 400 },
        title: { text: 'Running Time Analysis' },
        subtitle: { text: props.subtitle },
        xAxis: { type: 'datetime', title: { text: 'Date' } },
        yAxis: [
            {
                title: {
                    text: 'Counter Reading',
                    style: { color: Highcharts.getOptions().colors[0] },
                },
                labels: { style: { color: Highcharts.getOptions().colors[0] } },
            },
            {
                title: {
                    text: 'Running Hours',
                    style: { color: Highcharts.getOptions().colors[1] },
                },
                labels: { style: { color: Highcharts.getOptions().colors[1] } },
                opposite: true,
            },
        ],
        tooltip: {
            shared: true,
            crosshairs: true,
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
                data: chartData.map((i) => [i.x, i.counterReading]),
                color: Highcharts.getOptions().colors[0],
                marker: { enabled: true, radius: 4 },
            },
            {
                name: 'Running Hours',
                type: 'line',
                yAxis: 1,
                data: chartData.map((i) => [i.x, i.runningHours]),
                color: Highcharts.getOptions().colors[1],
                marker: { enabled: true, radius: 4 },
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
