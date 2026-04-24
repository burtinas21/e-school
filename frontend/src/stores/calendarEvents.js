import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useCalendarEventsStore = defineStore('calendarEvents', {
  state: () => ({
    events: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchMonthEvents(year, month) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get(`/api/calendar-events/month/${year}/${month}`)
        this.events = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load events'
        this.events = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async fetchUpcoming(days = 30) {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get(`/api/calendar-events/upcoming/${days}`)
        this.events = response.data.data || []
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load upcoming events'
        this.events = []
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async createEvent(data) {
      try {
        const response = await axiosInstance.post('/api/calendar-events', data)
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Creation failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async updateEvent(id, data) {
      try {
        const response = await axiosInstance.put(`/api/calendar-events/${id}`, data)
        return { success: true, data: response.data.data }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Update failed',
          errors: err.response?.data?.errors,
        }
      }
    },

    async deleteEvent(id) {
      try {
        await axiosInstance.delete(`/api/calendar-events/${id}`)
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