<script setup>
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { workOrderColumns } from './work-order/columns';

const props = defineProps({
    data: { type: Array, default: () => [] },
    formatDate: { type: Function, required: true },
});

const renderOrNA = (value) => {
    const s = value ?? '';
    const lowered = String(s).toLowerCase();
    if (!s || lowered === '-' || lowered === 'no data' || lowered === 'n/a')
        return 'N/A';
    return String(value);
};
</script>

<template>
    <div v-if="props.data?.length > 0">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead v-for="col in workOrderColumns" :key="col.key">
                        {{ col.label }}
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="wo in props.data" :key="wo.id">
                    <TableCell class="font-mono text-sm">
                        {{ renderOrNA(props.formatDate(wo.created_on)) }}
                    </TableCell>
                    <TableCell class="font-mono text-sm">{{
                        renderOrNA(wo.order)
                    }}</TableCell>
                    <TableCell>{{ renderOrNA(wo.order_type_label) }}</TableCell>
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
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No work orders found</p>
    </div>
</template>
