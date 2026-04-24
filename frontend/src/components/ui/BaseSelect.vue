<template>
  <div :class="['space-y-1.5', $attrs.class]">
    <label 
      v-if="label" 
      :for="id" 
      class="block text-xs font-bold uppercase tracking-widest text-secondary-500 dark:text-secondary-400 ml-1"
    >
      {{ label }}
    </label>
    <select
      :id="id"
      :value="modelValue"
      :disabled="disabled"
      v-bind="$attrs"
      :class="[
        'w-full bg-secondary-50 border-secondary-100 rounded-xl px-4 py-3 text-sm font-semibold text-secondary-700 appearance-none',
        'focus:ring-4 focus:ring-primary-100 focus:bg-white focus:border-primary-500 transition-all outline-none',
        'dark:bg-secondary-800 dark:border-secondary-700 dark:text-secondary-100 dark:focus:bg-secondary-900',
        'bg-[url(\'data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%236b7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22m6%208%204%204%204-4%22%2F%3E%3C%2Fsvg%3E\')] bg-[length:1.25rem_1.25rem] bg-[right_0.75rem_center] bg-no-repeat',
        error ? 'border-rose-500 focus:ring-rose-100' : ''
      ]"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <slot></slot>
    </select>
    <p v-if="error" class="text-xs font-bold text-rose-500 ml-1">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  id: { type: String, default: () => `select-${Math.random().toString(36).substr(2, 9)}` },
  error: String,
  disabled: Boolean
})
defineEmits(['update:modelValue'])
</script>

<script>
export default { inheritAttrs: false }
</script>
