<template>
  <div class="max-w-5xl mx-auto space-y-10 pb-12">
    <!-- Header Section -->
    <div>
      <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Account Settings</h1>
      <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Manage your personal credentials, contact information, and security preferences.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
      <!-- Profile Snapshot -->
      <div class="lg:col-span-1 space-y-6">
        <div class="glass-card p-8 text-center rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 shadow-premium group">
          <div class="relative w-32 h-32 mx-auto mb-6">
            <div class="w-full h-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-[2.5rem] flex items-center justify-center text-4xl font-black shadow-inner transform group-hover:rotate-6 transition-transform duration-500">
              {{ userInitial }}
            </div>
            <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-white dark:bg-secondary-800 rounded-2xl shadow-lg border border-secondary-100 dark:border-secondary-700 flex items-center justify-center text-primary-500">
              <CameraIcon class="w-5 h-5" />
            </div>
          </div>
          
          <h2 class="text-xl font-black text-secondary-900 dark:text-white leading-tight">{{ authStore.user?.name }}</h2>
          <p class="text-[10px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-[0.2em] mt-2">{{ roleName }}</p>
          
          <div class="mt-8 pt-8 border-t border-secondary-100 dark:border-secondary-800 space-y-4">
             <div class="flex items-center gap-3 text-xs font-bold text-secondary-500 dark:text-secondary-400">
               <MailIcon class="w-4 h-4 text-secondary-300" />
               <span class="truncate">{{ authStore.user?.email }}</span>
             </div>
             <div class="flex items-center gap-3 text-xs font-bold text-secondary-500 dark:text-secondary-400">
               <PhoneIcon class="w-4 h-4 text-secondary-300" />
               {{ authStore.user?.phone || 'No direct contact' }}
             </div>
          </div>
        </div>
      </div>

      <!-- Settings Forms -->
      <div class="lg:col-span-3 space-y-8">
        <!-- Identity Settings -->
        <div class="glass-card p-10 rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 shadow-premium">
          <div class="flex items-center gap-4 mb-8">
             <div class="w-12 h-12 bg-secondary-50 dark:bg-secondary-800 rounded-2xl flex items-center justify-center text-primary-500 shadow-inner">
               <UserIcon class="w-6 h-6" />
             </div>
             <div>
               <h3 class="text-lg font-black text-secondary-900 dark:text-white tracking-tight">Identity Information</h3>
               <p class="text-xs text-secondary-400 font-bold uppercase tracking-widest mt-0.5">Core account details</p>
             </div>
          </div>

          <form @submit.prevent="updateInfo" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <BaseInput 
                v-model="infoForm.name" 
                label="Full Display Name" 
                placeholder="How you appear to others"
              />
              <BaseInput 
                v-model="infoForm.phone" 
                label="Contact Number" 
                placeholder="+1-XXX-XXX-XXXX"
              />
            </div>
            <div class="flex justify-end pt-4">
              <BaseButton 
                variant="primary" 
                type="submit" 
                size="lg"
                :loading="updatingInfo"
                class="px-10"
              >
                Save Identity
              </BaseButton>
            </div>
          </form>
        </div>

        <!-- Security Hardening -->
        <div class="glass-card p-10 rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 shadow-premium">
          <div class="flex items-center gap-4 mb-8">
             <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center text-rose-500 shadow-inner">
               <ShieldCheckIcon class="w-6 h-6" />
             </div>
             <div>
               <h3 class="text-lg font-black text-secondary-900 dark:text-white tracking-tight">Security Hardening</h3>
               <p class="text-xs text-secondary-400 font-bold uppercase tracking-widest mt-0.5">Password & authorization</p>
             </div>
          </div>

          <form @submit.prevent="changePassword" class="space-y-6">
            <BaseInput 
              v-model="passForm.current_password" 
              type="password" 
              label="Current Authentication" 
              placeholder="Confirm your identity"
            />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <BaseInput 
                v-model="passForm.new_password" 
                type="password" 
                label="New Password" 
                placeholder="Minimum 8 characters"
              />
              <BaseInput 
                v-model="passForm.new_password_confirmation" 
                type="password" 
                label="Re-type Password" 
                placeholder="Must match exactly"
              />
            </div>

            <div v-if="passError" class="p-3 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase tracking-widest rounded-xl text-center">
              {{ passError }}
            </div>

            <div class="flex justify-end pt-4">
              <BaseButton 
                variant="primary" 
                type="submit" 
                size="lg"
                :loading="changingPass"
                class="px-10"
              >
                Update Credentials
              </BaseButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

// UI Components
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { 
  CameraIcon, MailIcon, PhoneIcon, UserIcon, ShieldCheckIcon 
} from '@heroicons/vue/outline'

const authStore = useAuthStore()

const userInitial = computed(() => authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U')
const roleName = computed(() => {
  const roles = { 1: 'System Administrator', 2: 'Academic Teacher', 3: 'Student', 4: 'Parent/Guardian' }
  return roles[authStore.user?.role_id] || 'Institutional Member'
})

const infoForm = reactive({
  name: authStore.user?.name || '',
  phone: authStore.user?.phone || ''
})
const updatingInfo = ref(false)

const passForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})
const changingPass = ref(false)
const passError = ref('')

const updateInfo = async () => {
  updatingInfo.value = true
  const result = await authStore.updateProfile(infoForm)
  if (result.success) {
     // Optional: Show toast or feedback
  }
  updatingInfo.value = false
}

const changePassword = async () => {
  if (passForm.new_password !== passForm.new_password_confirmation) {
    passError.value = 'Security: Passwords do not match'
    return
  }
  passError.value = ''
  changingPass.value = true
  
  const result = await authStore.updateProfile({
    current_password: passForm.current_password,
    new_password: passForm.new_password,
    new_password_confirmation: passForm.new_password_confirmation
  })
  
  if (result.success) {
    passForm.current_password = ''
    passForm.new_password = ''
    passForm.new_password_confirmation = ''
  } else {
    passError.value = result.message || 'Validation: Update failed'
  }
  changingPass.value = false
}
</script>
