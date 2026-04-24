<template>
  <div class="max-w-3xl mx-auto space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex items-center gap-4">
      <BaseButton variant="ghost" size="sm" @click="$router.push('/notifications')">
        <template #icon-left><ChevronLeftIcon class="w-4 h-4" /></template>
        Back to Inbox
      </BaseButton>
    </div>

    <div>
      <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Bulk Communications</h1>
      <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Broadcast important announcements or alerts to school groups.</p>
    </div>

    <!-- Form Section -->
    <div class="glass-card p-10 rounded-[2.5rem]">
      <form @submit.prevent="submitForm" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="md:col-span-2">
            <BaseInput 
              v-model="form.title" 
              label="Notification Title" 
              placeholder="e.g. Annual Sports Meet 2024"
              required 
            />
          </div>

          <div class="md:col-span-2">
            <div class="space-y-2">
              <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1">Message Content</label>
              <textarea 
                v-model="form.message" 
                rows="5" 
                required 
                placeholder="Compose your broadcast message here..."
                class="w-full bg-secondary-50 dark:bg-secondary-800 border-none rounded-2xl px-5 py-4 text-sm font-bold text-secondary-700 dark:text-secondary-100 focus:ring-4 focus:ring-primary-100 dark:focus:ring-primary-900/40 outline-none transition-all resize-none shadow-inner"
              ></textarea>
            </div>
          </div>

          <BaseSelect v-model="form.type" label="Communication Type" required>
            <option value="event">📅 Event Announcement</option>
            <option value="warning">⚠️ Urgent Warning</option>
            <option value="daily_summary">📊 Daily Summary</option>
            <option value="permission">📝 Permission Request</option>
          </BaseSelect>

          <!-- Recipient Selection -->
          <div class="space-y-4">
            <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1">Recipients Selection</label>
            <div class="grid grid-cols-2 gap-3">
              <label 
                v-for="group in recipientGroups" 
                :key="group.key"
                class="flex items-center gap-3 p-3 rounded-xl border-2 transition-all cursor-pointer select-none"
                :class="[
                  form[group.key] 
                    ? 'bg-primary-50 dark:bg-primary-900/40 border-primary-500 text-primary-700 dark:text-primary-300' 
                    : 'bg-white dark:bg-secondary-900 border-secondary-100 dark:border-secondary-800 text-secondary-400 group-hover:border-secondary-200'
                ]"
              >
                <input 
                  type="checkbox" 
                  v-model="form[group.key]" 
                  class="hidden"
                />
                <div 
                  class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all"
                  :class="form[group.key] ? 'border-primary-600 bg-primary-600 text-white' : 'border-secondary-200'"
                >
                  <CheckIcon v-if="form[group.key]" class="w-3.5 h-3.5" />
                </div>
                <span class="text-xs font-black uppercase tracking-widest">{{ group.label }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Errors -->
        <div v-if="formErrors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
          <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Dispatcher Errors</p>
          <ul class="space-y-1">
            <li v-for="(errs, field) in formErrors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
              <span class="capitalize">{{ field }}:</span> {{ errs.join(', ') }}
            </li>
          </ul>
        </div>

        <!-- Footer -->
        <div class="flex justify-end pt-8 border-t border-secondary-100 dark:border-secondary-800">
          <BaseButton 
            variant="primary" 
            size="lg" 
            type="submit"
            :loading="submitting"
            class="px-12"
          >
            Broadcast Notification
          </BaseButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useRouter } from 'vue-router'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import { ChevronLeftIcon, CheckIcon } from '@heroicons/vue/outline'

const notificationsStore = useNotificationsStore()
const router = useRouter()

const form = ref({
  title: '',
  message: '',
  send_to_all: false,
  send_to_students: false,
  send_to_guardians: false,
  send_to_teachers: false,
  type: 'event',
})

const recipientGroups = [
  { key: 'send_to_all', label: 'All Users' },
  { key: 'send_to_students', label: 'Students' },
  { key: 'send_to_teachers', label: 'Teachers' },
  { key: 'send_to_guardians', label: 'Guardians' },
]

const submitting = ref(false)
const formErrors = ref(null)

const submitForm = async () => {
  submitting.value = true
  formErrors.value = null
  
  const data = {
    title: form.value.title,
    message: form.value.message,
    send_to_all: form.value.send_to_all,
    send_to_students: form.value.send_to_students,
    send_to_guardians: form.value.send_to_guardians,
    send_to_teachers: form.value.send_to_teachers,
    type: form.value.type,
  }
  
  const result = await notificationsStore.sendBulkNotification(data)
  if (result.success) {
    alert('Broadcast dispatched successfully')
    router.push('/notifications')
  } else {
    formErrors.value = result.errors || { general: [result.message] }
    submitting.value = false
  }
}
</script>