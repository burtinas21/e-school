<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Institutional Periods</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Configure daily time slots for sessions, examinations, and recreational breaks.</p>
      </div>
      
      <div v-if="canManage">
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Define New Period
        </BaseButton>
      </div>
    </div>

    <!-- Main Table -->
    <div class="glass-card rounded-[2.5rem] overflow-hidden border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div v-if="loading" class="p-20 flex justify-center">
        <LoadingSpinner text="Synchronizing schedule slots..." />
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Period Designation</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Timeline</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Categorization</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-center">Status</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <tr v-for="period in filteredPeriods" :key="period.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-xs font-black text-secondary-400 border border-secondary-200 dark:border-secondary-700 shadow-sm">
                    {{ period.name.match(/\d+/)?.[0] || 'P' }}
                  </div>
                  <span class="text-sm font-black text-secondary-900 dark:text-white">{{ period.name }}</span>
                </div>
              </td>
              <td class="px-8 py-5">
                <div class="flex items-center gap-2">
                  <ClockIcon class="w-4 h-4 text-secondary-300" />
                  <span class="text-xs font-black text-secondary-600 dark:text-secondary-400 font-mono">
                    {{ formatTime(period.start_time) }} — {{ formatTime(period.end_time) }}
                  </span>
                </div>
              </td>
              <td class="px-8 py-5">
                <BaseBadge :variant="period.is_break ? 'warning' : 'primary'">
                  {{ period.is_break ? 'Institutional Break' : 'Academic Session' }}
                </BaseBadge>
              </td>
              <td class="px-8 py-5 text-center">
                <BaseBadge :variant="period.is_active ? 'success' : 'danger'">
                  {{ period.is_active ? 'Active' : 'Disabled' }}
                </BaseBadge>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2" v-if="canManage">
                  <BaseButton variant="ghost" size="xs" @click="editPeriod(period)">
                    Edit
                  </BaseButton>
                  <BaseButton variant="danger-ghost" size="xs" @click="confirmDelete(period)">
                    Delete
                  </BaseButton>
                </div>
              </td>
            </tr>
            <tr v-if="filteredPeriods.length === 0">
              <td colspan="5" class="px-8 py-20 text-center">
                 <p class="text-secondary-400 font-bold uppercase tracking-widest text-xs">No institutional periods defined</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <BaseModal 
      :show="modalOpen" 
      :title="isEditing ? 'Modify Time Slot' : 'Define New Period'" 
      @close="closeModal"
    >
      <form @submit.prevent="submitForm" class="space-y-6">
        <BaseInput 
          v-model="form.name" 
          label="Period Identity" 
          placeholder="e.g. 1st Period / Lunch Break"
          required 
        />
        
        <BaseInput 
          v-model.number="form.period_number" 
          label="Period Number" 
          type="number"
          placeholder="e.g. 1, 2, 3"
          required 
        />
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <BaseInput 
            v-model="form.start_time" 
            label="Start Timestamp" 
            type="time" 
            required 
          />
          <BaseInput 
            v-model="form.end_time" 
            label="End Timestamp" 
            type="time" 
            required 
          />
        </div>

        <BaseSelect v-model="form.is_break" label="Period Classification">
          <option :value="false">Academic Class</option>
          <option :value="true">Official Break</option>
        </BaseSelect>

        <BaseInput 
          v-if="form.is_break"
          v-model="form.break_name" 
          label="Break Name" 
          placeholder="e.g. Lunch Break, Recess"
        />

        <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl border border-secondary-100 dark:border-secondary-800">
          <label class="flex items-center cursor-pointer group">
            <div class="relative">
              <input v-model="form.is_active" type="checkbox" class="sr-only" />
              <div class="w-10 h-5 bg-secondary-200 dark:bg-secondary-700 rounded-full transition-colors group-hover:bg-secondary-300" :class="{'bg-emerald-500': form.is_active}"></div>
              <div class="absolute inset-y-0 left-0 w-5 h-5 bg-white rounded-full shadow-sm transform transition-transform" :class="{'translate-x-5': form.is_active}"></div>
            </div>
            <div class="ml-3">
              <span class="block text-xs font-black text-secondary-900 dark:text-white uppercase tracking-widest">Active Schedule</span>
              <span class="block text-[10px] text-secondary-500 font-bold uppercase tracking-tight">Allow sessions to be marked during this slot.</span>
            </div>
          </label>
        </div>

        <div v-if="formErrors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
           <ul class="space-y-1">
             <li v-for="(errs, field) in formErrors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
               <span class="capitalize">{{ field }}:</span> {{ errs.join(', ') }}
             </li>
           </ul>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-secondary-100 dark:border-secondary-800">
          <BaseButton variant="ghost" type="button" @click="closeModal">Cancel</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">
            {{ isEditing ? 'Save Changes' : 'Confirm Period' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Purge Time Slot" 
      description="Warning: Removing a period slot will permanently affect current schedules and historical reports."
      @close="deleteModalOpen = false"
    >
      <div v-if="deletingPeriod" class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Confirm deletion of <span class="font-black underline">{{ deletingPeriod.name }}</span>?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="deletePeriod">
          Confirm Deletion
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { usePeriodsStore } from '@/stores/periods'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { PlusIcon, ClockIcon } from '@heroicons/vue/outline'

const authStore = useAuthStore()
const periodsStore = usePeriodsStore()
const { periods, loading } = storeToRefs(periodsStore)

const filteredPeriods = computed(() => {
  const p = periods.value
  const arr = Array.isArray(p) ? p : []
  return arr.filter(period => period && period.id)
})

const canManage = computed(() => authStore.userRole === 1)

const modalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const form = ref({ name: '', period_number: null, start_time: '', end_time: '', is_break: false, break_name: '', is_active: true })
const formErrors = ref(null)
const editingId = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  form.value = { name: '', period_number: null, start_time: '', end_time: '', is_break: false, break_name: '', is_active: true }
  formErrors.value = null
  modalOpen.value = true
}

const editPeriod = (period) => {
  isEditing.value = true
  editingId.value = period.id
  form.value = { 
    name: period.name, 
    period_number: period.period_number,
    start_time: period.start_time.length >= 8 ? period.start_time : period.start_time + ':00', 
    end_time: period.end_time.length >= 8 ? period.end_time : period.end_time + ':00', 
    is_break: period.is_break, 
    break_name: period.break_name || '',
    is_active: period.is_active 
  }
  formErrors.value = null
  modalOpen.value = true
}

const closeModal = () => {
  modalOpen.value = false
}

const submitForm = async () => {
  submitting.value = true
  formErrors.value = null
  
  // Ensure times have seconds for backend validation
  const formData = {
    ...form.value,
    start_time: form.value.start_time.length >= 8 ? form.value.start_time : form.value.start_time + ':00',
    end_time: form.value.end_time.length >= 8 ? form.value.end_time : form.value.end_time + ':00'
  }
  
  try {
    let result
    if (isEditing.value) {
      result = await periodsStore.updatePeriod(editingId.value, formData)
    } else {
      result = await periodsStore.createPeriod(formData)
    }

    if (result.success) {
      modalOpen.value = false
    } else {
      formErrors.value = result.errors
    }
  } catch (err) {
    console.error('Submission failed')
  } finally {
    submitting.value = false
  }
}

const deleteModalOpen = ref(false)
const deletingPeriod = ref(null)

const confirmDelete = (period) => {
  deletingPeriod.value = period
  deleteModalOpen.value = true
}

const deletePeriod = async () => {
  submitting.value = true
  const result = await periodsStore.deletePeriod(deletingPeriod.value.id)
  if (result.success) {
    deleteModalOpen.value = false
  }
  submitting.value = false
}

const formatTime = (timeStr) => {
  if (!timeStr) return ''
  return timeStr.substring(0, 5)
}

onMounted(() => {
  periodsStore.fetchPeriods()
})
</script>