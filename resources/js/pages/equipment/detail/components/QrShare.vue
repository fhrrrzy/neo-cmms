<script setup>
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';

const props = defineProps({
    open: { type: Boolean, default: false },
    qrcode: { type: String, default: '' },
});
const emit = defineEmits(['update:open', 'print']);

const close = () => emit('update:open', false);
const onPrint = () => emit('print');
</script>

<template>
    <!-- Desktop Dialog -->
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="hidden sm:block sm:max-w-md"
            @pointer-down-outside.prevent
            @interact-outside.prevent
            @escape-key-down.prevent
        >
            <DialogHeader>
                <DialogTitle>Share via QR</DialogTitle>
                <DialogDescription />
            </DialogHeader>
            <div class="flex flex-col items-center gap-4 p-5">
                <img
                    v-if="props.qrcode"
                    :src="props.qrcode"
                    alt="QR Code"
                    class="h-64 w-64"
                />
                <div class="flex w-full justify-end gap-2">
                    <Button variant="outline" @click="onPrint">Print</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Mobile Drawer -->
    <Drawer :open="props.open" @update:open="emit('update:open', $event)">
        <DrawerContent class="sm:hidden">
            <DrawerHeader>
                <DrawerTitle>Share via QR</DrawerTitle>
                <DrawerDescription>Scan to open this page</DrawerDescription>
            </DrawerHeader>
            <div class="flex flex-col items-center gap-4 p-4">
                <img
                    v-if="props.qrcode"
                    :src="props.qrcode"
                    alt="QR Code"
                    class="h-60 w-60"
                />
                <div class="flex w-full justify-end gap-2">
                    <Button variant="outline" @click="onPrint">Print</Button>
                    <DrawerClose as-child>
                        <Button @click="close">Close</Button>
                    </DrawerClose>
                </div>
            </div>
        </DrawerContent>
    </Drawer>
</template>
