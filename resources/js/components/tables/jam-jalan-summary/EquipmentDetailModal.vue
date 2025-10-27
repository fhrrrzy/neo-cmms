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
        <DialogContent class="max-h-[85vh] w-[95vw] max-w-[95vw]">
            <DialogHeader>
                <DialogTitle> Equipment Details - {{ plantName }} </DialogTitle>
                <DialogDescription>
                    {{ formatDate(date) }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="loading" class="flex items-center justify-center p-8">
                <div class="text-muted-foreground">Loading...</div>
            </div>

            <div v-else-if="error" class="flex items-center justify-center p-8">
                <div class="text-destructive">{{ error }}</div>
            </div>

            <Tabs default-value="with-runtime" v-else class="w-full">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="with-runtime">
                        With Running Time ({{ withRunningTime.length }})
                    </TabsTrigger>
                    <TabsTrigger value="without-runtime">
                        Without Running Time ({{ withoutRunningTime.length }})
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="with-runtime" class="mt-4">
                    <div class="max-h-[60vh] overflow-y-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[100px]">No</TableHead>
                                    <TableHead>Equipment Number</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="text-right"
                                        >Running Hours</TableHead
                                    >
                                    <TableHead class="text-right"
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
                                    <TableCell class="font-mono">{{
                                        equipment.equipment_number
                                    }}</TableCell>
                                    <TableCell>{{
                                        equipment.equipment_description || 'N/A'
                                    }}</TableCell>
                                    <TableCell class="text-right font-mono">{{
                                        formatNumber(equipment.running_hours)
                                    }}</TableCell>
                                    <TableCell class="text-right font-mono">{{
                                        formatNumber(
                                            equipment.counter_reading || 0,
                                        )
                                    }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </TabsContent>

                <TabsContent value="without-runtime" class="mt-4">
                    <div class="max-h-[60vh] overflow-y-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[100px]">No</TableHead>
                                    <TableHead>Equipment Number</TableHead>
                                    <TableHead>Description</TableHead>
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
                                    <TableCell class="font-mono">{{
                                        equipment.equipment_number
                                    }}</TableCell>
                                    <TableCell>{{
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
