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
import { workOrderColumns } from './columns';

const props = defineProps({
    plantId: { type: [String, Number], required: true },
    dateRange: { type: Object, required: true },
    maxHeight: { type: String, default: '420px' },
});

const rows = ref([]);
const loading = ref(false);
const maxHeightStyle = computed(() => props.maxHeight || '420px');

const renderOrNA = (value) => {
    const s = value ?? '';
    const lowered = String(s).toLowerCase();
    if (!s || lowered === '-' || lowered === 'no data' || lowered === 'n/a')
        return 'N/A';
    return String(value);
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (props.plantId) params.append('plant_id', String(props.plantId));
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
        const { data } = await axios.get(`/api/workorders?${params}`);
        rows.value = data?.data || [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
watch(
    () => [props.plantId, props.dateRange?.start, props.dateRange?.end],
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
                            v-for="col in workOrderColumns"
                            :key="col.key"
                        >
                            {{ col.label }}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="wo in rows" :key="wo.id">
                        <TableCell class="font-mono text-sm">
                            {{
                                renderOrNA(
                                    new Date(wo.created_on).toLocaleDateString(
                                        'en-US',
                                        {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                        },
                                    ),
                                )
                            }}
                        </TableCell>
                        <TableCell class="font-mono text-sm">{{
                            renderOrNA(wo.order)
                        }}</TableCell>
                        <TableCell>{{
                            renderOrNA(wo.order_type_label)
                        }}</TableCell>
                        <TableCell>{{
                            renderOrNA(wo.order_status_label)
                        }}</TableCell>
                        <TableCell
                            class="max-w-[320px] truncate"
                            :title="wo.description"
                        >
                            {{ renderOrNA(wo.description) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ScrollArea>
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No work orders found</p>
    </div>
</template>
