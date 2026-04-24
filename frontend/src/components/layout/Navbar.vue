<template>
  <header class="bg-white/80 dark:bg-secondary-900/80 backdrop-blur-md border-b border-secondary-100 dark:border-secondary-800 sticky top-0 z-30">
    <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <!-- Mobile Toggle & Title -->
      <div class="flex items-center gap-4">
        <button 
          @click="$emit('toggle-sidebar')" 
          class="p-2 rounded-xl text-secondary-500 hover:bg-secondary-100 dark:text-secondary-400 dark:hover:bg-secondary-800 lg:hidden transition-all"
        >
          <MenuAlt2Icon class="w-6 h-6" />
        </button>
        
        <div>
          <h1 class="text-xl font-black text-secondary-900 dark:text-white tracking-tight leading-none">
            {{ pageTitle }}
          </h1>
          <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest mt-1 hidden sm:block">
            {{ roleName }} Portal
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2 sm:gap-4">
        <!-- Search (Desktop) -->
        <div class="hidden md:block relative group">
          <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
          <input 
            type="text" 
            placeholder="Search records..." 
            class="pl-10 pr-4 py-2 bg-secondary-50 dark:bg-secondary-800 border-none rounded-xl text-xs font-bold text-secondary-700 dark:text-secondary-100 focus:ring-4 focus:ring-primary-100 dark:focus:ring-primary-900/40 w-64 outline-none transition-all"
          />
        </div>

        <!-- Dark Mode Toggle -->
        <button 
          @click="toggleDarkMode" 
          class="p-2.5 rounded-xl bg-secondary-50 dark:bg-secondary-800 text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-all"
          :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <SunIcon v-if="isDark" class="w-5 h-5 text-amber-400" />
          <MoonIcon v-else class="w-5 h-5" />
        </button>

        <!-- Notifications -->
        <router-link 
          to="/notifications" 
          class="relative p-2.5 rounded-xl bg-secondary-50 dark:bg-secondary-800 text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-all"
        >
          <BellIcon class="w-5 h-5" />
          <span 
            v-if="unreadCount > 0" 
            class="absolute top-2 right-2 w-2.5 h-2.5 bg-rose-500 border-2 border-white dark:border-secondary-900 rounded-full"
          ></span>
        </router-link>

        <!-- Divider -->
        <div class="w-px h-8 bg-secondary-100 dark:bg-secondary-800 mx-1 hidden sm:block"></div>

        <!-- User Profile -->
        <router-link to="/profile" class="flex items-center gap-3 pl-2 sm:pl-0">
          <div class="text-right hidden sm:block text-[10px] leading-tight">
            <p class="font-black text-secondary-900 dark:text-white uppercase">{{ userName }}</p>
            <p class="font-bold text-secondary-400 uppercase tracking-widest">{{ userEmail }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 font-black border border-primary-200 dark:border-primary-800 shadow-sm">
            {{ userInitials }}
          </div>
        </router-link>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { useDarkMode } from '@/utils/theme'
import { 
  MenuAlt2Icon, 
  SearchIcon, 
  BellIcon, 
  MoonIcon, 
  SunIcon 
} from '@heroicons/vue/outline'

defineEmits(['toggle-sidebar'])

const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()
const route = useRoute()
const { isDark, toggleDarkMode } = useDarkMode()

const userName = computed(() => authStore.user?.name || 'User')
const userEmail = computed(() => authStore.user?.email || 'user@example.com')
const userInitials = computed(() => userName.value.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase())
const unreadCount = computed(() => notificationsStore.unreadCount)

const roleName = computed(() => {
  const roles = { 1: 'Admin', 2: 'Teacher', 3: 'Student', 4: 'Guardian' }
  return roles[authStore.user?.role_id] || 'User'
})

const pageTitle = computed(() => {
  const path = route.path
  if (path === '/') return 'Dashboard Overview'
  if (path.startsWith('/students')) return 'Students Registry'
  if (path.startsWith('/teachers')) return 'Teachers Registry'
  if (path.startsWith('/attendance')) return 'Attendance Records'
  if (path.startsWith('/reports')) return 'Analytics Reports'
  if (path.startsWith('/notifications')) return 'Comms Center'
  if (path.startsWith('/profile')) return 'My Account'
  return route.name || 'Portal Access'
})
</script>
