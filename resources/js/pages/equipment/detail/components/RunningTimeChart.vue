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
let themeChangeHandler = null;
let resizeObserver = null;

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

// Debounce helper for resize/theme changes
const debounce = (func, wait) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
};

// Memoize chart data transformation
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

    const dataLength = chartData.value.length;
    const enableMarkers = dataLength <= 100; // Reduced threshold for better performance
    const useBoost = dataLength > 250; // Enable boost for datasets larger than 250 points

    if (chart) {
        chart.destroy();
        chart = null;
    }

    const theme = getTheme();

    // Use requestAnimationFrame for smoother rendering
    requestAnimationFrame(() => {
        chart = Highcharts.chart(container.value, {
            chart: {
                type: 'line',
                height: 400,
                backgroundColor: 'transparent',
                animation: false,
                // Prevent reflow during rendering
                reflow: false,
            },
            boost: {
                useGPUTranslations: true,
                usePreallocated: true,
                enabled: useBoost,
                // Lower threshold for better performance
                seriesThreshold: 1,
            },
            title: {
                text: 'Running Time Analysis',
                style: { color: theme.text },
            },
            subtitle: {
                text: props.subtitle,
                style: { color: theme.mutedText },
            },
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
                        style: { color: theme.series[0] },
                    },
                    labels: { style: { color: theme.text } },
                    gridLineColor: theme.grid,
                },
                {
                    title: {
                        text: 'Counter Reading',
                        style: { color: theme.series[1] },
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
                backgroundColor: isDarkMode() ? '#111827' : '#ffffff',
                borderColor: theme.grid,
                style: { color: theme.text },
                formatter: function () {
                    const date = Highcharts.dateFormat('%e %b %Y', this.x);
                    const rhPoint = (this.points || []).find(
                        (p) => p.series.name === 'Running Hours',
                    );
                    const crPoint = (this.points || []).find(
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
                layout: 'vertical',
                align: 'left',
                x: 80,
                verticalAlign: 'top',
                y: 55,
                floating: true,
                backgroundColor: 'transparent',
                itemStyle: { color: theme.text },
            },
            plotOptions: {
                series: {
                    animation: false,
                    turboThreshold: 0, // Disable turbo threshold
                    boostThreshold: useBoost ? 250 : 0,
                    // Reduce overhead for large datasets
                    stickyTracking: dataLength > 1000,
                },
                line: {
                    animation: false,
                    lineWidth: dataLength > 1000 ? 1 : 2,
                    // Disable shadows for performance
                    shadow: false,
                },
            },
            series: [
                {
                    name: 'Running Hours',
                    type: 'line',
                    yAxis: 0,
                    data: chartData.value.map((i) => [i.x, i.runningHours]),
                    color: theme.series[0],
                    marker: {
                        enabled: enableMarkers,
                        radius: 3,
                        // Disable hover states for large datasets
                        states: {
                            hover: {
                                enabled: dataLength <= 500,
                            },
                        },
                    },
                    boostThreshold: 250,
                    opacity: 0.75,
                    states: {
                        hover: {
                            opacity: 1,
                            lineWidthPlus: dataLength > 1000 ? 0 : 1,
                        },
                        inactive: {
                            opacity: 0.5,
                        },
                    },
                },
                {
                    name: 'Counter Reading',
                    type: 'line',
                    yAxis: 1,
                    data: chartData.value.map((i) => [i.x, i.counterReading]),
                    color: theme.series[1],
                    marker: {
                        enabled: enableMarkers,
                        radius: 3,
                        states: {
                            hover: {
                                enabled: dataLength <= 500,
                            },
                        },
                    },
                    boostThreshold: 250,
                    opacity: 0.75,
                    states: {
                        hover: {
                            opacity: 1,
                            lineWidthPlus: dataLength > 1000 ? 0 : 1,
                        },
                        inactive: {
                            opacity: 0.5,
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

        // Manually trigger reflow after chart is created
        if (chart) {
            chart.reflow();
        }
    });
};

onMounted(() => {
    createChart();

    // Setup debounced theme change handler
    if (typeof window !== 'undefined' && window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        themeChangeHandler = debounce(() => createChart(), 150);
        mq.addEventListener?.('change', themeChangeHandler);
    }

    // Setup ResizeObserver for better resize handling
    if (typeof ResizeObserver !== 'undefined' && container.value) {
        resizeObserver = new ResizeObserver(debounce(() => {
            if (chart) {
                chart.reflow();
            }
        }, 100));
        resizeObserver.observe(container.value);
    }
});

onBeforeUnmount(() => {
    // Clean up chart
    if (chart) {
        chart.destroy();
        chart = null;
    }

    // Clean up theme change listener
    if (themeChangeHandler && typeof window !== 'undefined' && window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        mq.removeEventListener?.('change', themeChangeHandler);
    }

    // Clean up resize observer
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
});

// Watch for data changes with debouncing for large datasets
const debouncedCreateChart = debounce(createChart, 100);

watch(
    () => props.data,
    (newData) => {
        // Use debounced version for large datasets
        if (newData && newData.length > 500) {
            debouncedCreateChart();
        } else {
            createChart();
        }
    },
);
</script>

<template>
    <div v-if="props.data?.length" ref="container" class="w-full" style="will-change: contents; contain: layout;"></div>
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
