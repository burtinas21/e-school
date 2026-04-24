<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="flex items-center gap-6">
        <div class="w-20 h-20 rounded-3xl bg-primary-600 dark:bg-primary-500 text-white flex items-center justify-center text-3xl font-black shadow-premium shadow-primary-200 dark:shadow-none transition-transform hover:rotate-3">
          {{ data.user.name.charAt(0) }}
        </div>
        <div>
          <h2 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">
            Hi, <span class="text-primary-600 dark:text-primary-400">{{ data.user.name }}</span>
          </h2>
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <BaseBadge variant="neutral">{{ data.student.admission_number }}</BaseBadge>
            <span class="text-secondary-300">/</span>
            <span class="text-xs font-black text-secondary-500 dark:text-secondary-400 uppercase tracking-widest">
              {{ data.student.grade }} — {{ data.student.section }}
            </span>
          </div>
        </div>
      </div>
      <div>
        <BaseButton variant="outline" @click="$router.push(`/attendance/history`)">
          View Full Log History
        </BaseButton>
      </div>
    </div>

    <!-- Attendance Performance -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Circular Progress Card -->
      <div class="glass-card p-8 rounded-3xl flex flex-col items-center justify-center group hover:shadow-premium-hover transition-all">
         <div class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-6">Attendance Score</div>
         <div class="relative w-32 h-32 flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90">
              <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" class="text-secondary-100 dark:text-secondary-800" />
              <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" 
                class="text-primary-600 dark:text-primary-500 transition-all duration-1000 ease-out" 
                :stroke-dasharray="2 * Math.PI * 56" 
                :stroke-dashoffset="2 * Math.PI * 56 * (1 - attendancePercentage / 100)" 
                stroke-linecap="round"
              />
            </svg>
            <div class="absolute flex flex-col items-center">
              <span class="text-3xl font-black text-secondary-900 dark:text-white tracking-tighter">{{ attendancePercentage }}%</span>
              <span class="text-[8px] font-black text-secondary-400 uppercase tracking-widest">Efficiency</span>
            </div>
         </div>
      </div>

      <div class="glass-card p-8 rounded-3xl flex flex-col items-center justify-center bg-emerald-50/50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-800/30">
         <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-2">Days Present</p>
         <p class="text-5xl font-black text-emerald-700 dark:text-emerald-500 leading-none">{{ data.attendance_summary.present }}</p>
         <p class="text-[10px] font-bold text-emerald-500/60 mt-2 uppercase">Current Month</p>
      </div>
      
      <div class="glass-card p-8 rounded-3xl flex flex-col items-center justify-center bg-rose-50/50 dark:bg-rose-900/10 border-rose-100 dark:border-rose-800/30">
         <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Days Absent</p>
         <p class="text-5xl font-black text-rose-700 dark:text-rose-500 leading-none">{{ data.attendance_summary.absent }}</p>
         <p class="text-[10px] font-bold text-rose-500/60 mt-2 uppercase">Action Required</p>
      </div>

      <div class="glass-card p-8 rounded-3xl flex flex-col items-center justify-center bg-amber-50/50 dark:bg-amber-900/10 border-amber-100 dark:border-amber-800/30">
         <p class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest mb-2">Arrival Delay</p>
         <p class="text-5xl font-black text-amber-700 dark:text-amber-500 leading-none">{{ data.attendance_summary.late }}</p>
         <p class="text-[10px] font-bold text-amber-500/60 mt-2 uppercase">Lateness count</p>
      </div>
    </div>

    <!-- Weekly Visualization -->
    <div class="glass-card rounded-3xl p-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
         <div>
           <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">Weekly Roadmap</h3>
           <p class="text-xs text-secondary-500 dark:text-secondary-400 font-bold uppercase tracking-widest mt-1">Status overview for the last 7 sessions</p>
         </div>
         <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
              <span class="text-[10px] font-black uppercase text-secondary-400">Present</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-rose-500"></div>
              <span class="text-[10px] font-black uppercase text-secondary-400">Absent</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-amber-500"></div>
              <span class="text-[10px] font-black uppercase text-secondary-400">Late</span>
            </div>
         </div>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-4 md:grid-cols-7 gap-4">
         <div v-for="day in data.attendance_trend" :key="day.date" class="relative group">
            <div class="mb-4 text-center">
               <p class="text-[10px] font-black text-secondary-800 dark:text-white uppercase tracking-tighter">{{ formatDay(day.date) }}</p>
               <p class="text-[9px] font-bold text-secondary-400 uppercase tracking-widest">{{ formatDate(day.date) }}</p>
            </div>
            <div 
              class="h-24 w-full rounded-2xl border-2 flex items-center justify-center transition-all group-hover:shadow-lg relative overflow-hidden" 
              :class="trendBoxStyle(day.status)"
            >
               <div class="flex flex-col items-center gap-1">
                  <span class="text-lg" v-if="day.status === 'present'">✅</span>
                  <span class="text-lg" v-else-if="day.status === 'absent'">❌</span>
                  <span class="text-lg" v-else-if="day.status === 'late'">⏱️</span>
                  <span class="text-lg" v-else>🌑</span>
                  <span class="text-[8px] font-black uppercase tracking-tighter opacity-0 group-hover:opacity-100 transition-opacity">
                    {{ day.status || 'No Data' }}
                  </span>
               </div>
            </div>
         </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'

const props = defineProps(['data'])

const attendancePercentage = computed(() => {
  const summary = props.data.attendance_summary
  if (summary.total === 0) return 0
  return Math.round((summary.present / summary.total) * 100)
})

const formatDay = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long' })
}

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', { day: 'numeric', month: 'short' })
}

const trendBoxStyle = (status) => {
  switch (status) {
    case 'present': 
      return 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800/50 text-emerald-600 dark:text-emerald-400'
    case 'absent': 
      return 'bg-rose-50 dark:bg-rose-900/20 border-rose-100 dark:border-rose-800/50 text-rose-600 dark:text-rose-400'
    case 'late': 
      return 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800/50 text-amber-600 dark:text-amber-400'
    case 'permission': 
      return 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800/50 text-blue-600 dark:text-blue-400'
    default: 
      return 'bg-secondary-50 dark:bg-secondary-900/20 border-secondary-100 dark:border-secondary-800/50 text-secondary-300 dark:text-secondary-600'
  }
}
</script>
