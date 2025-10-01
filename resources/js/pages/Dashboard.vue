<script setup>
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';
import { DateFormatter, getLocalTimeZone } from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
});

const value = ref();
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Demo: Working Popover + Calendar -->
            <div class="flex items-center justify-start">
                <template>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="[
                                    'w-[280px] justify-start text-left font-normal',
                                    !value ? 'text-muted-foreground' : '',
                                ]"
                            >
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{
                                    value
                                        ? df.format(
                                              value.toDate(getLocalTimeZone()),
                                          )
                                        : 'Pick a date'
                                }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <Calendar v-model="value" initial-focus />
                        </PopoverContent>
                    </Popover>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
