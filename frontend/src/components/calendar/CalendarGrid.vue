<template>
  <div class="glass-card rounded-[2rem] overflow-hidden border border-secondary-100 dark:border-secondary-800 shadow-premium">
    <!-- Weekday Headers -->
    <div class="grid grid-cols-7 bg-secondary-50/50 dark:bg-secondary-900/50 border-b border-secondary-100 dark:border-secondary-800">
      <div v-for="day in weekDays" :key="day" class="py-5 text-center text-[10px] font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em]">
        {{ day }}
      </div>
    </div>

    <!-- Calendar Cells -->
    <div v-if="!loading" class="grid grid-cols-7 auto-rows-fr">
      <div
        v-for="date in monthDates"
        :key="date.dateStr"
        class="border-r border-b border-secondary-100 dark:border-secondary-800 min-h-[140px] p-3 relative group hover:bg-secondary-50/50 dark:hover:bg-secondary-800/20 transition-all duration-300"
        :class="{
          'bg-secondary-50/30 dark:bg-secondary-900/20 opacity-40': !date.isCurrentMonth,
          'bg-primary-50/30 dark:bg-primary-900/10': date.isToday,
        }"
      >
        <!-- Date Number -->
        <div class="flex justify-between items-start mb-2">
           <span 
            class="inline-flex items-center justify-center w-8 h-8 text-sm font-black rounded-xl transition-all"
            :class="date.isToday 
              ? 'bg-primary-600 text-white shadow-lg shadow-primary-200 dark:shadow-none translate-x-1 -translate-y-1' 
              : 'text-secondary-600 dark:text-secondary-400'"
          >
            {{ date.day }}
          </span>
        </div>

        <!-- Events List -->
        <div class="space-y-1.5 overflow-y-auto max-h-[100px] custom-scrollbar-mini">
          <div
            v-for="event in getEventsForDate(date.dateStr)"
            :key="event.id"
            @click="$emit('view-event', event)"
            class="px-2.5 py-1.5 rounded-lg text-[10px] font-black truncate cursor-pointer transition-all hover:translate-x-1 active:scale-95 border shadow-sm"
            :class="getEventTypeStyles(event.event_type)"
            :title="event.title"
          >
            {{ event.title }}
          </div>
        </div>
      </div>
    </div>
    
    <!-- Skeleton Loading -->
    <div v-else class="grid grid-cols-7">
      <div v-for="n in 35" :key="n" class="border-r border-b border-secondary-100 dark:border-secondary-800 min-h-[140px] p-4 bg-white dark:bg-secondary-900">
        <div class="h-6 w-6 bg-secondary-50 dark:bg-secondary-800 rounded-lg animate-pulse"></div>
        <div class="mt-4 space-y-2">
          <div class="h-4 w-full bg-secondary-50/50 dark:bg-secondary-800/50 rounded-lg animate-pulse"></div>
          <div class="h-4 w-3/4 bg-secondary-50/50 dark:bg-secondary-800/50 rounded-lg animate-pulse"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  monthDates: Array,
  events: Array,
  loading: Boolean
})

defineEmits(['view-event'])

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const getEventsForDate = (dateStr) => {
  return props.events.filter(event => {
    const start = event.start_date
    const end = event.end_date
    return dateStr >= start && dateStr <= end
  })
}

const getEventTypeStyles = (type) => {
  switch (type) {
    case 'holiday': 
      return 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border-rose-100 dark:border-rose-800'
    case 'exam': 
      return 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800'
    case 'closure': 
      return 'bg-secondary-900 dark:bg-white text-white dark:text-secondary-900 border-secondary-900 dark:border-white'
    default: 
      return 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 border-primary-100 dark:border-primary-800'
  }
}
</script>

<style scoped>
.custom-scrollbar-mini::-webkit-scrollbar {
  width: 2px;
}
.custom-scrollbar-mini::-webkit-scrollbar-thumb {
  @apply bg-secondary-200 dark:bg-secondary-700 rounded-full;
}
</style>
