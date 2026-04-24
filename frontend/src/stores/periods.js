import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const usePeriodsStore = defineStore('periods', {
  state: () => ({
    periods: [],
    loading: false,
    error: null,
  }),
  actions: {
    // Fetch all periods (public)
    async fetchPeriods() {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/periods')
        const raw = response.data?.data || []
        this.periods = Array.isArray(raw) ? raw.filter(p => p && p.id) : []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load periods'
        this.periods = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    // Create a new period (admin)
    async createPeriod(data) {
      try {
        const response = await axiosInstance.post('/api/periods', data)
        await this.fetchPeriods() // refresh list
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    // Update period (admin)
    async updatePeriod(id, data) {
      try {
        const response = await axiosInstance.put(`/api/periods/${id}`, data)
        await this.fetchPeriods()
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    // Delete period (admin)
    async deletePeriod(id) {
      try {
        await axiosInstance.delete(`/api/periods/${id}`)
        await this.fetchPeriods()
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Delete failed',
        }
      }
    },
  },
})