<script setup>
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { runningTimeColumns } from './columns';

const props = defineProps({
    equipmentNumber: { type: String, required: true },
    dateRange: {
        type: Object,
        required: true,
    },
    maxHeight: { type: String, default: '420px' },
});

const rows = ref([]);
const loading = ref(false);
const maxHeightStyle = computed(() => props.maxHeight || '420px');

const fetchData = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        const { data } = await axios.get(
            `/api/equipment/${props.equipmentNumber}?${params}`,
        );
        rows.value = data?.equipment?.recent_running_times || [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(
    () => [props.equipmentNumber, props.dateRange?.start, props.dateRange?.end],
    fetchData,
);
</script>

<template>
    <div v-if="rows?.length > 0">
        <ScrollArea :style="{ maxHeight: maxHeightStyle }" class="w-full">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="col in runningTimeColumns"
                            :key="col.key"
                            :class="col.align === 'right' ? 'text-right' : ''"
                        >
                            {{ col.label }}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(time, index) in rows" :key="index">
                        <TableCell class="font-medium">
                            {{
                                new Date(time.date).toLocaleDateString(
                                    'en-US',
                                    {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                    },
                                )
                            }}
                        </TableCell>
                        <TableCell class="text-right font-mono">
                            {{
                                Number(
                                    time.counter_reading || 0,
                                ).toLocaleString('id-ID', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })
                            }}
                        </TableCell>
                        <TableCell class="text-right font-mono">
                            {{
                                Number(time.running_hours || 0).toLocaleString(
                                    'id-ID',
                                    {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    },
                                )
                            }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No running times data available</p>
    </div>
</template>
