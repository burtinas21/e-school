

import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useTeacherAssignmentsStore = defineStore('teacherAssignments', {
  state: () => ({
    assignments: [],
    meta: { current_page: 1, last_page: 1, total: 0 },
    loading: false,
    error: null,
  }),
  actions: {
    async fetchAssignments(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/teacher-assignments', { params })
        this.assignments = response.data.data.data || []
        this.meta = {
          current_page: response.data.data.current_page,
          last_page: response.data.data.last_page,
          total: response.data.data.total,
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load assignments'
        this.assignments = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async createAssignment(data) {
      try {
        const response = await axiosInstance.post('/api/teacher-assignments', data)
        await this.fetchAssignments({ per_page: 15, page: 1 })
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async updateAssignment(id, data) {
      try {
        const response = await axiosInstance.put(`/api/teacher-assignments/${id}`, data)
        await this.fetchAssignments({ per_page: 15, page: this.meta.current_page })
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async deleteAssignment(id) {
      try {
        await axiosInstance.delete(`/api/teacher-assignments/${id}`)
        await this.fetchAssignments()
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