/**
 * Sections Store – Manages school sections (e.g., Grade 1 – Section A)
 * Provides CRUD operations and separate methods for fetching by grade.
 */
import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useSectionsStore = defineStore('sections', {
  state: () => ({
    sections: [],      // All sections (used for filters and admin lists)
    loading: false,
    error: null,
  }),
  actions: {
    /**
     * Fetch all sections (optionally filtered by gradeId, but replaces main list)
     * Use this for initial load or when you need to refresh the full list.
     * @param {number|null} gradeId - Optional grade ID to filter sections.
     */
    async fetchSections(gradeId = null) {
      this.loading = true
      this.error = null
      try {
        const url = gradeId ? `/api/sections/by-grade/${gradeId}` : '/api/sections'
        const response = await axiosInstance.get(url)
        this.sections = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load sections'
        this.sections = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    /**
     * Fetch sections for a specific grade WITHOUT modifying the main `sections` state.
     * Useful for dropdowns in modals (e.g., Teacher Assignments).
     * @param {number} gradeId - Grade ID to filter sections.
     * @returns {Promise<Array>} Array of sections.
     */
    async fetchSectionsByGrade(gradeId) {
      if (!gradeId) return []
      try {
        const response = await axiosInstance.get(`/api/sections/by-grade/${gradeId}`)
        return response.data.data || []
      } catch (err) {
        console.error('Failed to fetch sections by grade:', err)
        return []
      }
    },

    /**
     * Create a new section.
     * @param {Object} data - Section data (name, grade_id, is_active)
     * @returns {Object} { success, data, errors }
     */
    async createSection(data) {
      try {
        const response = await axiosInstance.post('/api/sections', data)
        await this.fetchSections() // Refresh main list
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
     * Update an existing section.
     * @param {number} id - Section ID.
     * @param {Object} data - Updated fields.
     * @returns {Object} { success, data, errors }
     */
    async updateSection(id, data) {
      try {
        const response = await axiosInstance.put(`/api/sections/${id}`, data)
        await this.fetchSections()
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
     * Delete a section by ID.
     * @param {number} id - Section ID.
     * @returns {Object} { success, message }
     */
    async deleteSection(id) {
      try {
        await axiosInstance.delete(`/api/sections/${id}`)
        await this.fetchSections()
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