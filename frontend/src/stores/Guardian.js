import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useGuardiansStore = defineStore('guardians', {
  state: () => ({
    guardians: [],
    meta: { current_page: 1, last_page: 1, total: 0 },
    loading: false,
    error: null,
  }),
  actions: {
    async fetchGuardians(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/guardians', { params })
        const data = response.data.data
        if (Array.isArray(data)) {
          this.guardians = data
          this.meta = { current_page: 1, last_page: 1, total: data.length }
        } else {
          this.guardians = data.data || []
          this.meta = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total,
          }
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load guardians'
        this.guardians = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async createGuardian(data) {
      try {
        const response = await axiosInstance.post('/api/guardians', data)
        await this.fetchGuardians()
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async updateGuardian(id, data) {
      try {
        const response = await axiosInstance.put(`/api/guardians/${id}`, data)
        await this.fetchGuardians()
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async deleteGuardian(id) {
      try {
        await axiosInstance.delete(`/api/guardians/${id}`)
        await this.fetchGuardians()
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Delete failed',
        }
      }
    },

    async linkStudent(guardianId, studentId) {
      try {
        await axiosInstance.post(`/api/guardians/${guardianId}/link-student`, { student_id: studentId })
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Link failed',
        }
      }
    },

    async unlinkStudent(guardianId, studentId) {
      try {
        await axiosInstance.delete(`/api/guardians/${guardianId}/unlink-student/${studentId}`)
        return { success: true }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Unlink failed',
        }
      }
    },
  },
})