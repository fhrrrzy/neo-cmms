<script setup lang="ts">
import { cn } from '@/lib/utils'
import { X } from 'lucide-vue-next'
import {
  DialogClose,
  DialogContent,
  type DialogContentEmits,
  type DialogContentProps,
  DialogPortal,
  useForwardPropsEmits,
} from 'reka-ui'
import { computed, type HTMLAttributes, onMounted, onUnmounted } from 'vue'
import DialogOverlay from './DialogOverlay.vue'

const props = defineProps<DialogContentProps & {
  class?: HTMLAttributes['class']
  forceZIndex?: number
}>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

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

const zIndexContent = computed(() => props.forceZIndex || (50 + (stackLevel * 10) + 5))
</script>

<template>
  <DialogPortal>
    <DialogOverlay :style="{ zIndex: props.forceZIndex ? props.forceZIndex - 1 : zIndexContent - 10 }" />
    <DialogContent data-slot="dialog-content" v-bind="forwarded" :style="{ zIndex: zIndexContent }" :class="cn(
      'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border p-6 shadow-lg duration-200 sm:max-w-lg',
      props.class,
    )">
      <slot />

      <DialogClose
        class="ring-offset-background  focus:ring-ring data-[state=open]:bg-accent data-[state=open]:text-muted-foreground absolute top-4 right-4 rounded opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4">
        <X />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
