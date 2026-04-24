
import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    notifications: [],    // list of notifications (user‑specific)
    unreadCount: 0,
    loading: false,
    error: null,
  }),
  actions: {
    // Fetch notifications for the current user
    async fetchNotifications() {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/notifications')
        // Handle Laravel pagination object
        this.notifications = response.data.data.data || response.data.data || []
        // Count unread
        this.unreadCount = Array.isArray(this.notifications) 
          ? this.notifications.filter(n => !n.read_at).length 
          : 0
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load notifications'
        this.notifications = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    // Mark a single notification as read
    async markAsRead(id) {
      try {
        await axiosInstance.put(`/api/notifications/${id}/read`)
        // Update local state
        const notification = this.notifications.find(n => n.id === id)
        if (notification && !notification.read_at) {
          notification.read_at = new Date().toISOString()
          this.unreadCount--
        }
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Failed to mark as read',
        }
      }
    },

    // Mark all as read
    async markAllAsRead() {
      try {
        await axiosInstance.put('/api/notifications/read-all')
        // Update all notifications
        this.notifications.forEach(n => {
          if (!n.read_at) n.read_at = new Date().toISOString()
        })
        this.unreadCount = 0
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Failed to mark all as read',
        }
      }
    },

    // Delete a notification
    async deleteNotification(id) {
      try {
        await axiosInstance.delete(`/api/notifications/${id}`)
        const removed = this.notifications.find(n => n.id === id)
        this.notifications = this.notifications.filter(n => n.id !== id)
        if (removed && !removed.read_at) this.unreadCount--
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Delete failed',
        }
      }
    },

    // Admin only: send bulk notification
    async sendBulkNotification(data) {
      try {
        const response = await axiosInstance.post('/api/notifications/bulk', data)
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Failed to send',
          errors: err.response?.data?.errors,
        }
      }
    },
  },
})