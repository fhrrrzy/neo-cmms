<script setup>
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { runningTimeColumns } from './running-time/columns';

const props = defineProps({
    data: { type: Array, default: () => [] },
    formatDate: { type: Function, required: true },
    formatNumber: { type: Function, required: true },
});
</script>

<template>
    <div v-if="props.data?.length > 0">
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
                <TableRow v-for="(time, index) in props.data" :key="index">
                    <TableCell class="font-medium">
                        {{ props.formatDate(time.date) }}
                    </TableCell>
                    <TableCell class="text-right font-mono">
                        {{ props.formatNumber(time.counter_reading) }}
                    </TableCell>
                    <TableCell class="text-right font-mono">
                        {{ props.formatNumber(time.running_hours) }}
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
    <div v-else class="py-8 text-center text-muted-foreground">
        <p>No running times data available</p>
    </div>
</template>
