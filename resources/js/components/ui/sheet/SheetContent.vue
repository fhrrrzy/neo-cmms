<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import type { SheetVariants } from "."
import { reactiveOmit } from "@vueuse/core"
import { X } from "lucide-vue-next"
import {
  DialogClose,
  DialogContent,

  DialogOverlay,
  DialogPortal,
  useForwardPropsEmits,
} from "reka-ui"
import { cn } from "@/lib/utils"
import { sheetVariants } from "."
import { computed, onMounted, onUnmounted } from "vue"

interface SheetContentProps extends DialogContentProps {
  class?: HTMLAttributes["class"]
  side?: SheetVariants["side"]
  hideClose?: boolean
}

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<SheetContentProps>()

const emits = defineEmits<DialogContentEmits>()

const delegatedProps = reactiveOmit(props, "class", "side", "hideClose")

const forwarded = useForwardPropsEmits(delegatedProps, emits)

// Track overlay stack level for proper z-index stacking
let stackLevel = 0

if (typeof window !== 'undefined') {
  // Get current stack level from body attribute
  const getStackLevel = () => {
    return parseInt(document.body.getAttribute('data-overlay-stack') || '0', 10)
  }

  // Increment stack level
  const incrementStack = () => {
    stackLevel = getStackLevel() + 1
    document.body.setAttribute('data-overlay-stack', String(stackLevel))
    return stackLevel
  }

  // Decrement stack level
  const decrementStack = () => {
    const current = getStackLevel()
    if (current > 0) {
      document.body.setAttribute('data-overlay-stack', String(current - 1))
    }
  }

  onMounted(() => {
    stackLevel = incrementStack()
  })

  onUnmounted(() => {
    decrementStack()
  })
}

const zIndexOverlay = computed(() => 50 + (stackLevel * 10))
const zIndexContent = computed(() => 50 + (stackLevel * 10) + 5)
</script>

<template>
  <DialogPortal>
    <DialogOverlay :style="{ zIndex: zIndexOverlay }"
      class="fixed inset-0 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
    <DialogContent :style="{ zIndex: zIndexContent }" :class="cn(sheetVariants({ side }), props.class)"
      v-bind="{ ...forwarded, ...$attrs }">
      <slot />

      <DialogClose v-if="!hideClose"
        class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-secondary">
        <X class="w-4 h-4 text-muted-foreground" />
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
