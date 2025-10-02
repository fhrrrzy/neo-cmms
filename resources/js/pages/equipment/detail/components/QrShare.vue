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
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    qrcode: { type: String, default: '' },
    description: { type: String, default: '' },
});
const emit = defineEmits(['update:open', 'print']);

const close = () => emit('update:open', false);
const onPrint = () => emit('print');

// Render only one of Dialog or Drawer to avoid overlapping overlays
const isDesktop = ref(false);
let mql;

const updateMatch = () => {
    if (mql) isDesktop.value = mql.matches;
};

onMounted(() => {
    mql = window.matchMedia('(min-width: 640px)');
    updateMatch();
    mql.addEventListener('change', updateMatch);
});

onBeforeUnmount(() => {
    if (mql) mql.removeEventListener('change', updateMatch);
});
</script>

<template>
    <!-- Desktop Dialog (only mounted on desktop) -->
    <Dialog
        v-if="isDesktop"
        :open="props.open"
        @update:open="emit('update:open', $event)"
    >
        <DialogContent class="sm:max-w-md">
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
                <p
                    v-if="props.description"
                    class="text-center text-sm whitespace-pre-line text-muted-foreground print:text-black"
                >
                    {{ props.description }}
                </p>
                <div class="flex w-full justify-end gap-2">
                    <Button variant="outline" @click="onPrint">Print</Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Mobile Drawer (only mounted on mobile) -->
    <Drawer
        v-else
        :open="props.open"
        @update:open="emit('update:open', $event)"
    >
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
                <p
                    v-if="props.description"
                    class="text-center text-sm whitespace-pre-line text-muted-foreground print:text-black"
                >
                    {{ props.description }}
                </p>
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
