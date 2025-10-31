<script lang="ts" setup>
import type { DialogOverlayProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { DrawerOverlay } from "vaul-vue"
import { cn } from "@/lib/utils"
import { computed, onMounted, onUnmounted } from "vue"

const props = defineProps<DialogOverlayProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

// Dynamic stacking, aligned with Dialog/Sheet
let stackLevel = 0

const getStackLevel = () => {
  if (typeof window === 'undefined') return 0
  return parseInt(document.body.getAttribute('data-overlay-stack') || '0', 10)
}
const incrementStack = () => {
  stackLevel = getStackLevel() + 1
  document.body.setAttribute('data-overlay-stack', String(stackLevel))
  return stackLevel
}
const decrementStack = () => {
  const current = getStackLevel()
  if (current > 0) document.body.setAttribute('data-overlay-stack', String(current - 1))
}

onMounted(() => { incrementStack() })
onUnmounted(() => { decrementStack() })

const zIndexOverlay = computed(() => 50 + (getStackLevel() * 10))
</script>

<template>
  <DrawerOverlay
    data-slot="drawer-overlay"
    v-bind="delegatedProps"
    :style="{ zIndex: zIndexOverlay }"
    :class="cn('data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 bg-black/80', props.class)"
  />
</template>
