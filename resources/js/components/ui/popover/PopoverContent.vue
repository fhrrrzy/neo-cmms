<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
  class?: string;
  side?: 'top' | 'right' | 'bottom' | 'left';
  align?: 'start' | 'center' | 'end';
}

const props = withDefaults(defineProps<Props>(), {
  side: 'bottom',
  align: 'center'
});

const contentRef = ref<HTMLDivElement>();

onMounted(() => {
  if (contentRef.value) {
    contentRef.value.style.position = 'absolute';
    contentRef.value.style.zIndex = '50';
  }
});
</script>

<template>
  <div
    ref="contentRef"
    :class="cn(
      'z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md',
      props.class
    )"
  >
    <slot />
  </div>
</template>
