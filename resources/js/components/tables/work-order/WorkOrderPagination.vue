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
</script>

<template>
    <div class="flex items-center justify-between px-2">
        <div class="flex-1 text-sm text-muted-foreground">
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
        <div class="flex items-center space-x-6 lg:space-x-8">
            <div class="flex items-center space-x-2">
                <p class="text-sm font-medium">Rows per page</p>
                <Select
                    v-model:open="selectOpen"
                    :model-value="`${pagination.per_page}`"
                    @update:model-value="handlePageSizeChange"
                >
                    <SelectTrigger class="h-8 w-[100px]">
                        <SelectValue :placeholder="`${pagination.per_page}`" />
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
            <div class="flex items-center space-x-2">
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
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

                <div
                    class="flex items-center justify-center px-2 text-sm font-medium md:hidden"
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
                    class="hidden h-8 w-8 p-0 lg:flex"
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
