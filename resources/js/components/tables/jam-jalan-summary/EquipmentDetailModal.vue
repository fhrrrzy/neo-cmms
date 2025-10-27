<script setup lang="js">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import axios from 'axios';
import { ref, watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    plantId: {
        type: [Number, String, null],
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
    if (!props.plantId || !props.date) return;

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/monitoring/jam-jalan-detail', {
            params: {
                plant_id: props.plantId,
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

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen && props.plantId && props.date) {
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
                        :class="isMengolah ? 'text-blue-600' : 'text-red-600'"
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
                default-value="with-runtime"
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

                <TabsContent
                    value="with-runtime"
                    class="mt-2 flex-1 overflow-hidden md:mt-4"
                >
                    <div
                        class="max-h-[calc(90vh-200px)] overflow-x-auto overflow-y-auto rounded-md border md:max-h-[calc(90vh-220px)]"
                    >
                        <Table class="min-w-full">
                            <TableHeader>
                                <TableRow class="text-xs md:text-sm">
                                    <TableHead class="w-[40px] md:w-[100px]"
                                        >No</TableHead
                                    >
                                    <TableHead
                                        class="min-w-[120px] md:min-w-[180px]"
                                        >Equipment Number</TableHead
                                    >
                                    <TableHead
                                        class="min-w-[150px] md:min-w-[200px]"
                                        >Description</TableHead
                                    >
                                    <TableHead
                                        class="min-w-[100px] text-right md:min-w-[130px]"
                                        >Running Hours</TableHead
                                    >
                                    <TableHead
                                        class="min-w-[100px] text-right md:min-w-[130px]"
                                        >Counter Reading</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="withRunningTime.length === 0">
                                    <TableCell
                                        :colspan="5"
                                        class="text-center text-muted-foreground"
                                    >
                                        No equipment found with running time
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="(
                                        equipment, index
                                    ) in withRunningTime"
                                    :key="equipment.equipment_number"
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
                                    <TableCell class="text-xs md:text-sm">{{
                                        equipment.equipment_description || 'N/A'
                                    }}</TableCell>
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
                                                equipment.counter_reading || 0,
                                            )
                                        }}</TableCell
                                    >
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>

                <TabsContent
                    value="without-runtime"
                    class="mt-2 flex-1 overflow-hidden md:mt-4"
                >
                    <div
                        class="max-h-[calc(90vh-200px)] overflow-x-auto overflow-y-auto rounded-md border md:max-h-[calc(90vh-220px)]"
                    >
                        <Table class="min-w-full">
                            <TableHeader>
                                <TableRow class="text-xs md:text-sm">
                                    <TableHead class="w-[40px] md:w-[100px]"
                                        >No</TableHead
                                    >
                                    <TableHead
                                        class="min-w-[120px] md:min-w-[180px]"
                                        >Equipment Number</TableHead
                                    >
                                    <TableHead
                                        class="min-w-[150px] md:min-w-[200px]"
                                        >Description</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-if="withoutRunningTime.length === 0"
                                >
                                    <TableCell
                                        :colspan="3"
                                        class="text-center text-muted-foreground"
                                    >
                                        All equipment have running time data
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="(
                                        equipment, index
                                    ) in withoutRunningTime"
                                    :key="equipment.equipment_number"
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
                                    <TableCell class="text-xs md:text-sm">{{
                                        equipment.equipment_description || 'N/A'
                                    }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>
