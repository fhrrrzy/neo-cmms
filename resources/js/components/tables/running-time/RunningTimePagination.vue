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
    <div class="space-y-4 px-2">
        <!-- Mobile: Compact view -->
        <div class="flex flex-col space-y-3 sm:hidden">
            <!-- Results info -->
            <div class="text-center text-xs text-muted-foreground">
                {{
                    pagination.from ||
                    (pagination.current_page - 1) * pagination.per_page + 1
                }}-{{
                    pagination.to ||
                    Math.min(
                        pagination.current_page * pagination.per_page,
                        pagination.total,
                    )
                }}
                of {{ pagination.total }}
            </div>

            <!-- Page size selector -->
            <div class="flex items-center justify-center space-x-2">
                <p class="text-xs font-medium">Per page:</p>
                <Select
                    v-model:open="selectOpen"
                    :model-value="`${pagination.per_page}`"
                    @update:model-value="handlePageSizeChange"
                >
                    <SelectTrigger class="h-7 w-16 text-xs">
                        <SelectValue :placeholder="`${pagination.per_page}`" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem
                            v-for="pageSize in [10, 15, 25, 50]"
                            :key="pageSize"
                            :value="`${pageSize}`"
                            class="text-xs"
                        >
                            {{ pageSize }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-center space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-7 w-7 p-0"
                    :disabled="pagination.current_page <= 1"
                    @click="handlePageChange(pagination.current_page - 1)"
                >
                    <span class="sr-only">Previous</span>
                    <ChevronLeft class="h-3 w-3" />
                </Button>

                <div
                    class="flex items-center justify-center px-3 py-1 text-xs font-medium"
                >
                    {{ pagination.current_page }} / {{ pagination.last_page }}
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    class="h-7 w-7 p-0"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="handlePageChange(pagination.current_page + 1)"
                >
                    <span class="sr-only">Next</span>
                    <ChevronRight class="h-3 w-3" />
                </Button>
            </div>
        </div>

        <!-- Tablet and Desktop: Full view -->
        <div class="hidden flex-col space-y-4 sm:flex">
            <!-- Top row: Results info and page size -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing
                    {{
                        pagination.from ||
                        (pagination.current_page - 1) * pagination.per_page + 1
                    }}
                    to
                    {{
                        pagination.to ||
                        Math.min(
                            pagination.current_page * pagination.per_page,
                            pagination.total,
                        )
                    }}
                    of {{ pagination.total }} results
                </div>
                <div class="flex items-center space-x-2">
                    <p class="text-sm font-medium">Rows per page</p>
                    <Select
                        v-model:open="selectOpen"
                        :model-value="`${pagination.per_page}`"
                        @update:model-value="handlePageSizeChange"
                    >
                        <SelectTrigger class="h-8 w-[100px]">
                            <SelectValue
                                :placeholder="`${pagination.per_page}`"
                            />
                        </SelectTrigger>
                        <SelectContent side="top">
                            <SelectItem
                                v-for="pageSize in [10, 15, 25, 50, 100]"
                                :key="pageSize"
                                :value="`${pageSize}`"
                            >
                                {{ pageSize }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Bottom row: Navigation -->
            <div class="flex items-center justify-center space-x-1">
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 md:flex"
                    :disabled="pagination.current_page <= 1"
                    @click="handlePageChange(1)"
                >
                    <span class="sr-only">Go to first page</span>
                    <ChevronsLeft class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="pagination.current_page <= 1"
                    @click="handlePageChange(pagination.current_page - 1)"
                >
                    <span class="sr-only">Go to previous page</span>
                    <ChevronLeft class="h-4 w-4" />
                </Button>

                <!-- Page Numbers - Show on tablet and up -->
                <div class="hidden items-center space-x-1 lg:flex">
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

                <!-- Tablet: Show current page -->
                <div
                    class="flex items-center justify-center px-2 text-sm font-medium lg:hidden"
                >
                    Page {{ pagination.current_page }} of
                    {{ pagination.last_page }}
                </div>

                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="handlePageChange(pagination.current_page + 1)"
                >
                    <span class="sr-only">Go to next page</span>
                    <ChevronRight class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 md:flex"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="handlePageChange(pagination.last_page)"
                >
                    <span class="sr-only">Go to last page</span>
                    <ChevronsRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
