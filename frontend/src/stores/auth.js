import { defineStore } from 'pinia'
import axiosInstance from '@/utils/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role_id,
  },
  actions: {
    async login(email, password) {
      try {
        const response = await axiosInstance.post('/api/login', { email, password })
        const { token, user } = response.data
        this.token = token
        this.user = user
        localStorage.setItem('token', token)
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Login failed' 
        }
      }
    },
    async register(userData) {
      try {
        const response = await axiosInstance.post('/api/register', userData)
        const { token, user } = response.data
        this.token = token
        this.user = user
        localStorage.setItem('token', token)
        return { success: true }
      } catch (error) {
        console.error('Registration error:', error.response?.data || error.message)
        return { 
          success: false, 
          message: error.response?.data?.message || 'Registration failed' 
        }
      }
    },
    async fetchUser() {
      try {
        const response = await axiosInstance.get('/api/profile')
        this.user = response.data.data
      } catch (error) {
        this.logout()
      }
    },
    async updateProfile(userData) {
      try {
        const response = await axiosInstance.put('/api/profile', userData)
        this.user = response.data.data
        return { success: true, message: 'Profile updated successfully' }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Update failed',
          errors: error.response?.data?.errors
        }
      }
    },
    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('token')
    },
  },
})