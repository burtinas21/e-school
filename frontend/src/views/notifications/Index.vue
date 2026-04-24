<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Comms Center</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Stay updated with system alerts, attendance notices, and school announcements.</p>
      </div>
      
      <div v-if="unreadCount > 0" class="flex items-center gap-3">
        <BaseButton variant="ghost" size="sm" @click="markAllRead">
          Mark all as read
        </BaseButton>
        <div class="px-4 py-1.5 bg-primary-100 dark:bg-primary-900/40 rounded-full border border-primary-200 dark:border-primary-800">
          <span class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-widest">
            {{ unreadCount }} Unread
          </span>
        </div>
      </div>
    </div>

    <!-- Messages List -->
    <div class="space-y-4">
      <!-- Loading State -->
      <div v-if="loading" class="p-20">
        <LoadingSpinner text="Synchronizing notifications..." />
      </div>

      <!-- No Data State -->
      <div v-else-if="!notifications.length" class="p-24 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
         <div class="w-24 h-24 bg-secondary-50 dark:bg-secondary-800 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
           <BellIcon class="w-10 h-10 text-secondary-300 dark:text-secondary-600" />
         </div>
         <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">All caught up!</h4>
         <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto leading-relaxed">
           Your inbox is currently empty. You'll receive system updates and personal alerts here.
         </p>
      </div>

      <!-- List Content -->
      <div v-else class="space-y-4">
        <NotificationItem 
          v-for="notification in notifications"
          :key="notification.id"
          :notification="notification"
          @mark-read="markRead"
          @delete="deleteNotification"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useNotificationsStore } from '@/stores/notifications'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import NotificationItem from '@/components/notifications/NotificationItem.vue'
import { BellIcon } from '@heroicons/vue/outline'

const notificationsStore = useNotificationsStore()
const { notifications, loading, unreadCount } = storeToRefs(notificationsStore)

const markRead = async (id) => {
  await notificationsStore.markAsRead(id)
}

const markAllRead = async () => {
  await notificationsStore.markAllAsRead()
}

const deleteNotification = async (id) => {
  // We'll use a simple alert for now, consistent with original but cleaner UI
  if (window.confirm('Are you sure you want to delete this notification?')) {
    await notificationsStore.deleteNotification(id)
  }
}

onMounted(() => {
  notificationsStore.fetchNotifications()
})
</script>