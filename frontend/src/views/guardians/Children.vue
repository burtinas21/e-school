<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Student Portfolio</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Associated academic profiles and real-time performance tracking.</p>
      </div>
      
      <BaseButton variant="ghost" size="sm" @click="$router.push('/guardians')">
        <template #icon-left><ChevronLeftIcon class="w-4 h-4" /></template>
        Back to Registry
      </BaseButton>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && !children.length" class="p-24 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
       <div class="w-24 h-24 bg-secondary-50 dark:bg-secondary-800 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
         <UsersIcon class="w-10 h-10 text-secondary-300 dark:text-secondary-600" />
       </div>
       <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">No Linked Students</h4>
       <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto leading-relaxed">
         Currently, there are no students associated with this guardian profile in our institutional database.
       </p>
    </div>

    <!-- Children Cards -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div v-if="loading" v-for="n in 2" :key="n" class="h-64 bg-white dark:bg-secondary-900 rounded-[2.5rem] animate-pulse"></div>
      
      <div v-for="child in children" :key="child.id" class="glass-card rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 shadow-premium overflow-hidden transition-all duration-300 hover:shadow-premium-hover group">
        <div class="p-8">
          <div class="flex items-start justify-between mb-8">
            <div class="flex items-center gap-5">
              <div class="w-16 h-16 rounded-[1.5rem] bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 flex items-center justify-center text-xl font-black shadow-inner">
                {{ child.user?.name.charAt(0) }}
              </div>
              <div>
                <h2 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">{{ child.user?.name }}</h2>
                <p class="text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mt-1 italic">
                  ID: {{ child.admission_number }}
                </p>
              </div>
            </div>
            <BaseBadge variant="primary">{{ child.grade?.name }} — {{ child.section?.name }}</BaseBadge>
          </div>

          <!-- Attendance Stats Grid -->
          <div class="grid grid-cols-4 gap-3">
             <div class="bg-emerald-50 dark:bg-emerald-900/10 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-800/30 text-center">
                <p class="text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none mb-1">{{ child.attendance?.present || 0 }}</p>
                <p class="text-[8px] font-black text-emerald-500 uppercase tracking-widest">Present</p>
             </div>
             <div class="bg-rose-50 dark:bg-rose-900/10 p-4 rounded-2xl border border-rose-100 dark:border-rose-800/30 text-center">
                <p class="text-lg font-black text-rose-600 dark:text-rose-400 leading-none mb-1">{{ child.attendance?.absent || 0 }}</p>
                <p class="text-[8px] font-black text-rose-500 uppercase tracking-widest">Absent</p>
             </div>
             <div class="bg-amber-50 dark:bg-amber-900/10 p-4 rounded-2xl border border-amber-100 dark:border-amber-800/30 text-center">
                <p class="text-lg font-black text-amber-600 dark:text-amber-400 leading-none mb-1">{{ child.attendance?.late || 0 }}</p>
                <p class="text-[8px] font-black text-amber-500 uppercase tracking-widest">Late</p>
             </div>
             <div class="bg-primary-50 dark:bg-primary-900/10 p-4 rounded-2xl border border-primary-100 dark:border-primary-800/30 text-center">
                <p class="text-lg font-black text-primary-600 dark:text-primary-400 leading-none mb-1">{{ child.attendance?.permission || 0 }}</p>
                <p class="text-[8px] font-black text-primary-500 uppercase tracking-widest">Permit</p>
             </div>
          </div>

          <div class="mt-8 pt-6 border-t border-secondary-100 dark:border-secondary-800 flex justify-end">
            <BaseButton variant="ghost" size="sm" @click="$router.push(`/attendance/history?student=${child.id}`)">
              View Detailed Timeline
              <template #icon-right><ChevronRightIcon class="w-4 h-4" /></template>
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axiosInstance from '@/utils/axios'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import { ChevronLeftIcon, ChevronRightIcon, UsersIcon } from '@heroicons/vue/outline'

const authStore = useAuthStore()
const children = ref([])
const loading = ref(true)

const props = defineProps(['guardianId'])

onMounted(async () => {
  try {
    const guardianId = props.guardianId || authStore.user?.guardian?.id
    if (guardianId) {
      const response = await axiosInstance.get(`/api/guardians/${guardianId}/children`)
      children.value = response.data.data || []
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})
</script>