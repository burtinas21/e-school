<template>
  <div
    class="glass-card p-6 flex flex-col sm:flex-row items-start justify-between gap-6 group transition-all duration-300 hover:shadow-premium-hover relative overflow-hidden"
    :class="{ 'border-l-4 border-l-primary-600': !notification.read_at }"
  >
    <!-- Background highlight for unread -->
    <div 
      v-if="!notification.read_at" 
      class="absolute inset-0 bg-primary-50 dark:bg-primary-900/10 -z-10"
    ></div>

    <div class="flex-1 space-y-2">
      <div class="flex items-center gap-3">
        <h3 class="text-lg font-black text-secondary-900 dark:text-white tracking-tight">
          {{ notification.title }}
        </h3>
        <BaseBadge v-if="!notification.read_at" variant="primary">New</BaseBadge>
      </div>
      <p class="text-sm font-medium text-secondary-600 dark:text-secondary-400 leading-relaxed">
        {{ notification.message }}
      </p>
      <div class="flex items-center gap-2 pt-2 text-[10px] font-bold text-secondary-400 uppercase tracking-widest">
        <ClockIcon class="w-3 h-3" />
        {{ formatDate(notification.created_at) }}
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-2 w-full sm:w-auto">
      <BaseButton
        v-if="!notification.read_at"
        variant="ghost"
        size="sm"
        class="flex-1 sm:flex-none"
        @click="$emit('mark-read', notification.id)"
      >
        Mark Read
      </BaseButton>
      <BaseButton 
        variant="danger-ghost" 
        size="sm"
        class="flex-1 sm:flex-none"
        @click="$emit('delete', notification.id)"
      >
        <template #icon-left><TrashIcon class="w-4 h-4" /></template>
        Delete
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { ClockIcon, TrashIcon } from '@heroicons/vue/outline'

defineProps({
  notification: Object
})

defineEmits(['mark-read', 'delete'])

const formatDate = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}
</script>
