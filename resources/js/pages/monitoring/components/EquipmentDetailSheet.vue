<template>
    <Sheet v-model:open="isOpenComputed">
        <SheetContent
            side="bottom"
            class="h-[100vh] max-h-[100vh] w-full overflow-hidden p-0"
            :hide-close="true"
            @after-enter="onAfterEnter"
            @before-leave="onBeforeLeave"
        >
            <!-- Header -->
            <div
                class="flex h-16 items-center justify-between border-b bg-background px-6"
            >
                <div class="flex items-center gap-4">
                    <Button
                        variant="outline"
                        size="icon"
                        @click="close"
                        class="h-8 w-8"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span class="sr-only">Back</span>
                    </Button>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                        @click="isQrOpen = true"
                        aria-label="Open QR code"
                    >
                        <QrCode class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" @click="goToDetailPage">
                        <ExternalLink class="mr-2 h-4 w-4" />
                        Open in New Tab
                    </Button>
                </div>
            </div>

            <!-- Content -->
            <div class="h-[calc(100vh-4rem)] flex-1 overflow-auto">
                <div
                    v-if="loading"
                    class="flex h-96 items-center justify-center"
                >
                    <div class="space-y-4 text-center">
                        <div
                            class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"
                        ></div>
                        <p class="text-muted-foreground">
                            Loading equipment details...
                        </p>
                    </div>
                </div>
                <div
                    v-else-if="error"
                    class="flex h-96 items-center justify-center px-6"
                >
                    <div class="space-y-3 text-center">
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10"
                        >
                            <AlertCircle class="h-6 w-6 text-destructive" />
                        </div>
                        <p class="text-base font-medium text-destructive">
                            {{ error }}
                        </p>
                        <div class="flex items-center justify-center gap-2">
                            <Button
                                @click="fetchEquipmentDetail"
                                variant="outline"
                            >
                                Try Again
                            </Button>
                            <Button variant="ghost" @click="close"
                                >Close</Button
                            >
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <!-- Mount heavy content only after sheet finishes entering -->
                    <div v-if="entered">
                        <EquipmentContent
                            :equipment="equipment"
                            :loading="loading"
                            :not-found="error === 'Equipment not found'"
                            :uuid="equipmentNumber"
                            :equipment-number="equipment.equipment_number"
                            :date-range="{
                                start: dateRangeStore.start,
                                end: dateRangeStore.end,
                            }"
                            :range-value="rangeValue"
                            :popover-open="popoverOpen"
                            :show-back-button="false"
                            :show-qr-button="false"
                            @open-qr="isQrOpen = true"
                            @go-back="close"
                            @update:range-value="rangeValue = $event"
                            @update:popover-open="popoverOpen = $event"
                        />
                    </div>
                </div>
            </div>

            <!-- QR Code Modal -->
            <QrShare
                :open="isQrOpen"
                :qrcode="qrcode"
                :description="equipment.equipment_description"
                :equipment-url="equipmentUrl"
                @update:open="(v) => (isQrOpen = v)"
                @print="printQr"
            />
        </SheetContent>
    </Sheet>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import EquipmentContent from '@/pages/equipment/detail/components/EquipmentContent.vue';
import QrShare from '@/pages/equipment/detail/components/QrShare.vue';
import { useDateRangeStore } from '@/stores/useDateRangeStore';
import { parseDate } from '@internationalized/date';
import { useQRCode } from '@vueuse/integrations/useQRCode';
import axios from 'axios';
import { AlertCircle, ArrowLeft, ExternalLink, QrCode } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    equipmentNumber: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['close']);

// Computed property to handle prop mutation
const isOpenComputed = computed({
    get: () => props.isOpen,
    set: (value) => {
        if (!value) {
            emit('close');
        }
    },
});

const equipment = ref({
    equipment_number: '',
    equipment_description: '',
    company_code: '',
    object_number: '',
    point: '',
    plant: null,
    station: null,
    cumulative_running_hours: 0,
    recent_running_times: [],
});

const loading = ref(false);
const error = ref(null);
const entered = ref(false);

// QR Code state
const isQrOpen = ref(false);
const equipmentUrl = computed(() => {
    if (typeof window !== 'undefined' && props.equipmentNumber) {
        return `${window.location.origin}/equipment/${props.equipmentNumber}`;
    }
    return '';
});
const qrcode = useQRCode(equipmentUrl, {
    errorCorrectionLevel: 'H',
    margin: 3,
});

// Date range state - use global store directly
const dateRangeStore = useDateRangeStore();
const popoverOpen = ref(false);
const rangeValue = ref({
    start: dateRangeStore.start ? parseDate(dateRangeStore.start) : undefined,
    end: dateRangeStore.end ? parseDate(dateRangeStore.end) : undefined,
});

const printQr = () => {
    const qrSrc = qrcode?.value;
    if (!qrSrc) return;
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;
    printWindow.document.write(
        `<!doctype html><html><head><title>QR Code</title><style>body{margin:0} .container{display:flex;align-items:center;justify-content:center;height:100vh;padding:16px;} .content{display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center} img{width:320px;height:320px} p{margin:0;white-space:pre-line;color:#000}@media print{img{width:320px;height:320px}}</style></head><body><div class="container"><div class="content"><img src="${qrSrc}" alt="QR Code" /><p>${equipment.value?.equipment_description || ''}</p><p class="text-xs text-gray-500">${equipmentUrl.value}</p></div></div><script>window.onload=()=>{window.focus();window.print();window.close();};<\/script></body></html>`,
    );
    printWindow.document.close();
};

const close = () => {
    emit('close');
};

const goToDetailPage = () => {
    window.open(`/equipment/${props.equipmentNumber}`, '_blank');
};

const fetchEquipmentDetail = async () => {
    if (!props.equipmentNumber) return;

    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (dateRangeStore.start)
            params.append('date_start', dateRangeStore.start);
        if (dateRangeStore.end) params.append('date_end', dateRangeStore.end);

        // props.equipmentNumber is actually UUID in this context
        const { data } = await axios.get(
            `/api/equipment/${props.equipmentNumber}?${params}`,
        );
        equipment.value = data.equipment;
        // Defer any heavy reflow work until next tick/idle
        await nextTick();
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {});
        }
    } catch (err) {
        if (err?.response?.status === 404) {
            error.value = 'Equipment not found';
        } else {
            error.value =
                err.response?.data?.message ||
                'Terjadi kesalahan saat memuat data';
        }
        console.error('Error fetching equipment:', err);
    } finally {
        loading.value = false;
    }
};
const onAfterEnter = () => {
    entered.value = true;
};

const onBeforeLeave = () => {
    entered.value = false;
};

// Watch for both equipment number and sheet open state changes
watch(
    [() => props.equipmentNumber, () => props.isOpen],
    ([newNumber, isOpen]) => {
        if (newNumber && isOpen) {
            // Show skeleton immediately, defer network to next frames for smooth animation
            loading.value = true;
            error.value = null;
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    fetchEquipmentDetail();
                });
            });
        }
    },
);

// Watch for date range changes in the store and update local rangeValue
watch(
    () => ({ start: dateRangeStore.start, end: dateRangeStore.end }),
    (newRange) => {
        rangeValue.value = {
            start: newRange.start ? parseDate(newRange.start) : undefined,
            end: newRange.end ? parseDate(newRange.end) : undefined,
        };
    },
    { immediate: true },
);

// Watch for local rangeValue changes and update the store
watch(
    rangeValue,
    (newValue) => {
        if (newValue?.start && newValue?.end) {
            const startStr = newValue.start.toString();
            const endStr = newValue.end.toString();
            if (
                startStr !== dateRangeStore.start ||
                endStr !== dateRangeStore.end
            ) {
                dateRangeStore.setRange({ start: startStr, end: endStr });
            }
        }
    },
    { deep: true },
);
</script>
