<template>
  <div :class="['space-y-1.5', $attrs.class]">
    <label 
      v-if="label" 
      :for="id" 
      class="block text-xs font-bold uppercase tracking-widest text-secondary-500 dark:text-secondary-400 ml-1"
    >
      {{ label }}
    </label>
    <div class="relative group">
      <div 
        v-if="$slots.icon" 
        class="absolute left-3 top-1/2 -translate-y-1/2 text-secondary-400 group-focus-within:text-primary-500 transition-colors"
      >
        <slot name="icon"></slot>
      </div>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        v-bind="$attrs"
        :class="[
          'w-full bg-secondary-50 border-secondary-100 rounded-xl px-4 py-3 text-sm font-semibold text-secondary-700 placeholder-secondary-400',
          'focus:ring-4 focus:ring-primary-100 focus:bg-white focus:border-primary-500 transition-all outline-none',
          'dark:bg-secondary-800 dark:border-secondary-700 dark:text-secondary-100 dark:focus:bg-secondary-900',
          $slots.icon ? 'pl-10' : '',
          error ? 'border-rose-500 focus:ring-rose-100' : ''
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
      />
    </div>
    <p v-if="error" class="text-xs font-bold text-rose-500 ml-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  error: String,
  disabled: Boolean
})
defineEmits(['update:modelValue'])
</script>

<script>
export default { inheritAttrs: false }
</script>
