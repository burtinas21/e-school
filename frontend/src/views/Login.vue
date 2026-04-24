<template>
  <div class="min-h-screen flex items-center justify-center bg-secondary-50 dark:bg-secondary-950 p-6 selection:bg-primary-500/30">
    <!-- Dynamic Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary-500/5 blur-[120px] rounded-full animate-float ring-1 ring-primary-500/10"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-500/5 blur-[120px] rounded-full animate-float-delayed ring-1 ring-indigo-500/10"></div>
    </div>

    <div class="max-w-md w-full relative z-10">
      <!-- Logo Section -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-[2rem] bg-white dark:bg-secondary-900 shadow-premium mb-6 ring-1 ring-secondary-100 dark:ring-secondary-800">
          <AcademicCapIcon class="w-10 h-10 text-primary-600 dark:text-primary-400" />
        </div>
        <h1 class="text-4xl font-black text-secondary-900 dark:text-white tracking-tighter">
          E-SCHOOL <span class="text-primary-600">ATTENDANCE</span>
        </h1>
        <p class="text-sm font-bold text-secondary-400 mt-2 uppercase tracking-[0.3em]">Institutional Access Portal</p>
      </div>

      <!-- Login Form Card -->
      <div class="glass-card p-10 rounded-[3rem] border border-secondary-100 dark:border-secondary-800 shadow-premium overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary-500/40 to-transparent"></div>
        
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl font-black text-secondary-900 dark:text-white">Welcome Back</h2>
            <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest leading-none">Authenticating user credentials...</p>
          </div>

          <div class="space-y-4">
            <BaseInput 
              v-model="email" 
              type="email" 
              label="Institutional Email" 
              placeholder="name@school.edu"
              required 
            />
            <BaseInput 
              v-model="password" 
              type="password" 
              label="Access Password" 
              placeholder="••••••••"
              required 
            />
          </div>

          <div v-if="error" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-2xl flex items-start gap-3 animate-shake">
            <ExclamationCircleIcon class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
            <p class="text-xs font-bold text-rose-700 dark:text-rose-300 leading-relaxed">{{ error }}</p>
          </div>

          <div class="pt-2">
            <BaseButton 
              type="submit" 
              variant="primary" 
              class="w-full text-sm py-4 rounded-2xl shadow-xl shadow-primary-500/20"
              :loading="loading"
            >
              Authorize Access
              <template #icon-right><ArrowRightIcon class="w-4 h-4" /></template>
            </BaseButton>
          </div>

          <p class="text-center text-xs font-bold text-secondary-400">
            Forgot authorization? <a href="#" class="text-primary-600 hover:underline">Request Reset</a>
          </p>
        </form>
      </div>

      <!-- Footer Links -->
      <div class="mt-8 text-center space-y-4">
        <p class="text-xs font-bold text-secondary-500 uppercase tracking-widest">
          No institutional profile? 
          <router-link to="/register" class="text-primary-600 hover:text-primary-500 ml-1 underline decoration-primary-500/30 underline-offset-4">Register Now</router-link>
        </p>
        
        <div class="flex items-center justify-center gap-6 pt-6 border-t border-secondary-100 dark:border-secondary-900/50">
          <span class="text-[10px] font-bold text-secondary-300 dark:text-secondary-700 uppercase tracking-[0.2em]">&copy; 2026 E-School Protocol</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

// UI Components
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { AcademicCapIcon, ArrowRightIcon, ExclamationCircleIcon } from '@heroicons/vue/outline'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const authStore = useAuthStore()
const router = useRouter()

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const result = await authStore.login(email.value, password.value)
    if (result.success) {
      await authStore.fetchUser()
      router.push('/')
    } else {
      error.value = result.message || 'Invalid institutional credentials.'
    }
  } catch (err) {
    error.value = 'Security communication failure. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@keyframes float {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(10px, 15px); }
}
@keyframes float-delayed {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(-10px, -15px); }
}
.animate-float { animation: float 12s ease-in-out infinite; }
.animate-float-delayed { animation: float-delayed 15s ease-in-out infinite; }
.animate-shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>