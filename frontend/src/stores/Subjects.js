/**
 * Subjects Store – Manages school subjects (e.g., Mathematics, English)
 * Provides CRUD operations and separate methods for fetching by grade.
 */
import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useSubjectsStore = defineStore('subjects', {
  state: () => ({
    subjects: [],      // All subjects (used for filters and admin lists)
    loading: false,
    error: null,
  }),
  actions: {
    /**
     * Fetch all subjects (optionally filtered by gradeId, but replaces main list)
     * @param {number|null} gradeId - Optional grade ID to filter subjects.
     */
    async fetchSubjects(gradeId = null) {
      this.loading = true
      this.error = null
      try {
        const url = gradeId ? `/api/subjects/by-grade/${gradeId}` : '/api/subjects'
        const response = await axiosInstance.get(url)
        this.subjects = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load subjects'
        this.subjects = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    /**
     * Fetch subjects for a specific grade WITHOUT modifying the main `subjects` state.
     * Useful for dropdowns in modals (e.g., Teacher Assignments).
     * @param {number} gradeId - Grade ID to filter subjects.
     * @returns {Promise<Array>} Array of subjects.
     */
    async fetchSubjectsByGrade(gradeId) {
      if (!gradeId) return []
      try {
        const response = await axiosInstance.get(`/api/subjects/by-grade/${gradeId}`)
        return response.data.data || []
      } catch (err) {
        console.error('Failed to fetch subjects by grade:', err)
        return []
      }
    },

    /**
     * Create a new subject.
     * @param {Object} data - Subject data (name, code, grade_id, description, is_active)
     * @returns {Object} { success, data, errors }
     */
    async createSubject(data) {
      try {
        const response = await axiosInstance.post('/api/subjects', data)
        await this.fetchSubjects()
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    /**
     * Update an existing subject.
     * @param {number} id - Subject ID.
     * @param {Object} data - Updated fields.
     * @returns {Object} { success, data, errors }
     */
    async updateSubject(id, data) {
      try {
        const response = await axiosInstance.put(`/api/subjects/${id}`, data)
        await this.fetchSubjects()
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    /**
     * Delete a subject by ID.
     * @param {number} id - Subject ID.
     * @returns {Object} { success, message }
     */
    async deleteSubject(id) {
      try {
        await axiosInstance.delete(`/api/subjects/${id}`)
        await this.fetchSubjects()
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