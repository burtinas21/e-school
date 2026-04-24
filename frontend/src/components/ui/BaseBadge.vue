<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
    :class="[variantClasses[variant]]"
  >
    <slot></slot>
  </span>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'neutral'
  }
})

const variantClasses = {
  neutral: 'bg-secondary-100 text-secondary-600 dark:bg-secondary-800 dark:text-secondary-400',
  primary: 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-400',
  success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
  danger: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400',
  warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
  info: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
}
</script>
