<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">
          Welcome, <span class="text-primary-600 dark:text-primary-400">{{ data.user.name }}</span>
        </h2>
        <div class="flex items-center gap-3 mt-2">
          <BaseBadge variant="primary">{{ data.teacher.employee_id }}</BaseBadge>
          <span class="text-xs font-bold text-secondary-400 uppercase tracking-widest">
            {{ data.teacher.qualification }}
          </span>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <BaseButton variant="primary" size="lg" @click="$router.push('/attendance/mark')">
          <template #icon-left><ClipboardCheckIcon class="w-5 h-5" /></template>
          Mark New Attendance
        </BaseButton>
      </div>
    </div>

    <!-- Assignments Grid -->
    <div>
      <div class="flex items-center justify-between mb-4 px-1">
        <h3 class="text-xs font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em]">
          Assigned Academic Scope
        </h3>
        <span class="text-[10px] font-bold text-secondary-400 uppercase">
          {{ data.assignments.length }} ACTIVE MODULES
        </span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="assignment in data.assignments" 
          :key="assignment.id"
          class="glass-card p-6 rounded-3xl group hover:border-primary-300 dark:hover:border-primary-800 transition-all hover:shadow-premium-hover cursor-pointer"
          @click="$router.push({ path: '/attendance/mark', query: { grade_id: assignment.section.grade_id, section_id: assignment.section_id, subject_id: assignment.subject_id } })"
        >
          <div class="flex justify-between items-start mb-6">
             <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/40 rounded-2xl flex items-center justify-center text-primary-600 dark:text-primary-400 font-black border border-primary-200 dark:border-primary-800 text-sm shadow-sm">
               {{ assignment.subject.name.charAt(0) }}
             </div>
             <BaseBadge variant="neutral">{{ assignment.section.grade.name }}</BaseBadge>
          </div>
          <h4 class="text-xl font-black text-secondary-900 dark:text-white mb-1 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors tracking-tight">
            {{ assignment.subject.name }}
          </h4>
          <p class="text-xs font-bold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest">
            SECTION: {{ assignment.section.name }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Progress Table -->
      <div class="lg:col-span-2 glass-card rounded-3xl overflow-hidden self-start">
        <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex justify-between items-center bg-secondary-50/30 dark:bg-secondary-800/20">
          <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight italic">Today's Progress</h3>
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></div>
            <span class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-widest">LIVE TRACKING</span>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-left">Class / Subject</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-center">Enrolled</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-center">Status</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-right">Completion</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
              <tr v-for="summary in data.today_attendance" :key="summary.subject + summary.section" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
                <td class="px-8 py-5">
                  <p class="text-sm font-black text-secondary-800 dark:text-white">{{ summary.subject }}</p>
                  <p class="text-[10px] text-secondary-500 dark:text-secondary-400 font-bold uppercase tracking-tight">{{ summary.grade }} — {{ summary.section }}</p>
                </td>
                <td class="px-8 py-5 text-center font-black text-secondary-700 dark:text-secondary-300 text-sm italic">
                  {{ summary.total_students }}
                </td>
                <td class="px-8 py-5 text-center">
                   <div class="flex items-center justify-center gap-2">
                     <div class="w-2.5 h-2.5 rounded-full" :class="summary.present > 0 ? 'bg-emerald-500 shadow-sm shadow-emerald-200' : 'bg-secondary-300 dark:bg-secondary-700'"></div>
                     <span class="text-[10px] font-black uppercase tracking-tight" :class="summary.present > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-secondary-400'">
                       {{ summary.present > 0 ? 'Marked' : 'Pending' }}
                     </span>
                   </div>
                </td>
                <td class="px-8 py-5 text-right">
                   <div class="flex items-center justify-end gap-3">
                      <div class="w-24 bg-secondary-100 dark:bg-secondary-800 rounded-full h-2 overflow-hidden border border-secondary-200 dark:border-secondary-700">
                        <div 
                          class="bg-primary-600 dark:bg-primary-500 h-full rounded-full transition-all duration-1000" 
                          :style="{ width: (summary.total_students > 0 ? (summary.present / summary.total_students * 100) : 0) + '%' }"
                        ></div>
                      </div>
                      <span class="text-xs font-black text-secondary-700 dark:text-secondary-300 min-w-[4rem]">
                        {{ summary.present }} / {{ summary.total_students }}
                      </span>
                   </div>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div v-if="!data.today_attendance.length" class="p-16 text-center space-y-4">
             <div class="w-20 h-20 bg-secondary-50 dark:bg-secondary-800 rounded-3xl flex items-center justify-center mx-auto border border-secondary-100 dark:border-secondary-700 shadow-inner">
               <span class="text-4xl">📚</span>
             </div>
             <div>
               <p class="text-secondary-900 dark:text-white font-black text-sm">No assignments scheduled for today.</p>
               <p class="text-secondary-500 dark:text-secondary-400 text-xs mt-1">Enjoy your free period or check the weekly schedule.</p>
             </div>
          </div>
        </div>
      </div>

      <!-- Recent Log Activity -->
      <div class="glass-card rounded-3xl p-8 flex flex-col">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">Recent Activity</h3>
          <BellIcon class="w-5 h-5 text-secondary-400" />
        </div>
        
        <div class="space-y-8 flex-1">
          <div v-for="record in data.recent_attendance" :key="record.id" class="flex gap-4 items-start relative group">
            <div 
              class="w-12 h-12 rounded-2xl bg-secondary-50 dark:bg-secondary-800 border border-secondary-100 dark:border-secondary-700 flex-shrink-0 flex items-center justify-center transition-all group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:border-primary-200 dark:group-hover:border-primary-800 group-hover:shadow-sm"
              :class="statusColorClass(record.status).border"
            >
               <span class="text-xs font-black transition-colors uppercase leading-none" :class="statusColorClass(record.status).text">
                 {{ record.status.charAt(0) }}
               </span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-start">
                <p class="text-sm font-black text-secondary-800 dark:text-white truncate">{{ record.student_name }}</p>
                <span class="text-[10px] font-bold text-secondary-400 flex-shrink-0">{{ formatShortDate(record.date) }}</span>
              </div>
              <p class="text-[10px] font-bold text-secondary-500 dark:text-secondary-400 uppercase tracking-widest mt-0.5">{{ record.subject }}</p>
              <div class="mt-2 text-[10px]">
                <BaseBadge :variant="statusVariant(record.status)">{{ record.status }}</BaseBadge>
              </div>
            </div>
          </div>
        </div>
        
        <BaseButton variant="ghost" size="sm" block class="mt-8" @click="$router.push('/reports/attendance')">
          View Detailed Logs
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import { 
  ClipboardCheckIcon,
  BellIcon
} from '@heroicons/vue/outline'

const props = defineProps(['data'])

const formatShortDate = (dateStr) => {
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const statusVariant = (status) => {
  switch (status) {
    case 'present': return 'success'
    case 'absent': return 'danger'
    case 'late': return 'warning'
    case 'permission': return 'info'
    default: return 'neutral'
  }
}

const statusColorClass = (status) => {
  switch (status) {
    case 'present': return { text: 'text-emerald-600 dark:text-emerald-400', border: 'border-emerald-100 dark:border-emerald-800' }
    case 'absent': return { text: 'text-rose-600 dark:text-rose-400', border: 'border-rose-100 dark:border-rose-800' }
    case 'late': return { text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-100 dark:border-amber-800' }
    case 'permission': return { text: 'text-blue-600 dark:text-blue-400', border: 'border-blue-100 dark:border-blue-800' }
    default: return { text: 'text-secondary-400', border: 'border-secondary-100 dark:border-secondary-700' }
  }
}
</script>
