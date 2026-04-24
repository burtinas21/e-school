<template>
  <aside 
    class="fixed inset-y-0 left-0 z-40 w-72 bg-white dark:bg-secondary-900 border-r border-secondary-100 dark:border-secondary-800 transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0"
    :class="[isOpen ? 'translate-x-0' : '-translate-x-full']"
  >
    <div class="flex flex-col h-full">
      <!-- Logo Section -->
      <div class="p-6 border-b border-secondary-100 dark:border-secondary-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200 dark:shadow-none">
            <AcademicCapIcon class="w-6 h-6" />
          </div>
          <div>
            <h2 class="text-sm font-black text-secondary-900 dark:text-white uppercase tracking-tighter leading-none">E-School</h2>
            <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest mt-1">Attendance Tracker</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-4 custom-scrollbar lg:mx-2 lg:my-2 lg:rounded-2xl lg:bg-secondary-50/50 lg:dark:bg-secondary-950/30">
        <div v-for="(group, idx) in menuGroups" :key="idx" class="mb-6 last:mb-0">
          <p v-if="group.title" class="px-4 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] mb-3">
            {{ group.title }}
          </p>
          <div class="space-y-1">
            <router-link
              v-for="item in group.items"
              :key="item.to"
              :to="item.to"
              v-show="item.show"
              class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all group"
              :class="[
                $route.path === item.to 
                  ? 'bg-primary-600 text-white shadow-lg shadow-primary-200 dark:shadow-none' 
                  : 'text-secondary-500 hover:bg-secondary-100 hover:text-secondary-900 dark:text-secondary-400 dark:hover:bg-secondary-800 dark:hover:text-white'
              ]"
              @click="$emit('close')"
            >
              <component :is="item.icon" class="w-5 h-5 transition-transform group-hover:scale-110" />
              {{ item.label }}
              
              <span v-if="item.badge" class="ml-auto bg-rose-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-md">
                {{ item.badge }}
              </span>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- User Profile / Footer -->
      <div class="p-4 border-t border-secondary-100 dark:border-secondary-800">
        <button
          @click="logout"
          class="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-bold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all group"
        >
          <LogoutIcon class="w-5 h-5 transition-transform group-hover:-translate-x-1" />
          Sign Out
        </button>
      </div>
    </div>
  </aside>
  
  <!-- Mobile Backdrop -->
  <div 
    v-if="isOpen" 
    class="fixed inset-0 z-30 bg-secondary-900/60 backdrop-blur-sm lg:hidden"
    @click="$emit('close')"
  ></div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { useRouter, useRoute } from 'vue-router'
import {
  HomeIcon,
  UsersIcon,
  AcademicCapIcon,
  BookOpenIcon,
  UserGroupIcon,
  CalendarIcon,
  ClockIcon,
  ClipboardCheckIcon,
  ChartBarIcon,
  BellIcon,
  MailIcon,
  CogIcon,
  UserIcon,
  LogoutIcon
} from '@heroicons/vue/outline'

const props = defineProps({
  isOpen: Boolean
})

defineEmits(['close'])

const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const router = useRouter()
const route = useRoute()

const role = computed(() => authStore.user?.role_id)
const unreadCount = computed(() => notificationsStore.unreadCount)

const logout = () => {
  authStore.logout()
  router.push('/login')
}

const menuGroups = computed(() => [
  {
    title: 'Main',
    items: [
      { label: 'Dashboard', to: '/', icon: HomeIcon, show: true },
      { label: 'Calendar', to: '/calendar', icon: CalendarIcon, show: true },
    ]
  },
  {
    title: 'Management',
    items: [
      { label: 'Students', to: '/students', icon: UsersIcon, show: role.value === 1 || role.value === 2 },
      { label: 'Teachers', to: '/teachers', icon: UsersIcon, show: role.value === 1 || role.value === 2 },
      { label: 'Grades', to: '/grades', icon: AcademicCapIcon, show: role.value === 1 },
      { label: 'Sections', to: '/sections', icon: AcademicCapIcon, show: role.value === 1 },
      { label: 'Subjects', to: '/subjects', icon: BookOpenIcon, show: role.value === 1 },
      { label: 'Assignments', to: '/teacher-assignments', icon: UserGroupIcon, show: role.value === 1 },
      { label: 'Schedules', to: '/schedules', icon: CalendarIcon, show: role.value === 1 || role.value === 2 },
      { label: 'Periods', to: '/periods', icon: ClockIcon, show: role.value === 1 },
    ]
  },
  {
    title: 'Attendance',
    items: [
      { label: 'Mark Attendance', to: '/attendance/mark', icon: ClipboardCheckIcon, show: role.value === 1 || role.value === 2 },
      { label: 'My Attendance', to: '/attendance/history', icon: ClockIcon, show: role.value === 3 || role.value === 4 },
      { label: 'Reports', to: '/reports/attendance', icon: ChartBarIcon, show: role.value === 1 || role.value === 2 },
    ]
  },
  {
    title: 'Comms',
    items: [
      { label: 'Notifications', to: '/notifications', icon: BellIcon, show: !!role.value, badge: unreadCount.value },
      { label: 'Send Notice', to: '/notifications/send', icon: MailIcon, show: role.value === 1 },
    ]
  },
  {
    title: 'Account',
    items: [
      { label: 'Profile', to: '/profile', icon: UserIcon, show: true },
      { label: 'Settings', to: '/settings', icon: CogIcon, show: role.value === 1 },
    ]
  }
])
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-secondary-200 dark:bg-secondary-800 rounded-full;
}
</style>
