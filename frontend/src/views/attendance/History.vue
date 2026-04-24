<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Attendance History</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Review your comprehensive record of presence and academic engagement.</p>
      </div>
      
      <!-- Stats Insight -->
      <div v-if="history.length" class="flex items-center gap-4">
        <div class="px-4 py-2 bg-primary-50 dark:bg-primary-900/20 rounded-xl border border-primary-100 dark:border-primary-800">
          <span class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-widest block">Total Records</span>
          <span class="text-lg font-black text-primary-700 dark:text-primary-300">{{ history.length }}</span>
        </div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="glass-card rounded-[2.5rem] overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="p-20">
        <LoadingSpinner text="Retrieving historical records..." />
      </div>

      <!-- No Data State -->
      <div v-else-if="!history.length" class="p-20 text-center">
        <div class="w-24 h-24 bg-secondary-50 dark:bg-secondary-800 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
          <ClockIcon class="w-10 h-10 text-secondary-300 dark:text-secondary-600" />
        </div>
        <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">No Records Yet</h4>
        <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto">
          We couldn't find any attendance logs for your profile. Records will appear here once marked by a teacher.
        </p>
      </div>

      <!-- Table Content -->
      <div v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Session Date</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Subject / Grade</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Academic Period</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-center">Final Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
              <tr v-for="record in history" :key="record.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-secondary-100 dark:bg-secondary-800 rounded-xl text-secondary-500 dark:text-secondary-400">
                      <CalendarIcon class="w-4 h-4" />
                    </div>
                    <div>
                      <p class="text-sm font-black text-secondary-900 dark:text-white leading-none mb-1">
                        {{ formatDate(record.date) }}
                      </p>
                      <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest">
                        {{ record.date }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <p class="text-sm font-black text-secondary-800 dark:text-white leading-none mb-1">
                    {{ record.subject?.name || 'N/A' }}
                  </p>
                  <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest">
                    {{ record.grade?.name }} — {{ record.section?.name }}
                  </p>
                </td>
                <td class="px-8 py-5">
                  <div class="flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-secondary-300" />
                    <span class="text-sm font-bold text-secondary-600 dark:text-secondary-400">
                      {{ record.period?.name || 'N/A' }}
                    </span>
                  </div>
                </td>
                <td class="px-8 py-5 text-center">
                  <BaseBadge :variant="statusVariant(record.status)">
                    {{ record.status }}
                  </BaseBadge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Local Pagination (since backend doesn't support it for history yet) -->
        <div class="px-8 py-6 border-t border-secondary-100 dark:border-secondary-800 flex items-center justify-between">
          <p class="text-xs font-bold text-secondary-400 uppercase tracking-widest">
            Showing {{ history.length }} entries
          </p>
          <div class="flex gap-2">
            <BaseButton variant="outline" size="xs" disabled>Previous</BaseButton>
            <BaseButton variant="outline" size="xs" disabled>Next</BaseButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAttendanceStore } from '@/stores/attendance'
import { useStudentsStore } from '@/stores/students'

// UI Components
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { CalendarIcon, ClockIcon } from '@heroicons/vue/outline'

const authStore = useAuthStore()
const attendanceStore = useAttendanceStore()
const studentsStore = useStudentsStore()

const loading = computed(() => attendanceStore.loading)
const history = computed(() => attendanceStore.studentHistory)

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', { 
    weekday: 'short',
    month: 'long', 
    day: 'numeric', 
    year: 'numeric' 
  })
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

onMounted(async () => {
  if (!authStore.user) await authStore.fetchUser()
  
  // Get the student record for the logged-in user if needed
  if (authStore.userRole === 3) { // Student
    await studentsStore.fetchStudents()
    const student = studentsStore.students.find(s => s.user_id === authStore.user?.id)
    if (student) {
      await attendanceStore.fetchStudentHistory(student.id)
    }
  } else if (authStore.userRole === 4) { // Guardian / Parent logic could be added here if they view a specific child
     // Assuming history is for the user's primary context
  } else {
     // Admin/Teacher might be viewing a specific student passed via route or shared state
     // For now keeping it simple as per original
  }
})
</script>