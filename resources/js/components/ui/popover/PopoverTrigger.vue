<script setup lang="js">
import { inject } from 'vue';

const context = inject('popover');

const toggle = () => {
  if (context) {
    context.setOpen(!context.open?.value);
  }
};
</script>

<template>
  <div @click="toggle">
    <slot />
  </div>
</template>
