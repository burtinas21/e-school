<template>
  <div class="min-h-[calc(100vh-8rem)]">
    <!-- Loading state -->
    <div v-if="loading" class="flex flex-col justify-center items-center h-96 space-y-4">
      <LoadingSpinner size="lg" text="Personalizing your workspace..." />
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="p-8">
      <div class="glass-card p-8 rounded-[2.5rem] border border-rose-100 dark:border-rose-900/30 shadow-premium flex flex-col items-center text-center">
        <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center text-rose-500 mb-6">
          <ExclamationIcon class="w-8 h-8" />
        </div>
        <h3 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight">Access Interrupted</h3>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-2 max-w-sm">{{ error }}</p>
        <BaseButton variant="ghost" class="mt-8" @click="retryLoad">
          Attempt Recovery
        </BaseButton>
      </div>
    </div>

    <!-- Data rendered based on role -->
    <div v-else-if="dashboardData" class="space-y-8 animate-fade-in-up">
      <!-- Admin Dashboard -->
      <AdminDashboard v-if="userRole === 1" :data="dashboardData" />

      <!-- Teacher Dashboard -->
      <TeacherDashboard v-else-if="userRole === 2" :data="dashboardData" />

      <!-- Student Dashboard -->
      <StudentDashboard v-else-if="userRole === 3" :data="dashboardData" />

      <!-- Guardian Dashboard -->
      <GuardianDashboard v-else-if="userRole === 4" :data="dashboardData" />

      <!-- Fallback -->
      <div v-else class="text-center py-20">
        <div class="w-20 h-20 bg-secondary-50 dark:bg-secondary-900 rounded-3xl flex items-center justify-center mx-auto mb-6">
          <ShieldCheckIcon class="w-10 h-10 text-secondary-300" />
        </div>
        <p class="text-secondary-400 font-black uppercase tracking-widest text-xs">Awaiting Role Classification</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/Dashboard'

// UI Components
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { ExclamationIcon, ShieldCheckIcon } from '@heroicons/vue/outline'

// Role-specific Components
import AdminDashboard from '@/components/dashboards/AdminDashboard.vue'
import TeacherDashboard from '@/components/dashboards/TeacherDashboard.vue'
import StudentDashboard from '@/components/dashboards/StudentDashboard.vue'
import GuardianDashboard from '@/components/dashboards/GuardianDashboard.vue'

const authStore = useAuthStore()
const dashboardStore = useDashboardStore()

const { data: dashboardData, loading, error } = storeToRefs(dashboardStore)
const userRole = authStore.userRole

const retryLoad = () => {
  dashboardStore.fetchDashboard()
}

onMounted(() => {
  dashboardStore.fetchDashboard()
})
</script>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>