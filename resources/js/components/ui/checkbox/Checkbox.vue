<script setup lang="js">
import { cn } from '@/lib/utils'
import { Check } from 'lucide-vue-next'
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui'
import { computed } from 'vue'

const props = defineProps()
// Declare events to satisfy useForwardPropsEmits and devtools
const emits = defineEmits([
  'update:checked',
  'checked-change',
  'focus',
  'blur',
])

// Map model-value (shadcn-vue convention) to checked (reka-ui)
const controlledChecked = computed(() => {
  return props.modelValue !== undefined ? props.modelValue : props.checked
})

const delegatedProps = computed(() => {
  const { class: _class, modelValue, ...delegated } = props
  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <CheckboxRoot
    data-slot="checkbox"
    v-bind="forwarded"
    :checked="controlledChecked"
    @checked-change="(val) => { emits('checked-change', val); emits('update:checked', val); emits('update:model-value', val); }"
    :class="
      cn('peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
         props.class)"
  >
    <CheckboxIndicator
      data-slot="checkbox-indicator"
      class="flex items-center justify-center text-current transition-none"
    >
      <slot>
        <Check class="size-3.5" />
      </slot>
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
