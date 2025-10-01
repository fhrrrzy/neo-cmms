<script setup lang="ts">
import { ref, computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface Props {
  modelValue?: Date;
  from?: Date;
  to?: Date;
  locale?: any;
}

const props = withDefaults(defineProps<Props>(), {
  locale: () => ({
    code: 'id',
    weekStartsOn: 1,
    firstWeekContainsDate: 1,
    months: [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ],
    weekdays: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
  })
});

const emit = defineEmits<{
  'update:modelValue': [date: Date | undefined];
  'update:from': [date: Date | undefined];
  'update:to': [date: Date | undefined];
}>();

const currentDate = ref(new Date());
const selectedDate = ref(props.modelValue);

const currentMonth = computed(() => currentDate.value.getMonth());
const currentYear = computed(() => currentDate.value.getFullYear());

const daysInMonth = computed(() => {
  return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
  return new Date(currentYear.value, currentMonth.value, 1).getDay();
});

const calendarDays = computed(() => {
  const days = [];
  
  // Previous month days
  const prevMonth = new Date(currentYear.value, currentMonth.value - 1, 0);
  const daysInPrevMonth = prevMonth.getDate();
  
  for (let i = firstDayOfMonth.value - 1; i >= 0; i--) {
    days.push({
      date: new Date(currentYear.value, currentMonth.value - 1, daysInPrevMonth - i),
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      isInRange: false
    });
  }
  
  // Current month days
  for (let day = 1; day <= daysInMonth.value; day++) {
    const date = new Date(currentYear.value, currentMonth.value, day);
    const isToday = isSameDay(date, new Date());
    const isSelected = props.modelValue ? isSameDay(date, props.modelValue) : false;
    const isInRange = isDateInRange(date);
    
    days.push({
      date,
      isCurrentMonth: true,
      isToday,
      isSelected,
      isInRange
    });
  }
  
  // Next month days
  const remainingDays = 42 - days.length; // 6 weeks * 7 days
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(currentYear.value, currentMonth.value + 1, day);
    days.push({
      date,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      isInRange: false
    });
  }
  
  return days;
});

const isSameDay = (date1: Date, date2: Date) => {
  return date1.getDate() === date2.getDate() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getFullYear() === date2.getFullYear();
};

const isDateInRange = (date: Date) => {
  if (props.from && props.to) {
    return date >= props.from && date <= props.to;
  }
  return false;
};

const selectDate = (date: Date) => {
  selectedDate.value = date;
  emit('update:modelValue', date);
  
  if (props.from && !props.to) {
    if (date < props.from) {
      emit('update:from', date);
      emit('update:to', props.from);
    } else {
      emit('update:to', date);
    }
  } else if (!props.from) {
    emit('update:from', date);
  } else {
    emit('update:from', date);
    emit('update:to', undefined);
  }
};

const previousMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
};

const nextMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
};

const goToToday = () => {
  currentDate.value = new Date();
  selectedDate.value = new Date();
  emit('update:modelValue', new Date());
};
</script>

<template>
  <div class="rounded-md border p-3">
    <div class="flex items-center justify-between mb-4">
      <Button variant="outline" size="sm" @click="previousMonth">
        <ChevronLeft class="h-4 w-4" />
      </Button>
      <div class="text-sm font-medium">
        {{ props.locale.months[currentMonth] }} {{ currentYear }}
      </div>
      <Button variant="outline" size="sm" @click="nextMonth">
        <ChevronRight class="h-4 w-4" />
      </Button>
    </div>
    
    <div class="grid grid-cols-7 gap-1 mb-2">
      <div
        v-for="day in props.locale.weekdays"
        :key="day"
        class="text-center text-xs font-medium text-muted-foreground p-2"
      >
        {{ day }}
      </div>
    </div>
    
    <div class="grid grid-cols-7 gap-1">
      <Button
        v-for="day in calendarDays"
        :key="day.date.toISOString()"
        variant="ghost"
        size="sm"
        :class="cn(
          'h-8 w-8 p-0 text-xs',
          !day.isCurrentMonth && 'text-muted-foreground',
          day.isToday && 'bg-primary text-primary-foreground',
          day.isSelected && 'bg-primary text-primary-foreground',
          day.isInRange && 'bg-primary/20',
          day.isCurrentMonth && 'hover:bg-accent'
        )"
        @click="selectDate(day.date)"
      >
        {{ day.date.getDate() }}
      </Button>
    </div>
    
    <div class="mt-4 pt-2 border-t">
      <Button variant="outline" size="sm" class="w-full" @click="goToToday">
        Hari Ini
      </Button>
    </div>
  </div>
</template>
