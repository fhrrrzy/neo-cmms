<script setup>
import { cn } from "@/lib/utils"

const props = defineProps({
  class: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <div
    data-slot="empty-title"
    :class="cn('text-lg font-medium tracking-tight', props.class)"
  >
    <slot />
  </div>
</template>
