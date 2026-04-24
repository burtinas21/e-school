<template>
  <div class="max-w-4xl mx-auto py-6">
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">School Settings</h1>
      <button 
        @click="saveAll" 
        :disabled="saving"
        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-all duration-200"
      >
        <span v-if="saving" class="animate-spin mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
        {{ saving ? 'Saving Changes...' : 'Save All Settings' }}
      </button>
    </div>

    <!-- Tabbed Navigation -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="border-b border-gray-100 px-6 py-4 flex space-x-8">
        <button 
          v-for="tab in ['General', 'Contact', 'Academic']" 
          :key="tab"
          @click="activeTab = tab.toLowerCase()"
          class="text-sm font-semibold pb-1 transition-all"
          :class="activeTab === tab.toLowerCase() ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'"
        >
          {{ tab }}
        </button>
      </div>

      <div class="p-8">
        <!-- General Settings -->
        <div v-show="activeTab === 'general'" class="space-y-8 animate-in fade-in duration-300">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">School Name</label>
              <input 
                v-model="form.school_name" 
                type="text" 
                class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                placeholder="e.g. St. Xavier's International"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
              <input 
                v-model="form.academic_year" 
                type="text" 
                class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                placeholder="e.g. 2024-2025"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Attendance Policy Description</label>
            <textarea 
              v-model="form.attendance_policy" 
              rows="4"
              class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
              placeholder="Explain how attendance should be marked..."
            ></textarea>
          </div>
        </div>

        <!-- Contact Settings -->
        <div v-show="activeTab === 'contact'" class="space-y-8 animate-in fade-in duration-300">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">School Email</label>
              <input 
                v-model="form.school_email" 
                type="email" 
                class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input 
                v-model="form.school_phone" 
                type="text" 
                class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
              />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
            <textarea 
              v-model="form.school_address" 
              rows="2"
              class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
            ></textarea>
          </div>
        </div>

        <!-- Academic Settings -->
        <div v-show="activeTab === 'academic'" class="space-y-8 animate-in fade-in duration-300">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Passing Attendance Percentage (%)</label>
              <input 
                v-model="form.passing_percentage" 
                type="number" 
                class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Working Days per Year</label>
              <input 
                v-model="form.working_days" 
                type="number" 
                class="block w-full border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
              />
            </div>
          </div>

          <!-- Production Logic: Weekend Marking -->
          <div class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-indigo-900">Allow Weekend Marking</h3>
              <p class="text-xs text-indigo-700 mt-1">If enabled, teachers can mark attendance on Saturdays and Sundays.</p>
            </div>
            <button 
              @click="form.allow_weekend_marking = !form.allow_weekend_marking"
              class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
              :class="form.allow_weekend_marking ? 'bg-indigo-600' : 'bg-gray-200'"
            >
              <span 
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                :class="form.allow_weekend_marking ? 'translate-x-5' : 'translate-x-0'"
              ></span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()
const activeTab = ref('general')
const saving = ref(false)

const form = reactive({
  school_name: '',
  academic_year: '',
  attendance_policy: '',
  school_email: '',
  school_phone: '',
  school_address: '',
  passing_percentage: 75,
  working_days: 200,
  allow_weekend_marking: false
})

onMounted(async () => {
  await settingsStore.fetchSettings()
  // Hydrate form from existing settings
  settingsStore.settings.forEach(s => {
    if (form.hasOwnProperty(s.key)) {
      form[s.key] = s.value
    }
  })
})

const saveAll = async () => {
  saving.value = true
  const settingsArray = Object.keys(form).map(key => ({
    key: key,
    value: form[key]
  }))
  
  const result = await settingsStore.updateSettings(settingsArray)
  if (result.success) {
    alert('Settings updated successfully')
  } else {
    alert(result.message || 'Failed to save settings')
  }
  saving.value = false
}
</script>
