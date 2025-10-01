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
import { ref } from 'vue';

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
</script>

<template>
    <div class="flex items-center justify-between px-2">
        <div class="flex-1 text-sm text-muted-foreground">
            Showing {{ pagination.from }} to {{ pagination.to }} of
            {{ pagination.total }} results
        </div>
        <div class="flex items-center space-x-6 lg:space-x-8">
            <div class="flex items-center space-x-2">
                <p class="text-sm font-medium">Rows per page</p>
                <Select
                    v-model:open="selectOpen"
                    :model-value="`${pagination.per_page}`"
                    @update:model-value="handlePageSizeChange"
                >
                    <SelectTrigger class="h-8 w-[70px]">
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
            <div
                class="flex w-[100px] items-center justify-center text-sm font-medium"
            >
                Page {{ pagination.current_page }} of {{ pagination.last_page }}
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
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="!pagination.has_more_pages"
                    @click="handlePageChange(pagination.current_page + 1)"
                >
                    <span class="sr-only">Go to next page</span>
                    <ChevronRight class="h-4 w-4" />
                </Button>
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
