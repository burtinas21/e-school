<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="flex items-center gap-6">
        <div class="w-20 h-20 rounded-3xl bg-secondary-900 dark:bg-primary-600 text-white flex items-center justify-center text-3xl font-black shadow-premium">
          {{ data.guardian.name.charAt(0) }}
        </div>
        <div>
          <h2 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Parental Dashboard</h2>
          <p class="text-xs font-bold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest mt-1">
            Registered: {{ data.guardian.name }} — {{ data.guardian.email }}
          </p>
        </div>
      </div>
      <div>
        <BaseButton variant="success" size="lg" shadow>
          Contact Administration
        </BaseButton>
      </div>
    </div>

    <!-- Children Monitoring Grid -->
    <div>
      <div class="flex items-center justify-between mb-8 px-1">
        <h3 class="text-xs font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em]">
          Child Progress Monitoring
        </h3>
        <span class="text-[10px] font-black text-primary-500 uppercase tracking-widest">
          {{ data.children.length }} Active Student{{ data.children.length > 1 ? 's' : '' }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div 
          v-for="child in data.children" 
          :key="child.id"
          class="glass-card rounded-[2.5rem] p-10 group relative transition-all duration-300 hover:shadow-premium-hover"
        >
          <!-- Corner Badge -->
          <div class="absolute top-6 right-6">
            <BaseBadge variant="neutral">{{ child.grade }} — {{ child.section }}</BaseBadge>
          </div>

          <!-- Header info -->
          <div class="flex items-center gap-6 mb-10">
            <div class="w-16 h-16 rounded-2xl bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold group-hover:bg-primary-600 group-hover:text-white transition-all shadow-sm">
              {{ child.name.charAt(0) }}
            </div>
            <div>
              <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">{{ child.name }}</h4>
              <p class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-[0.2em] mt-1">ROLL: {{ child.admission_no }}</p>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="grid grid-cols-2 gap-6 mb-10">
            <div class="p-8 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-3xl text-center border border-emerald-100 dark:border-emerald-800/30">
              <span class="block text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-2">Present Days</span>
              <span class="text-4xl font-black text-emerald-700 dark:text-emerald-500">{{ child.attendance.present }}</span>
            </div>
            <div class="p-8 bg-rose-50/50 dark:bg-rose-900/10 rounded-3xl text-center border border-rose-100 dark:border-rose-800/30">
              <span class="block text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Missed/Late</span>
              <span class="text-4xl font-black text-rose-700 dark:text-rose-500">{{ child.attendance.absent + child.attendance.late }}</span>
            </div>
          </div>

          <!-- Progress Bar Section -->
          <div class="space-y-3">
             <div class="flex justify-between items-end">
                <span class="text-xs font-black text-secondary-700 dark:text-secondary-300 uppercase tracking-widest">Attendance Health</span>
                <span class="text-sm font-black text-primary-600 dark:text-primary-400">{{ attendancePercentage(child) }}%</span>
             </div>
             <div class="w-full bg-secondary-100 dark:bg-secondary-800 rounded-full h-4 overflow-hidden border border-secondary-200 dark:border-secondary-700 p-1">
                <div 
                  class="bg-gradient-to-r from-primary-400 to-primary-600 h-full rounded-full transition-all duration-1000 shadow-sm" 
                  :style="{ width: attendancePercentage(child) + '%' }"
                ></div>
             </div>
             <p class="text-[10px] font-bold text-secondary-400 text-center uppercase tracking-widest">Calculated from total school sessions</p>
          </div>

          <!-- Actions -->
          <div class="mt-10 flex gap-4">
            <BaseButton 
              variant="primary" 
              block 
              size="lg"
              @click="$router.push(`/attendance/history`)"
            >
              Analyze Detailed Logs
            </BaseButton>
            <BaseButton variant="secondary" size="lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </BaseButton>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="!data.children.length" class="p-24 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
         <div class="text-6xl mb-6">🏘️</div>
         <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">No linked records found.</h4>
         <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto leading-relaxed">
           Your account is not currently linked to any student profiles. Please visit the school office to verify and link your parent account.
         </p>
         <BaseButton variant="outline" class="mt-8">Request Connection</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'

const props = defineProps(['data'])

const attendancePercentage = (child) => {
  const total = child.attendance.total
  if (total === 0) return 0
  return Math.round((child.attendance.present / total) * 100)
}
</script>
