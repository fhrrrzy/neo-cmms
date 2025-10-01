<script setup lang="ts">
import { inject } from 'vue';

interface PopoverContext {
  open: boolean;
  setOpen: (open: boolean) => void;
}

const context = inject<PopoverContext>('popover');

const toggle = () => {
  if (context) {
    context.setOpen(!context.open);
  }
};
</script>

<template>
  <div @click="toggle">
    <slot />
  </div>
</template>
