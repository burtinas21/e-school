import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    settings: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchSettings() {
      this.loading = true
      this.error = null
      try {
        const response = await axiosInstance.get('/api/settings')
        this.settings = response.data.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch settings'
        console.error(err)
      } finally {
        this.loading = false
      }
    },
    async updateSettings(settingsArray) {
      this.loading = true
      try {
        const response = await axiosInstance.post('/api/settings', { settings: settingsArray })
        await this.fetchSettings()
        return { success: true, message: response.data.message }
      } catch (err) {
        return {
          success: false,
          message: err.response?.data?.message || 'Failed to update settings',
          errors: err.response?.data?.errors
        }
      } finally {
        this.loading = false
      }
    }
  },
  getters: {
    getSetting: (state) => (key) => state.settings.find(s => s.key === key)?.value
  }
})
