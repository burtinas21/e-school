
import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useTeachersStore = defineStore('teachers', {
  state: () => ({
    teachers: [],
    meta: { current_page: 1, last_page: 1, total: 0 },
    loading: false,
    error: null,
  }),
  actions: {
    async fetchTeachers(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/teachers', { params })
        this.teachers = response.data.data
        this.meta = {
          current_page: response.data.data.current_page || 1,
          last_page: response.data.data.last_page || 1,
          total: response.data.data.total || response.data.data.length,
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load teachers'
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async createTeacher(data) {
      try {
        const response = await axiosInstance.post('/api/teachers', data)
        await this.fetchTeachers({ per_page: 10, page: 1 })
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async updateTeacher(id, data) {
      try {
        const response = await axiosInstance.put(`/api/teachers/${id}`, data)
        await this.fetchTeachers({ per_page: 10, page: this.meta.current_page })
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async deleteTeacher(id) {
      try {
        await axiosInstance.delete(`/api/teachers/${id}`)
        await this.fetchTeachers({ per_page: 10, page: this.meta.current_page })
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