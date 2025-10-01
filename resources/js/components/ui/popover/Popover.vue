<script setup lang="ts">
import { ref, provide, inject } from 'vue';
import { cn } from '@/lib/utils';

interface PopoverContext {
  open: boolean;
  setOpen: (open: boolean) => void;
}

const open = ref(false);
const setOpen = (value: boolean) => {
  open.value = value;
};

provide<PopoverContext>('popover', {
  open: open.value,
  setOpen
});
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template>
