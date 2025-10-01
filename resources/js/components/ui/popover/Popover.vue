<script setup lang="js">
import { ref, watch, provide, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: undefined }
});

const emit = defineEmits(['update:open']);

const internalOpen = ref(props.open ?? false);

watch(
  () => props.open,
  (val) => {
    if (typeof val === 'boolean') {
      internalOpen.value = val;
    }
  }
);

const setOpen = (value) => {
  internalOpen.value = value;
  emit('update:open', value);
};

const rootRef = ref();

const handleDocumentClick = (event) => {
  if (!internalOpen.value) return;
  const root = rootRef.value;
  if (!root) return;
  if (!root.contains(event.target)) {
    setOpen(false);
  }
};

const handleKeydown = (event) => {
  if (!internalOpen.value) return;
  if (event.key === 'Escape') {
    setOpen(false);
  }
};

onMounted(() => {
  document.addEventListener('mousedown', handleDocumentClick);
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('mousedown', handleDocumentClick);
  document.removeEventListener('keydown', handleKeydown);
});

provide('popover', {
  open: internalOpen,
  setOpen
});
</script>

<template>
  <div class="relative" ref="rootRef">
    <slot />
  </div>
</template>
