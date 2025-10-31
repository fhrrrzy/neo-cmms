<script setup>
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useFullscreenStore } from '@/stores/useFullscreenStore';
import axios from 'axios';
import {
    ChevronLeft,
    ChevronRight,
    ImageOffIcon,
    Maximize,
    Minimize,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Button from './ui/button/Button.vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    equipmentUuid: { type: String, required: true },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(props.modelValue);
watch(
    () => props.modelValue,
    (v) => {
        open.value = v;
        if (v) fetchImages();
    },
);
watch(open, (v) => emit('update:modelValue', v));

const images = ref([]);
const activeIndex = ref(0);
const active = computed(() => images.value[activeIndex.value] || null);
const loading = ref(false);
const error = ref('');

// lightbox-like zoom state
const zoomed = ref(false);
const zoomOrigin = ref({ x: 50, y: 50 });

// Fullscreen state (global)
const fullscreen = useFullscreenStore();
const isFullscreen = computed(() => fullscreen.isFullscreen);

// Reset zoom when changing images
watch(activeIndex, () => {
    zoomed.value = false;
    zoomOrigin.value = { x: 50, y: 50 };
});

async function toggleFullscreen() {
    // Make the dialog content element the target when possible for better UX
    const dialogEl = document.querySelector('[data-slot="dialog-content"]');
    await fullscreen.toggle(dialogEl || document.documentElement);
}

function toggleZoom(e) {
    zoomed.value = !zoomed.value;
    if (zoomed.value && e) {
        updateZoomOrigin(e);
    }
}

function updateZoomOrigin(e) {
    if (!zoomed.value) return;
    const target = e.currentTarget;
    if (!target) return;
    const rect = target.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    zoomOrigin.value = {
        x: Math.max(0, Math.min(100, x)),
        y: Math.max(0, Math.min(100, y)),
    };
}

async function fetchImages() {
    if (!props.equipmentUuid) return;
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get(
            `/api/equipment/${props.equipmentUuid}/images`,
        );
        images.value = Array.isArray(data?.data) ? data.data : [];
        activeIndex.value = 0;
    } catch (e) {
        error.value = 'Gagal memuat gambar';
    } finally {
        loading.value = false;
    }
}

function next() {
    if (images.value.length === 0) return;
    activeIndex.value = (activeIndex.value + 1) % images.value.length;
}

function prev() {
    if (images.value.length === 0) return;
    activeIndex.value =
        (activeIndex.value - 1 + images.value.length) % images.value.length;
}

function selectAt(i) {
    activeIndex.value = i;
}

// Ensure fullscreen exits when modal closes
watch(open, async (v) => {
    if (!v) await fullscreen.exit();
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            :fullscreen="true"
            :show-close="false"
            class="overflow-hidden p-0"
        >
            <DialogHeader
                class="flex flex-row items-center justify-between border-b px-4 py-3"
            >
                <DialogTitle class="text-sm font-medium">
                    Equipment Images
                </DialogTitle>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        @click="toggleFullscreen"
                    >
                        <Maximize v-if="!isFullscreen" class="h-4 w-4" />
                        <Minimize v-else class="h-4 w-4" />
                    </Button>
                    <DialogClose as-child>
                        <Button variant="outline" size="icon">✕</Button>
                    </DialogClose>
                </div>
            </DialogHeader>

            <div class="flex h-[calc(100vh-73px)] flex-col p-4">
                <div
                    v-if="loading"
                    class="flex flex-1 items-center justify-center text-muted-foreground"
                >
                    Loading...
                </div>
                <div
                    v-else-if="error"
                    class="flex flex-1 items-center justify-center text-destructive"
                >
                    {{ error }}
                </div>
                <div v-else-if="images.length === 0" class="flex flex-1">
                    <Empty class="h-full">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <ImageOffIcon />
                            </EmptyMedia>
                            <EmptyTitle>Tidak ada gambar</EmptyTitle>
                            <EmptyDescription>
                                Belum ada gambar untuk equipment ini
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </div>
                <div v-else class="flex flex-1 flex-col space-y-4">
                    <!-- Focus image -->
                    <div
                        class="relative flex w-full flex-1 items-center justify-center overflow-hidden rounded-md bg-transparent"
                    >
                        <img
                            :src="active?.url"
                            :alt="active?.name || 'image'"
                            class="max-h-[75svh] max-w-full object-contain transition-transform duration-300 ease-out select-none"
                            :class="
                                zoomed ? 'cursor-zoom-out' : 'cursor-zoom-in'
                            "
                            loading="lazy"
                            :style="{
                                transform: zoomed ? 'scale(1.75)' : 'scale(1)',
                                transformOrigin: `${zoomOrigin.x}% ${zoomOrigin.y}%`,
                            }"
                            @click="toggleZoom"
                            @mousemove="updateZoomOrigin"
                        />
                        <div
                            v-if="active?.name"
                            class="pointer-events-none absolute bottom-2 left-2 rounded bg-black/60 px-2 py-1 text-xs text-white dark:bg-white/60 dark:text-black"
                        >
                            {{ active.name }}
                        </div>
                        <button
                            class="absolute top-1/2 left-2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded bg-white/80 text-foreground duration-100 hover:bg-white dark:bg-black/40 dark:hover:bg-black/60"
                            @click="prev"
                        >
                            <ChevronLeft class="h-6 w-6" />
                        </button>
                        <button
                            class="absolute top-1/2 right-2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded bg-white/80 text-foreground duration-100 hover:bg-white dark:bg-black/40 dark:hover:bg-black/60"
                            @click="next"
                        >
                            <ChevronRight class="h-6 w-6" />
                        </button>
                    </div>
                    <!-- Thumbnails -->
                    <div
                        class="flex shrink-0 items-center gap-2 overflow-x-auto pb-2"
                    >
                        <button
                            v-for="(img, i) in images"
                            :key="img.id || i"
                            class="shrink-0 rounded-md border p-0.5 hover:border-primary"
                            :class="
                                i === activeIndex
                                    ? 'border-primary'
                                    : 'border-border'
                            "
                            @click="selectAt(i)"
                        >
                            <img
                                :src="img.url"
                                :alt="img.name || 'thumb'"
                                class="h-16 w-20 rounded object-cover transition-all"
                                :class="
                                    i === activeIndex
                                        ? 'brightness-100'
                                        : 'brightness-50 hover:brightness-100'
                                "
                                loading="lazy"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
