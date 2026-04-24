// axios.js - Configured HTTP client with interceptors

import axios from 'axios'

// Read base URL from environment variables
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

// Check if the variable is defined (helpful for debugging)
if (!API_BASE_URL) {
  console.error('❌ VITE_API_BASE_URL is not defined. Please check your .env file.')
} else {
  console.log('✅ Axios base URL:', API_BASE_URL)
}

const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor: attach token to every request if it exists
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor: handle 401 Unauthorized (token expired or invalid)
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default axiosInstance