<template>
  <div class="min-h-screen flex items-center justify-center bg-secondary-50 dark:bg-secondary-950 p-6 selection:bg-primary-500/30">
    <!-- Dynamic Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[20%] right-[-5%] w-[35%] h-[35%] bg-primary-500/5 blur-[100px] rounded-full animate-float ring-1 ring-primary-500/10"></div>
      <div class="absolute bottom-[20%] left-[-5%] w-[35%] h-[35%] bg-indigo-500/5 blur-[100px] rounded-full animate-float-delayed ring-1 ring-indigo-500/10"></div>
    </div>

    <div class="max-w-xl w-full relative z-10">
      <!-- Logo Section -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-[1.5rem] bg-white dark:bg-secondary-900 shadow-premium mb-4 ring-1 ring-secondary-100 dark:ring-secondary-800">
          <AcademicCapIcon class="w-8 h-8 text-primary-600 dark:text-primary-400" />
        </div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tighter">
          ESTABLISH <span class="text-primary-600">PROFILE</span>
        </h1>
        <p class="text-[10px] font-black text-secondary-400 mt-2 uppercase tracking-[0.3em]">Institutional Enrollment Protocol</p>
      </div>

      <!-- Registration Form Card -->
      <div class="glass-card p-10 rounded-[3rem] border border-secondary-100 dark:border-secondary-800 shadow-premium overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary-500/30 to-transparent"></div>
        
        <form @submit.prevent="handleRegister" class="space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl font-black text-secondary-900 dark:text-white">Institutional Identity</h2>
            <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest leading-none">Registering new academic credentials...</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
              <BaseInput 
                v-model="name" 
                type="text" 
                label="Full Legal Name" 
                placeholder="e.g. Dr. Alexander Pierce"
                required 
              />
            </div>
            
            <div class="md:col-span-2">
              <BaseInput 
                v-model="email" 
                type="email" 
                label="Institutional Email" 
                placeholder="a.pierce@school.edu"
                required 
              />
            </div>

            <BaseInput 
              v-model="password" 
              type="password" 
              label="Access Secret" 
              placeholder="••••••••"
              required 
            />
            
            <BaseInput 
              v-model="passwordConfirmation" 
              type="password" 
              label="Confirm Secret" 
              placeholder="••••••••"
              required 
            />
          </div>

          <div v-if="error" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-2xl flex items-start gap-3 animate-shake">
            <ExclamationCircleIcon class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
            <p class="text-xs font-bold text-rose-700 dark:text-rose-300 leading-relaxed">{{ error }}</p>
          </div>

          <div class="pt-4">
            <BaseButton 
              type="submit" 
              variant="primary" 
              class="w-full text-sm py-4 rounded-2xl shadow-xl shadow-primary-500/20"
              :loading="loading"
            >
              Initialize Profile
              <template #icon-right><ShieldCheckIcon class="w-4 h-4" /></template>
            </BaseButton>
          </div>
        </form>
      </div>

      <!-- Footer Links -->
      <div class="mt-8 text-center">
        <p class="text-xs font-bold text-secondary-500 uppercase tracking-widest">
          Existing institutional profile? 
          <router-link to="/login" class="text-primary-600 hover:text-primary-500 ml-1 underline decoration-primary-500/30 underline-offset-4">Sign In Instead</router-link>
        </p>
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
import { AcademicCapIcon, ShieldCheckIcon, ExclamationCircleIcon } from '@heroicons/vue/outline'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)
const authStore = useAuthStore()
const router = useRouter()

const handleRegister = async () => {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Identity secrets do not match. Please verify.'
    return
  }

  loading.value = true
  error.value = ''
  
  try {
    const result = await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    
    if (result.success) {
      await authStore.fetchUser()
      router.push('/')
    } else {
      error.value = result.message || 'Institutional registration failed.'
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
  50% { transform: translate(15px, 20px); }
}
@keyframes float-delayed {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(-15px, -20px); }
}
.animate-float { animation: float 14s ease-in-out infinite; }
.animate-float-delayed { animation: float-delayed 18s ease-in-out infinite; }
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