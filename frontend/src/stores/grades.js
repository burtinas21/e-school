
import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useGradesStore = defineStore('grades', {
  state: () => ({
    grades: [],
    meta: { current_page: 1, last_page: 1, total: 0 },
    loading: false,
  }),
  actions: {
    async fetchGrades(params = {}) {
      this.loading = true
      try {
        const response = await axiosInstance.get('/api/grades', { params })
        this.grades = response.data.data.data
        this.meta = {
          current_page: response.data.data.current_page,
          last_page: response.data.data.last_page,
          total: response.data.data.total,
        }
      } catch (err) {
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async createGrade(data) {
      try {
        const response = await axiosInstance.post('/api/grades', data)
        await this.fetchGrades({ per_page: 15, page: 1 })
        return { success: true, data: response.data.data }
      } catch (err) {
        console.error(err)
        return { success: false, errors: err.response?.data?.errors || err.response?.data?.message }
      }
    },

    async updateGrade(id, data) {
      try {
        const response = await axiosInstance.put(`/api/grades/${id}`, data)
        await this.fetchGrades({ per_page: 15, page: this.meta.current_page })
        return { success: true, data: response.data.data }
      } catch (err) {
        console.error(err)
        return { success: false, errors: err.response?.data?.errors || err.response?.data?.message }
      }
    },

    async deleteGrade(id) {
      try {
        await axiosInstance.delete(`/api/grades/${id}`)
        await this.fetchGrades({ per_page: 15, page: this.meta.current_page })
        return { success: true }
      } catch (err) {
        console.error(err)
        return { success: false, message: err.response?.data?.message || 'Delete failed' }
      }
    },
  },
})