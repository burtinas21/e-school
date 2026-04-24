

import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useAttendanceStore = defineStore('attendance', {
  state: () => ({
    classAttendance: [],   // list of students with their status for a particular class/date
    studentHistory: [],
    reportData: null,
    loading: false,
    error: null,
  }),
  actions: {
    // Get attendance for a specific class (for teachers to mark)
    async getClassAttendance(params) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.post('/api/attendances/class', params)
        this.classAttendance = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load class attendance'
        this.classAttendance = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    // Mark attendance for multiple students
    async markAttendance(data) {
      try {
        const response = await axiosInstance.post('/api/attendances/mark', data)
        return { success: true, message: response.data.message }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Failed to mark attendance',
          errors: err.response?.data?.errors,
        }
      }
    },

    // Get student attendance history (for student/parent view)
    async fetchStudentHistory(studentId) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get(`/api/attendances/student/${studentId}/history`)
        this.studentHistory = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load history'
        this.studentHistory = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    // Get attendance report (admin)
    async fetchReport(params) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.post('/api/attendances/report', params)
        this.reportData = response.data.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load report'
        this.reportData = null
        console.error(err)
      } finally {
        this.loading = false
      }
    },
  },
})