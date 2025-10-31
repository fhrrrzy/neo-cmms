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
    equipmentUrl: { type: String, default: '' },
});
const emit = defineEmits(['update:open', 'print']);

const close = () => emit('update:open', false);
const onPrint = () => emit('print');

const shareToWhatsApp = () => {
    const url = props.equipmentUrl || window.location.href;
    const text = `Check out this equipment: ${props.description || 'Equipment Details'}\n\n${url}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(whatsappUrl, '_blank');
};

const shareToTelegram = () => {
    const url = props.equipmentUrl || window.location.href;
    const text = `Check out this equipment: ${props.description || 'Equipment Details'}\n\n${url}`;
    const telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
    window.open(telegramUrl, '_blank');
};

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
                    class="h-64 w-64 rounded-lg"
                />
                <p
                    v-if="props.description"
                    class="text-center text-sm whitespace-pre-line text-muted-foreground print:text-black"
                >
                    {{ props.description }}
                </p>
                <!-- Share Section -->
                <div class="w-full space-y-3">
                    <h3 class="text-center text-sm font-medium">Share via</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <Button
                            variant="outline"
                            class="flex h-10 items-center justify-center gap-2"
                            @click="shareToWhatsApp"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                                />
                            </svg>
                            WhatsApp
                        </Button>
                        <Button
                            variant="outline"
                            class="flex h-10 items-center justify-center gap-2"
                            @click="shareToTelegram"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                                />
                            </svg>
                            Telegram
                        </Button>
                    </div>

                    <!-- Print Button -->
                    <div class="flex justify-center">
                        <Button
                            variant="outline"
                            class="flex h-10 items-center justify-center gap-2"
                            @click="onPrint"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                                <path
                                    d="m6,18h-2a2,2 0 0,1 -2,-2v-5a2,2 0 0,1 2,-2h16a2,2 0 0,1 2,2v5a2,2 0 0,1 -2,2h-2"
                                ></path>
                                <polyline
                                    points="6,14 18,14 18,22 6,22 6,14"
                                ></polyline>
                            </svg>
                            Print QR Code
                        </Button>
                    </div>
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
                    class="h-60 w-60 rounded-lg"
                />
                <p
                    v-if="props.description"
                    class="text-center text-sm whitespace-pre-line text-muted-foreground print:text-black"
                >
                    {{ props.description }}
                </p>
                <!-- Share Section -->
                <div class="w-full space-y-3">
                    <h3 class="text-center text-sm font-medium">Share via</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <Button
                            variant="outline"
                            class="flex h-10 items-center justify-center gap-2"
                            @click="shareToWhatsApp"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"
                                />
                            </svg>
                            WhatsApp
                        </Button>
                        <Button
                            variant="outline"
                            class="flex h-10 items-center justify-center gap-2"
                            @click="shareToTelegram"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                                />
                            </svg>
                            Telegram
                        </Button>
                    </div>

                    <!-- Print Button -->
                    <div class="flex justify-center">
                        <Button
                            variant="outline"
                            class="flex h-10 items-center justify-center gap-2"
                            @click="onPrint"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                                <path
                                    d="m6,18h-2a2,2 0 0,1 -2,-2v-5a2,2 0 0,1 2,-2h16a2,2 0 0,1 2,2v5a2,2 0 0,1 -2,2h-2"
                                ></path>
                                <polyline
                                    points="6,14 18,14 18,22 6,22 6,14"
                                ></polyline>
                            </svg>
                            Print QR Code
                        </Button>
                    </div>

                    <DrawerClose as-child>
                        <Button variant="ghost" class="w-full">Close</Button>
                    </DrawerClose>
                </div>
            </div>
        </DrawerContent>
    </Drawer>
</template>
