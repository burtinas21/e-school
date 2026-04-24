
import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    data: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchDashboard() {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/dashboard')
        this.data = response.data.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load dashboard data'
        console.error('Dashboard fetch error:', err)
      } finally {
        this.loading = false
      }
    },
  },
})