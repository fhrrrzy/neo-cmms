<script setup lang="js">
import { ComboboxRoot, useForwardPropsEmits } from "reka-ui"

const props = defineProps()
const emits = defineEmits()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <ComboboxRoot
    data-slot="combobox"
    v-bind="forwarded"
  >
    <slot />
  </ComboboxRoot>
</template>
