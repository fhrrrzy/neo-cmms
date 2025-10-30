<script setup lang="js">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import EquipmentDetailSheet from '@/pages/monitoring/components/EquipmentDetailSheet.vue';
import axios from 'axios';
import { Factory } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    plantUuid: {
        type: String,
        default: null,
    },
    date: {
        type: String,
        default: '',
    },
    plantName: {
        type: String,
        default: '',
    },
});

const loading = ref(false);
const error = ref(null);
const isMengolah = ref(true);
const withRunningTime = ref([]);
const withoutRunningTime = ref([]);
const activeTab = ref('with-runtime');

// Equipment Detail Sheet state
const isEquipmentSheetOpen = ref(false);
const selectedEquipmentNumber = ref('');

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const fetchEquipmentDetail = async () => {
    if (!props.plantUuid || !props.date) return;

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/monitoring/jam-jalan-detail', {
            params: {
                plant_uuid: props.plantUuid,
                date: props.date,
            },
        });

        isMengolah.value = response.data.is_mengolah ?? true;
        withRunningTime.value = response.data.with_running_time || [];
        withoutRunningTime.value = response.data.without_running_time || [];
    } catch (err) {
        error.value =
            err.response?.data?.message || 'Terjadi kesalahan saat memuat data';
        console.error('Error fetching equipment detail:', err);
    } finally {
        loading.value = false;
    }
};

const openEquipmentDetail = (equipmentNumber) => {
    selectedEquipmentNumber.value = equipmentNumber;
    isEquipmentSheetOpen.value = true;
};

const closeEquipmentSheet = () => {
    isEquipmentSheetOpen.value = false;
    selectedEquipmentNumber.value = '';
};

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen && props.plantUuid && props.date) {
            fetchEquipmentDetail();
        }
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => $emit('close')">
        <DialogContent
            class="max-h-[90vh] w-[95vw] max-w-[95vw] md:w-[80vw] md:max-w-[80vw]"
        >
            <DialogHeader>
                <DialogTitle class="text-base md:text-lg">
                    Equipment Details - {{ plantName }}
                </DialogTitle>
                <DialogDescription class="text-xs md:text-sm">
                    {{ formatDate(date) }}
                    <span
                        :class="isMengolah ? 'text-green-600' : 'text-red-600'"
                    >
                        • {{ isMengolah ? 'Mengolah' : 'Tidak Mengolah' }}
                    </span>
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="loading"
                class="flex items-center justify-center p-4 md:p-8"
            >
                <div class="text-sm text-muted-foreground md:text-base">
                    Loading...
                </div>
            </div>

            <div
                v-else-if="error"
                class="flex items-center justify-center p-4 md:p-8"
            >
                <div class="text-sm text-destructive md:text-base">
                    {{ error }}
                </div>
            </div>

            <Tabs
                v-model="activeTab"
                v-else
                class="flex max-h-[calc(90vh-120px)] w-full flex-col md:max-h-[calc(90vh-140px)]"
            >
                <TabsList class="grid w-full grid-cols-2 text-xs md:text-sm">
                    <TabsTrigger value="with-runtime" class="truncate">
                        With Running Time ({{ withRunningTime.length }})
                    </TabsTrigger>
                    <TabsTrigger value="without-runtime" class="truncate">
                        Without Running Time ({{ withoutRunningTime.length }})
                    </TabsTrigger>
                </TabsList>

                <div class="relative mt-2 flex-1 overflow-hidden md:mt-4">
                    <Transition name="slide-x" mode="out-in">
                        <div :key="activeTab">
                            <div
                                v-if="activeTab === 'with-runtime'"
                                class="max-h-[calc(90vh-200px)] overflow-x-auto overflow-y-auto rounded-md border md:max-h-[calc(90vh-220px)]"
                            >
                                <Table class="min-w-full">
                                    <TableHeader>
                                        <TableRow class="text-xs md:text-sm">
                                            <TableHead
                                                class="w-[40px] md:w-[100px]"
                                                >No</TableHead
                                            >
                                            <TableHead
                                                class="min-w-[120px] md:min-w-[180px]"
                                                >Equipment Number
                                            </TableHead>
                                            <TableHead
                                                class="min-w-[150px] md:min-w-[200px]"
                                                >Description</TableHead
                                            >
                                            <TableHead
                                                class="min-w-[100px] text-right md:min-w-[130px]"
                                                >Running Hours
                                            </TableHead>
                                            <TableHead
                                                class="min-w-[100px] text-right md:min-w-[130px]"
                                                >Counter Reading
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-if="withRunningTime.length === 0"
                                        >
                                            <TableCell :colspan="5" class="p-8">
                                                <Empty>
                                                    <EmptyHeader>
                                                        <EmptyMedia
                                                            variant="icon"
                                                        >
                                                            <Factory />
                                                        </EmptyMedia>
                                                        <EmptyTitle
                                                            >No
                                                            Equipment</EmptyTitle
                                                        >
                                                        <EmptyDescription>
                                                            No equipment found
                                                            with running time
                                                        </EmptyDescription>
                                                    </EmptyHeader>
                                                </Empty>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow
                                            v-for="(
                                                equipment, index
                                            ) in withRunningTime"
                                            :key="equipment.equipment_number"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="
                                                openEquipmentDetail(
                                                    equipment.uuid,
                                                )
                                            "
                                        >
                                            <TableCell class="text-center">{{
                                                index + 1
                                            }}</TableCell>
                                            <TableCell
                                                class="font-mono text-xs md:text-sm"
                                                >{{
                                                    equipment.equipment_number
                                                }}</TableCell
                                            >
                                            <TableCell
                                                class="text-xs md:text-sm"
                                                >{{
                                                    equipment.equipment_description ||
                                                    'N/A'
                                                }}</TableCell
                                            >
                                            <TableCell
                                                class="text-right font-mono text-xs md:text-sm"
                                                >{{
                                                    formatNumber(
                                                        equipment.running_hours,
                                                    )
                                                }}</TableCell
                                            >
                                            <TableCell
                                                class="text-right font-mono text-xs md:text-sm"
                                                >{{
                                                    formatNumber(
                                                        equipment.counter_reading ||
                                                            0,
                                                    )
                                                }}</TableCell
                                            >
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <div
                                v-else
                                class="max-h-[calc(90vh-200px)] overflow-x-auto overflow-y-auto rounded-md border md:max-h-[calc(90vh-220px)]"
                            >
                                <Table class="min-w-full">
                                    <TableHeader>
                                        <TableRow class="text-xs md:text-sm">
                                            <TableHead
                                                class="w-[40px] md:w-[100px]"
                                                >No</TableHead
                                            >
                                            <TableHead
                                                class="min-w-[120px] md:min-w-[180px]"
                                                >Equipment Number
                                            </TableHead>
                                            <TableHead
                                                class="min-w-[150px] md:min-w-[200px]"
                                                >Description</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-if="
                                                withoutRunningTime.length === 0
                                            "
                                        >
                                            <TableCell :colspan="3" class="p-8">
                                                <Empty>
                                                    <EmptyHeader>
                                                        <EmptyMedia
                                                            variant="icon"
                                                        >
                                                            <Factory />
                                                        </EmptyMedia>
                                                        <EmptyTitle
                                                            >All Have
                                                            Data</EmptyTitle
                                                        >
                                                        <EmptyDescription>
                                                            All equipment have
                                                            running time data
                                                        </EmptyDescription>
                                                    </EmptyHeader>
                                                </Empty>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow
                                            v-for="(
                                                equipment, index
                                            ) in withoutRunningTime"
                                            :key="equipment.equipment_number"
                                            class="cursor-pointer hover:bg-muted/50"
                                            @click="
                                                openEquipmentDetail(
                                                    equipment.uuid,
                                                )
                                            "
                                        >
                                            <TableCell class="text-center">{{
                                                index + 1
                                            }}</TableCell>
                                            <TableCell
                                                class="font-mono text-xs md:text-sm"
                                                >{{
                                                    equipment.equipment_number
                                                }}</TableCell
                                            >
                                            <TableCell
                                                class="text-xs md:text-sm"
                                                >{{
                                                    equipment.equipment_description ||
                                                    'N/A'
                                                }}</TableCell
                                            >
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Tabs>
        </DialogContent>
    </Dialog>

    <!-- Equipment Detail Sheet -->
    <EquipmentDetailSheet
        :is-open="isEquipmentSheetOpen"
        :equipment-number="selectedEquipmentNumber"
        @close="closeEquipmentSheet"
    />
</template>

<style>
.slide-x-enter-active,
.slide-x-leave-active {
    transition:
        transform 200ms ease,
        opacity 200ms ease;
}

.slide-x-enter-from {
    transform: translateX(12px);
    opacity: 0.01;
}

.slide-x-leave-to {
    transform: translateX(-12px);
    opacity: 0.01;
}
</style>
