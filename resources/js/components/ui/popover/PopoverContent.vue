<script setup lang="js">
import { ref, inject, computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: String, default: undefined },
  side: { type: String, default: 'bottom' },
  align: { type: String, default: 'center' }
});

const context = inject('popover');
const isOpen = computed(() => !!context?.open?.value);

const contentRef = ref();

const sideClasses = computed(() => {
  switch (props.side) {
    case 'top':
      return 'bottom-full mb-2';
    case 'right':
      return 'left-full ml-2 top-0';
    case 'left':
      return 'right-full mr-2 top-0';
    case 'bottom':
    default:
      return 'top-full mt-2';
  }
});

const alignClasses = computed(() => {
  if (props.side === 'left' || props.side === 'right') {
    if (props.align === 'start') return 'top-0';
    if (props.align === 'end') return 'bottom-0';
    return 'top-1/2 -translate-y-1/2';
  }
  // top or bottom
  if (props.align === 'start') return 'left-0';
  if (props.align === 'end') return 'right-0';
  return 'left-1/2 -translate-x-1/2';
});
</script>

<template>
  <div v-if="isOpen"
    ref="contentRef"
    :class="cn(
      'absolute z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md',
      sideClasses,
      alignClasses,
      props.class
    )"
  >
    <slot />
  </div>
</template>
