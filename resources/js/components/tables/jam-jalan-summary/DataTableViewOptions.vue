<script setup lang="js">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Settings } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    visibleDates: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update:visibleDates']);

const COLUMN_VISIBILITY_KEY = 'jam_jalan_summary_date_visibility';
const isInitialized = ref(false);

// Load column visibility from localStorage on mount
onMounted(() => {
    isInitialized.value = true;
});

// Function to save column visibility to localStorage
const saveColumnVisibility = () => {
    if (props.visibleDates && isInitialized.value) {
        localStorage.setItem(
            COLUMN_VISIBILITY_KEY,
            JSON.stringify(props.visibleDates),
        );
    }
};

// Watch for column visibility changes and save to localStorage
watch(
    () => props.visibleDates,
    () => {
        saveColumnVisibility();
    },
    { deep: true },
);

const toggleDate = (date, value) => {
    emit('update:visibleDates', {
        ...props.visibleDates,
        [date]: !!value,
    });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="default" class="h-9">
                <Settings class="h-4 w-4" />
                <span class="hidden sm:inline">View</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            class="max-h-[300px] w-56 overflow-y-auto"
        >
            <DropdownMenuLabel>Toggle dates</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem
                v-for="(isVisible, date) in visibleDates"
                :key="date"
                class="capitalize"
                :model-value="isVisible"
                @update:model-value="toggleDate(date, $event)"
            >
                {{ date }}
            </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
