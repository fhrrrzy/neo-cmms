<script setup lang="js">
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
    // Close the select after value change
    selectOpen.value = false;
    emit('page-size-change', Number(value));
};

const handlePageChange = (page) => {
    emit('page-change', page);
};

const pageNumbers = computed(() => {
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const totalButtons = 5; // Total number of page buttons to show
    const pages = [];

    let start, end;

    if (last <= totalButtons) {
        // If total pages is less than or equal to totalButtons, show all pages
        start = 1;
        end = last;
    } else if (current <= 3) {
        // Near the start, show first 5 pages
        start = 1;
        end = totalButtons;
    } else if (current >= last - 2) {
        // Near the end, show last 5 pages
        start = last - totalButtons + 1;
        end = last;
    } else {
        // In the middle, show 2 pages on each side
        start = current - 2;
        end = current + 2;
    }

    // Add pages to array
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});
</script>

<template>
    <div
        class="flex flex-col gap-4 px-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <!-- Results info -->
        <div class="text-sm text-muted-foreground">
            <span class="hidden sm:inline">
                Showing {{ pagination.from }} to {{ pagination.to }} of
                {{ pagination.total }} results
            </span>
            <span class="sm:hidden">
                {{ pagination.from }}-{{ pagination.to }} of
                {{ pagination.total }}
            </span>
        </div>

        <!-- Pagination controls -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:space-x-6 lg:space-x-8"
        >
            <!-- Rows per page selector -->
            <div class="flex items-center space-x-2">
                <p class="hidden text-sm font-medium sm:block">Rows per page</p>
                <p class="text-sm font-medium sm:hidden">Per page</p>
                <Select
                    v-model:open="selectOpen"
                    :model-value="`${pagination.per_page}`"
                    @update:model-value="handlePageSizeChange"
                >
                    <SelectTrigger class="h-8 w-[80px] sm:w-[100px]">
                        <SelectValue :placeholder="`${pagination.per_page}`" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem
                            v-for="pageSize in [15, 25, 50, 100]"
                            :key="pageSize"
                            :value="`${pageSize}`"
                        >
                            {{ pageSize }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Navigation buttons -->
            <div class="flex items-center justify-center space-x-2">
                <!-- First page button - hidden on mobile -->
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
                    :disabled="pagination.current_page <= 1"
                    @click="handlePageChange(1)"
                >
                    <span class="sr-only">Go to first page</span>
                    <ChevronsLeft class="h-4 w-4" />
                </Button>

                <!-- Previous page button -->
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="pagination.current_page <= 1"
                    @click="handlePageChange(pagination.current_page - 1)"
                >
                    <span class="sr-only">Go to previous page</span>
                    <ChevronLeft class="h-4 w-4" />
                </Button>

                <!-- Page Numbers - Desktop -->
                <div class="hidden items-center space-x-1 md:flex">
                    <Button
                        v-for="page in pageNumbers"
                        :key="page"
                        variant="outline"
                        class="h-8 w-8 p-0"
                        :class="{
                            'border-primary bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-50 dark:text-slate-900 dark:hover:bg-slate-200':
                                page === pagination.current_page,
                        }"
                        @click="handlePageChange(page)"
                    >
                        {{ page }}
                    </Button>
                </div>

                <!-- Mobile: Show current page -->
                <div
                    class="flex items-center justify-center px-3 text-sm font-medium md:hidden"
                >
                    {{ pagination.current_page }} / {{ pagination.last_page }}
                </div>

                <!-- Next page button -->
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="!pagination.has_more_pages"
                    @click="handlePageChange(pagination.current_page + 1)"
                >
                    <span class="sr-only">Go to next page</span>
                    <ChevronRight class="h-4 w-4" />
                </Button>

                <!-- Last page button - hidden on mobile -->
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
                    :disabled="!pagination.has_more_pages"
                    @click="handlePageChange(pagination.last_page)"
                >
                    <span class="sr-only">Go to last page</span>
                    <ChevronsRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
