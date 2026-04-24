<template>
  <div class="flex flex-col items-center justify-center p-8 space-y-4">
    <div 
      class="animate-spin rounded-full border-4 border-primary-100 border-t-primary-600 dark:border-secondary-800 dark:border-t-primary-500"
      :class="sizeClasses[size]"
    ></div>
    <p v-if="text" class="text-sm font-bold text-secondary-500 dark:text-secondary-400 animate-pulse">
      {{ text }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  size: {
    type: String,
    default: 'md'
  },
  text: {
    type: String,
    default: 'Loading...'
  }
})

const sizeClasses = {
  sm: 'h-6 w-6 border-2',
  md: 'h-10 w-10 border-4',
  lg: 'h-16 w-16 border-8'
}
</script>
