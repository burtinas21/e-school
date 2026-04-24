<template>
  <div class="flex h-screen bg-secondary-50 dark:bg-secondary-950 overflow-hidden">
    <!-- Sidebar Component -->
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Navbar Component -->
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <!-- View Content -->
      <main class="flex-1 overflow-y-auto custom-scrollbar">
        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
          <router-view v-slot="{ Component }">
            <Transition
              name="fade-slide"
              mode="out-in"
              appear
            >
              <component :is="Component" />
            </Transition>
          </router-view>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'
import Navbar from '@/components/layout/Navbar.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const sidebarOpen = ref(false)
const authStore = useAuthStore()
const notificationsStore = useNotificationsStore()

onMounted(async () => {
  // Ensure user data is fetched for role-based logic
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }
  
  if (authStore.isAuthenticated) {
    await notificationsStore.fetchNotifications()
  }
})
</script>

<style>
/* Page Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease-out;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  @apply bg-transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-secondary-200 dark:bg-secondary-800 rounded-full;
}
</style>