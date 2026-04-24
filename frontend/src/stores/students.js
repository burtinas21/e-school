// stores/students.js
import { defineStore } from 'pinia'
import axios from '@/utils/axios'

export const useStudentsStore = defineStore('students', {
  state: () => ({
    students: [],
    meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 },
    loading: false,
    error: null,
  }),
  actions: {
    async fetchStudents(params = {}) {
      this.loading = true
      this.error = null
      try {
        const res = await axios.get('/api/students', { params })
        this.students = res.data.data.data
        this.meta = {
          current_page: res.data.data.current_page,
          last_page: res.data.data.last_page,
          total: res.data.data.total,
          per_page: res.data.data.per_page,
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load students'
        console.error(err)
      } finally {
        this.loading = false
      }
    },
    async createStudent(data) {
      try {
        const res = await axios.post('/api/students', data)
        await this.fetchStudents({ page: this.meta.current_page, per_page: this.meta.per_page })
        return { success: true, data: res.data.data }
      } catch (err) {
        return { success: false, errors: err.response?.data?.errors || err.response?.data?.message }
      }
    },
    async updateStudent(id, data) {
      try {
        const res = await axios.put(`/api/students/${id}`, data)
        await this.fetchStudents({ page: this.meta.current_page, per_page: this.meta.per_page })
        return { success: true, data: res.data.data }
      } catch (err) {
        return { success: false, errors: err.response?.data?.errors || err.response?.data?.message }
      }
    },
    async deleteStudent(id) {
      try {
        await axios.delete(`/api/students/${id}`)
        await this.fetchStudents({ page: this.meta.current_page, per_page: this.meta.per_page })
        return { success: true }
      } catch (err) {
        return { success: false, message: err.response?.data?.message || 'Delete failed' }
      }
    },
  },
})