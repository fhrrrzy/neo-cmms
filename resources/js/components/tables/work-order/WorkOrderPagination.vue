<script setup>
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['page-change', 'page-size-change']);

const selectOpen = ref(false);

const handlePageSizeChange = (value) => {
    selectOpen.value = false;
    emit('page-size-change', Number(value));
};

const handlePageChange = (page) => {
    emit('page-change', page);
};

const pageNumbers = computed(() => {
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const totalButtons = 5;
    const pages = [];

    let start, end;

    if (last <= totalButtons) {
        start = 1;
        end = last;
    } else if (current <= 3) {
        start = 1;
        end = totalButtons;
    } else if (current >= last - 2) {
        start = last - totalButtons + 1;
        end = last;
    } else {
        start = current - 2;
        end = current + 2;
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

const pageSizeOptions = computed(() => {
    return [10, 15, 25, 50, 100];
});
</script>

<template>
    <div class="space-y-4 px-2 md:flex md:items-center md:justify-between">
        <!-- Results info -->
        <div class="text-center text-xs text-muted-foreground sm:text-left sm:text-sm">
            <span class="sm:hidden">
                {{
                    props.pagination.from ||
                    (props.pagination.current_page - 1) *
                    props.pagination.per_page +
                    1
                }}-{{
                    props.pagination.to ||
                    Math.min(
                        props.pagination.current_page *
                        props.pagination.per_page,
                        props.pagination.total,
                    )
                }}
                of {{ props.pagination.total }}
            </span>
            <span class="hidden sm:inline">
                Showing
                {{
                    props.pagination.from ||
                    (props.pagination.current_page - 1) *
                    props.pagination.per_page +
                    1
                }}
                to
                {{
                    props.pagination.to ||
                    Math.min(
                        props.pagination.current_page *
                        props.pagination.per_page,
                        props.pagination.total,
                    )
                }}
                of {{ props.pagination.total }} results
            </span>
        </div>

        <!-- Page size selector -->
        <div class="flex items-center justify-center space-x-2 sm:justify-end">
            <p class="text-xs font-medium sm:text-sm">Rows per page</p>
            <Select v-model:open="selectOpen" :model-value="`${props.pagination.per_page}`"
                @update:model-value="handlePageSizeChange">
                <SelectTrigger class="h-7 w-16 text-xs sm:h-8 sm:w-[100px] sm:text-sm">
                    <SelectValue :placeholder="`${props.pagination.per_page}`" />
                </SelectTrigger>
                <SelectContent side="top">
                    <SelectItem v-for="pageSize in pageSizeOptions" :key="pageSize" :value="`${pageSize}`"
                        class="text-xs sm:text-sm">
                        {{ pageSize }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Navigation -->
        <div class="flex items-center justify-center space-x-1 sm:space-x-2">
            <!-- First page button -->
            <Button variant="outline" class="hidden h-7 w-7 p-0 sm:h-8 sm:w-8 md:flex"
                :disabled="props.pagination.current_page <= 1" @click="handlePageChange(1)">
                <span class="sr-only">Go to first page</span>
                <ChevronsLeft class="h-3 w-3 sm:h-4 sm:w-4" />
            </Button>

            <!-- Previous page button -->
            <Button variant="outline" class="h-7 w-7 p-0 sm:h-8 sm:w-8" :disabled="props.pagination.current_page <= 1"
                @click="handlePageChange(props.pagination.current_page - 1)">
                <span class="sr-only">Go to previous page</span>
                <ChevronLeft class="h-3 w-3 sm:h-4 sm:w-4" />
            </Button>

            <!-- Page numbers (desktop only) -->
            <div class="hidden items-center space-x-1 lg:flex">
                <Button v-for="page in pageNumbers" :key="page" variant="outline" class="h-8 w-8 p-0" :class="{
                    'border-primary bg-primary text-primary-foreground hover:bg-primary/90 dark:text-background dark:bg-foreground dark:hover:bg-foreground/90':
                        page === props.pagination.current_page,
                }" @click="handlePageChange(page)">
                    {{ page }}
                </Button>
            </div>

            <!-- Current page indicator (tablet only) -->
            <div class="flex items-center justify-center px-2 text-xs font-medium sm:text-sm lg:hidden">
                Page {{ props.pagination.current_page }} of
                {{ props.pagination.last_page }}
            </div>

            <!-- Next page button -->
            <Button variant="outline" class="h-7 w-7 p-0 sm:h-8 sm:w-8" :disabled="props.pagination.current_page >= props.pagination.last_page
                " @click="handlePageChange(props.pagination.current_page + 1)">
                <span class="sr-only">Go to next page</span>
                <ChevronRight class="h-3 w-3 sm:h-4 sm:w-4" />
            </Button>

            <!-- Last page button -->
            <Button variant="outline" class="hidden h-7 w-7 p-0 sm:h-8 sm:w-8 md:flex" :disabled="props.pagination.current_page >= props.pagination.last_page
                " @click="handlePageChange(props.pagination.last_page)">
                <span class="sr-only">Go to last page</span>
                <ChevronsRight class="h-3 w-3 sm:h-4 sm:w-4" />
            </Button>
        </div>
    </div>
</template>
