<script setup lang="js">
import { CalendarIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { format } from 'date-fns';
import {
    DateRangePickerContent,
    DateRangePickerField,
    DateRangePickerRoot,
    DateRangePickerTrigger,
} from 'reka-ui';
import { RangeCalendar } from '@/components/ui/range-calendar';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ from: null, to: null }),
    },
    placeholder: {
        type: String,
        default: 'Pilih periode',
    },
    closeOnSelect: {
        type: Boolean,
        default: true,
    },
    numberOfMonths: {
        type: Number,
        default: 2,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const dateRange = ref({
    from: props.modelValue?.from ? new Date(props.modelValue.from) : null,
    to: props.modelValue?.to ? new Date(props.modelValue.to) : null,
});

// Watch for prop changes
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            dateRange.value = {
                from: newValue.from ? new Date(newValue.from) : null,
                to: newValue.to ? new Date(newValue.to) : null,
            };
        }
    },
    { deep: true },
);

// Watch for internal changes and emit
watch(
    dateRange,
    (newDateRange) => {
        const formattedRange = {
            from: newDateRange.from ? format(newDateRange.from, 'yyyy-MM-dd') : null,
            to: newDateRange.to ? format(newDateRange.to, 'yyyy-MM-dd') : null,
        };
        emit('update:modelValue', formattedRange);
    },
    { deep: true },
);

const displayValue = computed(() => {
    if (!dateRange.value.from && !dateRange.value.to) {
        return props.placeholder;
    }
    
    const fromStr = dateRange.value.from ? format(dateRange.value.from, 'yyyy-MM-dd') : '';
    const toStr = dateRange.value.to ? format(dateRange.value.to, 'yyyy-MM-dd') : '';
    
    return `${fromStr} - ${toStr}`;
});
</script>

<template>
    <DateRangePickerRoot
        v-model="dateRange"
        :close-on-select="closeOnSelect"
        :disabled="disabled"
    >
        <DateRangePickerField
            v-slot="{ segments }"
            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        >
            <div class="flex items-center gap-2">
                <CalendarIcon class="h-4 w-4" />
                <span>{{ displayValue }}</span>
            </div>
            <DateRangePickerTrigger class="h-4 w-4 opacity-50">
                <CalendarIcon class="h-4 w-4" />
            </DateRangePickerTrigger>
        </DateRangePickerField>
        
        <DateRangePickerContent class="w-auto p-0">
            <RangeCalendar
                v-model="dateRange"
                :number-of-months="numberOfMonths"
            />
        </DateRangePickerContent>
    </DateRangePickerRoot>
</template>
