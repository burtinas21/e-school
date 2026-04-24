<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">System Overview</h2>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Real-time snapshots of school operations and attendance metrics.</p>
      </div>
      <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800">
        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
        <span class="text-xs font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">System Live</span>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard 
        title="Total Students" 
        :value="data.total_students" 
        :icon="UsersIcon"
        variant="primary"
      />
      <StatCard 
        title="Faculty Members" 
        :value="data.total_teachers" 
        :icon="UserGroupIcon"
        variant="success"
      />
      <StatCard 
        title="Active Sections" 
        :value="data.total_sections" 
        :icon="AcademicCapIcon"
        variant="warning"
      />
      <StatCard 
        title="Today's Attendance" 
        :value="data.today_attendance" 
        :icon="CheckCircleIcon"
        variant="danger"
      />
    </div>

    <!-- Charts and Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 glass-card p-8 rounded-3xl">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">Attendance Trend</h3>
            <p class="text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mt-1">Last 5 Operational Days</p>
          </div>
          <select class="bg-secondary-50 dark:bg-secondary-800 border-none rounded-xl text-xs font-bold px-4 py-2 outline-none">
            <option>Last 7 Days</option>
            <option>Last 30 Days</option>
          </select>
        </div>
        <div class="h-[350px]">
          <LinearChart :data="chartData" />
        </div>
      </div>

      <div class="glass-card p-8 rounded-3xl flex flex-col justify-between">
        <div class="space-y-6">
          <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">Quick Actions</h3>
          <div class="grid grid-cols-1 gap-3">
            <BaseButton variant="primary" block @click="$router.push('/students')">
              <template #icon-left><UsersIcon class="w-4 h-4" /></template>
              Manage Students
            </BaseButton>
            <BaseButton variant="secondary" block @click="$router.push('/attendance/mark')">
              <template #icon-left><ClipboardCheckIcon class="w-4 h-4" /></template>
              Mark Attendance
            </BaseButton>
            <BaseButton variant="outline" block @click="$router.push('/reports/attendance')">
              <template #icon-left><ChartBarIcon class="w-4 h-4" /></template>
              Generate Report
            </BaseButton>
          </div>
        </div>
        
        <div class="mt-8 p-6 bg-primary-50 dark:bg-primary-900/20 rounded-2xl border border-primary-100 dark:border-primary-800">
           <h4 class="text-xs font-black text-primary-700 dark:text-primary-400 uppercase tracking-widest mb-2">Need Help?</h4>
           <p class="text-xs text-primary-600 dark:text-primary-400 font-medium leading-relaxed">
             Access the administrator guide or contact support for advanced system settings.
           </p>
        </div>
      </div>
    </div>

    <!-- Recent Records Table -->
    <div class="glass-card rounded-3xl overflow-hidden">
      <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex justify-between items-center">
        <div>
          <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">Recent Roll Calls</h3>
          <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mt-1">Latest activity across all grades</p>
        </div>
        <BaseButton variant="ghost" size="sm" @click="$router.push('/reports/attendance')">
          View All Records
        </BaseButton>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-secondary-50/50 dark:bg-secondary-800/50">
              <th class="px-8 py-4 text-[10px] font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em] text-left">Date</th>
              <th class="px-8 py-4 text-[10px] font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em] text-left">Student</th>
              <th class="px-8 py-4 text-[10px] font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em] text-left">Subject</th>
              <th class="px-8 py-4 text-[10px] font-black text-secondary-400 dark:text-secondary-500 uppercase tracking-[0.2em] text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <tr v-for="record in data.recent_attendance" :key="record.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
              <td class="px-8 py-5 text-sm font-bold text-secondary-600 dark:text-secondary-400">
                {{ formatDate(record.date) }}
              </td>
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-xs font-black text-secondary-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600 transition-all border border-secondary-200 dark:border-secondary-700">
                    {{ record.student_name.charAt(0) }}
                  </div>
                  <span class="text-sm font-black text-secondary-900 dark:text-white">{{ record.student_name }}</span>
                </div>
              </td>
              <td class="px-8 py-5">
                <BaseBadge variant="neutral">{{ record.subject }}</BaseBadge>
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
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import StatCard from '@/components/ui/StatCard.vue'
import LinearChart from '@/components/charts/LinearChart.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import { 
  UsersIcon, 
  UserGroupIcon, 
  AcademicCapIcon, 
  CheckCircleIcon,
  ClipboardCheckIcon,
  ChartBarIcon
} from '@heroicons/vue/outline'

const props = defineProps(['data'])

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', { 
    month: 'short', 
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

// Chart Data mapping
const chartData = computed(() => ({
  labels: props.data.attendance_trend.map(item => {
    const d = new Date(item.date)
    return d.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' })
  }),
  datasets: [{
    label: 'Marked Submissions',
    data: props.data.attendance_trend.map(item => item.count),
    borderColor: '#0ea5e9',
    backgroundColor: 'rgba(14, 165, 233, 0.1)',
    fill: true,
    tension: 0.4,
    pointRadius: 4,
    pointBackgroundColor: '#0ea5e9',
    pointBorderWidth: 2,
    pointBorderColor: '#fff',
  }]
}))
</script>