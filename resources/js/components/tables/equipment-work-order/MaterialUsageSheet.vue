<script setup>
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { loadDateRange } from '@/lib/dateRangeStorage';
import axios from 'axios';
import { ArrowLeft, History } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    material: { type: String, required: true },
    materialDescription: { type: String, default: '' },
    equipmentNumber: { type: String, required: true },
});

const emit = defineEmits(['update:open']);

const isOpenComputed = computed({
    get: () => props.open,
    set: (v) => emit('update:open', v),
});

const items = ref([]);
const loading = ref(false);
const error = ref('');
const runningHoursByKey = ref({}); // key: `${equipment_number}|${date}` -> hours
const dateRange = ref(null);
const equipmentDetails = ref(null);

const fetchEquipmentDetails = async () => {
    if (!props.equipmentNumber) return;
    try {
        const { data } = await axios.get(
            `/api/equipment/${encodeURIComponent(props.equipmentNumber)}`,
        );
        equipmentDetails.value = data?.equipment || null;
    } catch {
        equipmentDetails.value = null;
    }
};

const fetchItems = async () => {
    loading.value = true;
    error.value = '';

    // Validate required fields
    if (!props.material || !props.equipmentNumber) {
        error.value = 'Material dan Equipment Number diperlukan';
        loading.value = false;
        return;
    }

    try {
        // Fetch items only; skip equipment details to avoid 404 on /api/equipment/:equipmentNumber
        const params = new URLSearchParams();
        params.append('material', props.material);
        params.append('equipment_number', props.equipmentNumber);
        params.append('sort_by', 'requirements_date');
        params.append('sort_direction', 'desc');
        const { data } = await axios.get(
            `/api/equipment-work-orders?${params}`,
        );
        const list = Array.isArray(data?.data) ? data.data : [];
        items.value = list;
        await fetchRunningHoursForItems(list);
    } catch (e) {
        error.value = e?.response?.data?.message || 'Gagal memuat data';
    } finally {
        loading.value = false;
    }
};

const fetchRunningHoursForItems = async (list) => {
    const seen = new Set();
    for (const row of list) {
        const eq = row?.equipment_number;
        const dt = row?.requirements_date?.slice(0, 10);
        if (!eq || !dt) continue;
        const key = `${eq}|${dt}`;
        if (seen.has(key) || runningHoursByKey.value[key] !== undefined)
            continue;
        seen.add(key);
        try {
            const p = new URLSearchParams();
            p.append('date_start', dt);
            p.append('date_end', dt);
            p.append('rt_page', '1');
            p.append('rt_per_page', '1');
            p.append('rt_sort_by', 'date');
            p.append('rt_sort_direction', 'desc');
            const { data } = await axios.get(
                `/api/equipment/${encodeURIComponent(eq)}?${p}`,
            );
            const rt = data?.equipment?.recent_running_times?.[0];
            const hours = rt?.counter_reading ?? rt?.running_hours ?? null;
            runningHoursByKey.value[key] = hours;
        } catch {
            runningHoursByKey.value[key] = null;
        }
    }
};

const formatDate = (value) => {
    if (!value) return 'N/A';
    try {
        const d = new Date(value);
        return d.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return String(value);
    }
};

const dayDiffBetween = (a, b) => {
    if (!a || !b) return null;
    const d1 = new Date(a);
    const d2 = new Date(b);
    const diffMs = Math.abs(d1.getTime() - d2.getTime());
    return Math.max(0, Math.round(diffMs / (1000 * 60 * 60 * 24)));
};

const formatNumber = (n, digits = 2) => {
    const v = Number(n);
    if (!isFinite(v)) return 'N/A';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(v);
};

// Format hours: if effectively zero, show 0 (no decimals); otherwise 2 decimals
const formatHours = (n) => {
    const v = Number(n);
    if (!isFinite(v)) return 'N/A';
    if (Math.abs(v) < 0.005) return '0';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(v);
};

const getHoursFor = (eq, dt) => {
    const key = `${eq}|${dt?.slice(0, 10)}`;
    const val = runningHoursByKey.value[key];
    return val === undefined ? null : val;
};

const counterGapBetween = (currentItem, nextItem) => {
    if (!currentItem || !nextItem) return null;
    const cur = getHoursFor(
        currentItem?.equipment_number,
        currentItem?.requirements_date,
    );
    const nxt = getHoursFor(
        nextItem?.equipment_number,
        nextItem?.requirements_date,
    );
    if (cur == null || nxt == null) return null;
    const gap = Number(cur) - Number(nxt);
    if (!isFinite(gap)) return null;
    return gap;
};

const loadStoredDateRange = () => {
    dateRange.value = loadDateRange();
};

// Compute actual date range from items
const actualDateRange = computed(() => {
    if (!items.value || items.value.length === 0) return null;

    const dates = items.value
        .map((item) => item.requirements_date)
        .filter(Boolean)
        .sort();

    if (dates.length === 0) return null;

    return {
        start: dates[dates.length - 1], // oldest (since sorted desc from API)
        end: dates[0], // newest
    };
});

onMounted(() => {
    loadStoredDateRange();
    if (props.open) fetchItems();
});

watch(
    () => [props.open, props.material, props.equipmentNumber],
    ([isOpen]) => {
        if (isOpen) {
            loadStoredDateRange();
            fetchItems();
        }
    },
);
</script>

<template>
    <Sheet v-model:open="isOpenComputed" class="sheet-above-modal">
        <SheetContent
            side="left"
            class="!z-[150] h-[100dvh] max-h-[100dvh] w-full overflow-hidden p-0 sm:max-w-lg"
            :hide-close="true"
        >
            <!-- Header -->
            <div
                class="flex h-auto min-h-14 flex-col border-b bg-background px-4 py-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="text-left">
                        <p class="text-sm leading-none font-semibold">
                            History Maintenance
                        </p>
                        <p
                            v-if="actualDateRange"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ formatDate(actualDateRange.start) }} -
                            {{ formatDate(actualDateRange.end) }}
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-8 w-8"
                        @click="isOpenComputed = false"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span class="sr-only">Kembali</span>
                    </Button>
                </div>
            </div>

            <!-- Content -->
            <div class="flex h-[calc(100vh-4rem)] flex-col p-4">
                <div
                    v-if="loading"
                    class="flex h-40 items-center justify-center text-muted-foreground"
                >
                    Memuat...
                </div>
                <div
                    v-else-if="error"
                    class="p-4 text-center text-sm text-destructive"
                >
                    {{ error }}
                </div>
                <template v-else>
                    <!-- Material & Equipment Summary (Fixed) -->
                    <div
                        class="mb-4 flex-shrink-0 rounded-lg border bg-card p-4"
                    >
                        <div class="space-y-3">
                            <div>
                                <p class="mb-1 text-xs text-muted-foreground">
                                    Equipment
                                </p>
                                <p class="font-mono text-sm font-semibold">
                                    {{ equipmentNumber || 'N/A' }}
                                </p>
                                <p
                                    v-if="
                                        equipmentDetails?.equipment_description
                                    "
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ equipmentDetails.equipment_description }}
                                </p>
                            </div>
                            <div class="h-px bg-border"></div>

                            <div>
                                <p class="mb-1 text-xs text-muted-foreground">
                                    Material
                                </p>
                                <p class="text-sm font-semibold">
                                    {{ material }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ materialDescription || 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Simple Timeline (Scrollable) -->
                    <div class="min-h-0 flex-1 overflow-auto">
                        <div class="relative space-y-6 pl-4">
                            <div
                                v-for="(ewo, idx) in items"
                                :key="`${ewo.order_number}-${idx}`"
                                class="relative flex items-start gap-4"
                            >
                                <!-- Timeline line -->
                                <div
                                    v-if="idx !== items.length - 1"
                                    class="absolute top-4 left-[7px] h-[calc(100%+1.5rem)] w-px bg-border"
                                ></div>

                                <!-- Days gap badge on line -->
                                <div
                                    v-if="idx < items.length - 1"
                                    class="absolute top-[calc(50%+0.75rem)] left-[7px] z-10 flex -translate-x-1/2 flex-col items-center rounded-full bg-background px-2 py-0.5 text-xs text-muted-foreground ring-1 ring-border"
                                >
                                    <div>
                                        {{
                                            dayDiffBetween(
                                                ewo.requirements_date,
                                                items[idx + 1]
                                                    ?.requirements_date,
                                            )
                                        }}
                                        hari
                                    </div>
                                    <div
                                        class="mt-0.5 text-[10px] text-muted-foreground"
                                    >
                                        <template
                                            v-if="
                                                counterGapBetween(
                                                    ewo,
                                                    items[idx + 1],
                                                ) !== null
                                            "
                                        >
                                            {{
                                                formatHours(
                                                    counterGapBetween(
                                                        ewo,
                                                        items[idx + 1],
                                                    ),
                                                )
                                            }}
                                            Jam
                                        </template>
                                    </div>
                                </div>

                                <!-- Timeline node -->
                                <div
                                    class="relative z-10 mt-1 h-4 w-4 shrink-0 rounded-full border-2 border-primary bg-background"
                                ></div>

                                <!-- Content -->
                                <div
                                    class="flex min-w-0 flex-1 flex-col gap-1 pb-2 pl-5"
                                >
                                    <p class="text-sm font-semibold">
                                        {{ formatDate(ewo.requirements_date) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        WO #{{ ewo.order_number || 'N/A' }}
                                    </p>
                                    <div
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Counter reading:
                                        <span class="font-mono font-medium">
                                            {{
                                                formatHours(
                                                    getHoursFor(
                                                        ewo.equipment_number,
                                                        ewo.requirements_date,
                                                    ),
                                                )
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        UoM:
                                        <span class="font-medium">{{
                                            ewo.base_unit_of_measure || 'N/A'
                                        }}</span>
                                        · Qty:
                                        <span class="font-medium">{{
                                            ewo.requirement_quantity ?? 'N/A'
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="items.length === 0" class="py-8">
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <History />
                                        </EmptyMedia>
                                        <EmptyTitle
                                            >No Maintenance History</EmptyTitle
                                        >
                                        <EmptyDescription>
                                            Tidak ada data
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </SheetContent>
    </Sheet>
</template>

<style>
/* Global styles to ensure sheet appears above dialog */
[data-slot='sheet-overlay'] {
    z-index: 150 !important;
}

/* When MaterialUsageSheet is rendered */
body:has(.sheet-above-modal) [data-slot='dialog-overlay'] {
    z-index: 55 !important;
}

body:has(.sheet-above-modal) [data-slot='dialog-content'] {
    z-index: 60 !important;
}
</style>
