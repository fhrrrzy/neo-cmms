<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ equipment.equipment_description || 'N/A' }}
                    </h1>
                    <Button
                        v-if="showQrButton"
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                        @click="$emit('openQr')"
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

                <!-- Equipment Details Grid -->
                <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-muted-foreground">
                            Year
                        </p>
                        <p class="text-sm font-semibold">
                            {{
                                equipment.baujj && equipment.baujj !== '-'
                                    ? equipment.baujj
                                    : 'N/A'
                            }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-muted-foreground">
                            Capacity
                        </p>
                        <p class="text-sm font-semibold">
                            {{
                                equipment.groes && equipment.groes !== '-'
                                    ? equipment.groes
                                    : 'N/A'
                            }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-muted-foreground">
                            Manufacturer
                        </p>
                        <p class="text-sm font-semibold">
                            {{
                                equipment.herst && equipment.herst !== '-'
                                    ? equipment.herst
                                    : 'N/A'
                            }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-medium text-muted-foreground">
                            Equipment Type
                        </p>
                        <p class="text-sm font-semibold">
                            {{
                                equipment.eqart && equipment.eqart !== '-'
                                    ? equipment.eqart
                                    : 'N/A'
                            }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-medium text-muted-foreground">
                            Functional Location
                        </p>
                        <p class="text-sm font-semibold">
                            {{
                                equipment.description_func_location &&
                                equipment.description_func_location !== '-'
                                    ? equipment.description_func_location
                                    : 'N/A'
                            }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 md:flex-nowrap">
                <Popover
                    :open="popoverOpen"
                    class="w-full md:w-auto"
                    @update:open="$emit('update:popoverOpen', $event)"
                >
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            :class="[
                                'w-full justify-start text-left font-normal md:w-[280px]',
                                isRangeEmpty ? 'text-muted-foreground' : '',
                            ]"
                        >
                            <Calendar class="mr-2 h-4 w-4" />
                            {{ rangeDisplay }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-auto p-0">
                        <RangeCalendar
                            :model-value="rangeValue"
                            @update:model-value="
                                $emit('update:rangeValue', $event)
                            "
                            :number-of-months="2"
                        />
                    </PopoverContent>
                </Popover>
                <Button
                    v-if="showBackButton"
                    variant="outline"
                    class="w-full md:w-auto"
                    @click="$emit('goBack')"
                >
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Monitoring
                </Button>
            </div>
        </div>

        <!-- Tabs Content -->
        <Tabs default-value="running" v-model="activeTab">
            <TabsList class="grid w-full grid-cols-3 md:w-fit">
                <TabsTrigger value="running">Running Time</TabsTrigger>
                <TabsTrigger value="workorders">Work Orders</TabsTrigger>
                <TabsTrigger value="material">Material</TabsTrigger>
            </TabsList>

            <div class="relative overflow-hidden">
                <Transition
                    :name="getTransitionName()"
                    mode="out-in"
                    enter-active-class="transition-all duration-150 ease-out"
                    leave-active-class="transition-all duration-100 ease-in"
                >
                    <TabsContent
                        v-if="activeTab === 'running'"
                        key="running"
                        value="running"
                        class="space-y-6 pt-4"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <BarChart3 class="h-5 w-5" />
                                    Running Time Analysis
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div
                                    v-if="isLoadingRunningTime"
                                    class="space-y-4"
                                >
                                    <Skeleton class="h-[300px] w-full" />
                                    <div class="flex gap-4">
                                        <Skeleton class="h-4 w-20" />
                                        <Skeleton class="h-4 w-16" />
                                    </div>
                                </div>
                                <RunningTimeChart
                                    v-else
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
                                <div
                                    v-if="isLoadingRunningTime"
                                    class="space-y-3"
                                >
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                </div>
                                <RunningTimeTable
                                    v-else
                                    :equipment-number="equipmentNumber"
                                    :date-range="dateRange"
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent
                        v-else-if="activeTab === 'workorders'"
                        key="workorders"
                        value="workorders"
                        class="pt-4"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>Work Orders</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div
                                    v-if="isLoadingWorkOrders"
                                    class="space-y-3"
                                >
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                </div>
                                <WorkOrderTable
                                    v-else
                                    :equipment-number="equipmentNumber"
                                    :date-range="dateRange"
                                    max-height-class="max-h-[60vh]"
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent
                        v-else-if="activeTab === 'material'"
                        key="material"
                        value="material"
                        class="pt-4"
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>Material</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="isLoadingMaterial" class="space-y-3">
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                    <Skeleton class="h-10 w-full" />
                                </div>
                                <EquipmentWorkOrderTable
                                    v-else
                                    :equipment-number="equipmentNumber"
                                    :date-range="dateRange"
                                    max-height-class="max-h-[60vh]"
                                />
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Transition>
            </div>
        </Tabs>
    </div>
</template>

<script setup>
import EquipmentWorkOrderTable from '@/components/tables/equipment-work-order/EquipmentWorkOrderTable.vue';
import RunningTimeTable from '@/components/tables/running-time/RunningTimeTable.vue';
import WorkOrderTable from '@/components/tables/work-order/WorkOrderTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, BarChart3, Calendar, Clock, QrCode } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import RunningTimeChart from './RunningTimeChart.vue';

const props = defineProps({
    equipment: {
        type: Object,
        required: true,
    },
    equipmentNumber: {
        type: String,
        required: true,
    },
    dateRange: {
        type: Object,
        required: true,
    },
    rangeValue: {
        type: Object,
        required: true,
    },
    popoverOpen: {
        type: Boolean,
        required: true,
    },
    showBackButton: {
        type: Boolean,
        default: false,
    },
    showQrButton: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits([
    'openQr',
    'goBack',
    'update:rangeValue',
    'update:popoverOpen',
]);

// Tab animation state
const activeTab = ref('running');
const previousTab = ref('running');

// Loading states
const isLoadingRunningTime = ref(false);
const isLoadingWorkOrders = ref(false);
const isLoadingMaterial = ref(false);

// Computed properties
const isRangeEmpty = computed(
    () => !props.rangeValue.start && !props.rangeValue.end,
);

const rangeDisplay = computed(() => {
    if (!props.rangeValue.start && !props.rangeValue.end)
        return 'Select date range';
    if (props.rangeValue.start && props.rangeValue.end)
        return `${props.rangeValue.start.toString()} - ${props.rangeValue.end.toString()}`;
    if (props.rangeValue.start) return props.rangeValue.start.toString();
    return 'Select date range';
});

// Location display helpers
const regionalName = computed(
    () => props.equipment?.plant?.regional?.name || 'N/A',
);
const plantName = computed(() => props.equipment?.plant?.name || 'N/A');
const stationName = computed(
    () => props.equipment?.station?.description || 'N/A',
);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Tab transition helper
const getTransitionName = () => {
    const tabOrder = ['running', 'workorders', 'material'];
    const currentIndex = tabOrder.indexOf(activeTab.value);
    const previousIndex = tabOrder.indexOf(previousTab.value);

    if (currentIndex > previousIndex) {
        return 'slide-left';
    } else if (currentIndex < previousIndex) {
        return 'slide-right';
    }
    return 'slide-fade';
};

// Watch for tab changes
watch(activeTab, (newTab, oldTab) => {
    previousTab.value = oldTab;

    // Set loading state for the new tab
    if (newTab === 'running') {
        isLoadingRunningTime.value = true;
        // Simulate API call delay - in real app, this would be actual API calls
        setTimeout(() => {
            isLoadingRunningTime.value = false;
        }, 200);
    } else if (newTab === 'workorders') {
        isLoadingWorkOrders.value = true;
        setTimeout(() => {
            isLoadingWorkOrders.value = false;
        }, 200);
    } else if (newTab === 'material') {
        isLoadingMaterial.value = true;
        setTimeout(() => {
            isLoadingMaterial.value = false;
        }, 200);
    }
});
</script>

<style scoped>
/* Slide animations for tab transitions */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active,
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.15s ease-out;
}

.slide-left-leave-active,
.slide-right-leave-active,
.slide-fade-leave-active {
    transition: all 0.1s ease-in;
}

/* Slide left (moving to next tab) */
.slide-left-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-100%);
}

/* Slide right (moving to previous tab) */
.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-100%);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

/* Slide fade (same tab or initial load) */
.slide-fade-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* Ensure smooth transitions */
.slide-left-enter-to,
.slide-left-leave-from,
.slide-right-enter-to,
.slide-right-leave-from,
.slide-fade-enter-to,
.slide-fade-leave-from {
    opacity: 1;
    transform: translateX(0) translateY(0);
}
</style>
