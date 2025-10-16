<script setup>
import EquipmentWorkOrderTable from '@/components/tables/equipment-work-order/EquipmentWorkOrderTable.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps({
    open: { type: Boolean, default: false },
    orderNumber: { type: String, default: '' },
});
const emit = defineEmits(['update:open']);
</script>

<template>
    <Dialog :open="props.open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="max-h-[85vh] overflow-hidden sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Work Order Items</DialogTitle>
            </DialogHeader>
            <div class="max-h-[75vh] space-y-3 overflow-auto">
                <EquipmentWorkOrderTable :order-number="orderNumber" />
            </div>
        </DialogContent>
    </Dialog>
</template>
