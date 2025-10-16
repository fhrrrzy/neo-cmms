<script setup>
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import {
    Stepper,
    StepperDescription,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';
import axios from 'axios';
import { ArrowLeft, Check, Circle, Dot } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    material: { type: String, required: true },
    materialDescription: { type: String, default: '' },
    equipmentNumber: { type: String, required: false },
    dateRange: { type: Object, required: false },
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

const fetchItems = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = new URLSearchParams();
        params.append('material', props.material);
        if (props.equipmentNumber)
            params.append('equipment_number', props.equipmentNumber);
        if (props.dateRange?.start)
            params.append('date_start', props.dateRange.start);
        if (props.dateRange?.end)
            params.append('date_end', props.dateRange.end);
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
            const hours = rt?.counter_reading ?? rt?.hours ?? null;
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

const getHoursFor = (eq, dt) => {
    const key = `${eq}|${dt?.slice(0, 10)}`;
    const val = runningHoursByKey.value[key];
    return val === undefined ? null : val;
};

onMounted(() => {
    if (props.open) fetchItems();
});

watch(
    () => [
        props.open,
        props.material,
        props.equipmentNumber,
        props.dateRange?.start,
        props.dateRange?.end,
    ],
    ([isOpen]) => {
        if (isOpen) fetchItems();
    },
);
</script>

<template>
    <Sheet v-model:open="isOpenComputed">
        <SheetContent
            side="left"
            class="h-[100vh] max-h-[100vh] w-full overflow-hidden p-0 sm:max-w-lg"
            :hide-close="true"
        >
            <!-- Header -->
            <div
                class="flex h-14 items-center justify-between border-b bg-background px-4"
            >
                <div class="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8"
                        @click="isOpenComputed = false"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span class="sr-only">Kembali</span>
                    </Button>
                    <div class="text-left">
                        <p class="text-sm leading-none font-semibold">
                            {{ material }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ materialDescription || 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="h-[calc(100vh-3.5rem)] overflow-auto p-4">
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
                <div v-else>
                    <Stepper
                        orientation="vertical"
                        class="mx-auto flex w-full max-w-md flex-col justify-start gap-10"
                    >
                        <StepperItem
                            v-for="(ewo, idx) in items"
                            :key="`${ewo.order_number}-${idx}`"
                            v-slot="{ state }"
                            class="relative flex w-full items-start gap-6"
                            :step="idx + 1"
                        >
                            <StepperSeparator
                                v-if="idx !== items.length - 1"
                                class="absolute top-[38px] left-[18px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                            />

                            <StepperTrigger as-child>
                                <Button
                                    :variant="
                                        state === 'completed' ||
                                        state === 'active'
                                            ? 'default'
                                            : 'outline'
                                    "
                                    size="icon"
                                    class="z-10 shrink-0 rounded-full"
                                    :class="[
                                        state === 'active' &&
                                            'ring-2 ring-ring ring-offset-2 ring-offset-background',
                                    ]"
                                >
                                    <Check
                                        v-if="state === 'completed'"
                                        class="size-5"
                                    />
                                    <Circle v-else-if="state === 'active'" />
                                    <Dot v-else />
                                </Button>
                            </StepperTrigger>

                            <div class="flex min-w-0 flex-col gap-1">
                                <StepperTitle
                                    :class="[
                                        state === 'active' && 'text-primary',
                                    ]"
                                    class="truncate text-sm font-semibold transition lg:text-base"
                                >
                                    {{ formatDate(ewo.requirements_date) }} — WO
                                    #{{ ewo.order_number || 'N/A' }}
                                </StepperTitle>
                                <StepperDescription
                                    :class="[
                                        state === 'active' && 'text-primary',
                                    ]"
                                    class="text-xs text-muted-foreground transition lg:text-sm"
                                >
                                    {{ ewo.material_description || 'N/A' }}
                                </StepperDescription>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    Equipment:
                                    <span class="font-mono">{{
                                        ewo.equipment_number || 'N/A'
                                    }}</span>
                                    · Counter reading:
                                    <span class="font-mono">
                                        {{
                                            formatNumber(
                                                getHoursFor(
                                                    ewo.equipment_number,
                                                    ewo.requirements_date,
                                                ),
                                                2,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    UoM:
                                    {{ ewo.base_unit_of_measure || 'N/A' }} ·
                                    Qty:
                                    {{ ewo.requirement_quantity ?? 'N/A' }}
                                </div>
                            </div>

                            <!-- Days gap pill centered on the stepper line -->
                            <template v-if="idx < items.length - 1">
                                <span
                                    class="pointer-events-none absolute top-[72px] left-[18px] -translate-x-1/2 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                >
                                    {{
                                        dayDiffBetween(
                                            ewo.requirements_date,
                                            items[idx + 1]?.requirements_date,
                                        )
                                    }}
                                    hari
                                </span>
                            </template>
                        </StepperItem>
                    </Stepper>
                    <div
                        v-if="items.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Tidak ada data
                    </div>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
