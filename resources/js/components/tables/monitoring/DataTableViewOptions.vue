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
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    table: {
        type: Object,
        required: true,
    },
});

const COLUMN_VISIBILITY_KEY = 'monitoring_table_column_visibility';
const isInitialized = ref(false);

const columns = computed(() =>
    props.table
        .getAllColumns()
        .filter(
            (column) =>
                typeof column.accessorFn !== 'undefined' && column.getCanHide(),
        ),
);

// Load column visibility from localStorage on mount
onMounted(() => {
    // The DataTable component now handles initial loading
    // Just mark as initialized to allow saving
    isInitialized.value = true;
});

// Function to save column visibility to localStorage
const saveColumnVisibility = () => {
    if (props.table?.getState()?.columnVisibility && isInitialized.value) {
        localStorage.setItem(
            COLUMN_VISIBILITY_KEY,
            JSON.stringify(props.table.getState().columnVisibility),
        );
    }
};

// Watch for column visibility changes and save to localStorage
watch(
    () => props.table?.getState()?.columnVisibility,
    () => {
        saveColumnVisibility();
    },
    { deep: true },
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child class="">
            <Button variant="outline" size="default" class="h-9">
                <Settings class="h-4 w-4" />
                <span class="hidden sm:inline">View</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem
                v-for="column in columns"
                :key="column.id"
                class="capitalize"
                :model-value="column.getIsVisible()"
                @update:model-value="
                    (value) => {
                        column.toggleVisibility(!!value);
                        saveColumnVisibility();
                    }
                "
            >
                {{ column.id }}
            </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
