import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useSchedulesStore = defineStore('schedules', {
  state: () => ({
    schedules: [],      // array of schedule entries for the current view
    loading: false,
    error: null,
  }),
  actions: {
    // Fetch schedules for a specific section (used by admin)
    async fetchBySection(sectionId) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get(`/api/schedules/section/${sectionId}`)
        this.schedules = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load schedules'
        this.schedules = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    // Fetch schedules for a specific teacher (used by teachers)
    async fetchByTeacher(teacherId) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get(`/api/schedules/teacher/${teacherId}`)
        this.schedules = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load teacher schedules'
        this.schedules = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    // Create a new schedule entry
    async create(data) {
      try {
        const response = await axiosInstance.post('/api/schedules', data)
        // Refresh the current list after creation
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    // Update an existing schedule entry
    async update(id, data) {
      try {
        const response = await axiosInstance.put(`/api/schedules/${id}`, data)
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    // Delete a schedule entry
    async delete(id) {
      try {
        await axiosInstance.delete(`/api/schedules/${id}`)
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