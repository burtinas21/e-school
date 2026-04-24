<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Grade Configurations</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Define and manage academic grade levels and their operational status.</p>
      </div>
      
      <div>
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Define New Grade
        </BaseButton>
      </div>
    </div>

    <!-- Main Table -->
    <div class="glass-card rounded-[2.5rem] overflow-hidden border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div v-if="loading" class="p-20 flex justify-center">
        <LoadingSpinner text="Synchronizing grade levels..." />
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Academic Level</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-center">Operational Status</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <tr v-for="grade in activeGrades" :key="grade.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 flex items-center justify-center font-black shadow-inner">
                    {{ grade.name.charAt(0) }}
                  </div>
                  <span class="text-sm font-black text-secondary-900 dark:text-white">{{ grade.name }}</span>
                </div>
              </td>
              <td class="px-8 py-5 text-center">
                <BaseBadge :variant="grade.is_active ? 'success' : 'danger'">
                  {{ grade.is_active ? 'Operational' : 'Disabled' }}
                </BaseBadge>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <BaseButton variant="ghost" size="xs" @click="editGrade(grade)">
                    Edit
                  </BaseButton>
                  <BaseButton variant="danger-ghost" size="xs" @click="confirmDelete(grade)">
                    Delete
                  </BaseButton>
                </div>
              </td>
            </tr>
            <tr v-if="activeGrades.length === 0">
              <td colspan="3" class="px-8 py-20 text-center">
                 <p class="text-secondary-400 font-bold uppercase tracking-widest text-xs">No grade levels found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <BaseModal 
      :show="modalOpen" 
      :title="isEditing ? 'Modify Grade Level' : 'Define Academic Grade'" 
      @close="closeModal"
    >
      <form @submit.prevent="submitForm" class="space-y-6">
        <BaseInput 
          v-model="form.name" 
          label="Grade Identification" 
          placeholder="e.g. Grade 10 / Senior High"
          required 
        />
        
        <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl border border-secondary-100 dark:border-secondary-800">
          <label class="flex items-center cursor-pointer group">
            <div class="relative">
              <input v-model="form.is_active" type="checkbox" class="sr-only" />
              <div class="w-10 h-5 bg-secondary-200 dark:bg-secondary-700 rounded-full transition-colors group-hover:bg-secondary-300" :class="{'bg-emerald-500': form.is_active}"></div>
              <div class="absolute inset-y-0 left-0 w-5 h-5 bg-white rounded-full shadow-sm transform transition-transform" :class="{'translate-x-5': form.is_active}"></div>
            </div>
            <div class="ml-3">
              <span class="block text-xs font-black text-secondary-900 dark:text-white uppercase tracking-widest">Active Status</span>
              <span class="block text-[10px] text-secondary-500 font-bold uppercase tracking-tight">Allow classes and enrollments for this grade.</span>
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
            {{ isEditing ? 'Save Changes' : 'Confirm Definition' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Delete Grade Level" 
      description="Caution: Deleting a grade will affect associated sections, students, and sessions."
      @close="deleteModalOpen = false"
    >
      <div v-if="deletingGrade" class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Are you sure you want to delete <span class="font-black underline">{{ deletingGrade.name }}</span>?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="deleteGrade">
          Permanently Delete
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useGradesStore } from '@/stores/grades'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { PlusIcon } from '@heroicons/vue/outline'

const gradesStore = useGradesStore()
const { grades, loading } = storeToRefs(gradesStore)

const activeGrades = computed(() => (grades.value || []).filter(g => g && g.id && g.name))

const modalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const form = ref({ name: '', is_active: true })
const formErrors = ref(null)
const editingId = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  form.value = { name: '', is_active: true }
  formErrors.value = null
  modalOpen.value = true
}

const editGrade = (grade) => {
  isEditing.value = true
  editingId.value = grade.id
  form.value = { name: grade.name, is_active: grade.is_active ?? true }
  formErrors.value = null
  modalOpen.value = true
}

const closeModal = () => {
  modalOpen.value = false
}

const submitForm = async () => {
  submitting.value = true
  formErrors.value = null
  try {
    let result
    if (isEditing.value) {
      result = await gradesStore.updateGrade(editingId.value, form.value)
    } else {
      result = await gradesStore.createGrade(form.value)
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
const deletingGrade = ref(null)

const confirmDelete = (grade) => {
  deletingGrade.value = grade
  deleteModalOpen.value = true
}

const deleteGrade = async () => {
  submitting.value = true
  const result = await gradesStore.deleteGrade(deletingGrade.value.id)
  if (result.success) {
    deleteModalOpen.value = false
  }
  submitting.value = false
}

onMounted(() => {
  gradesStore.fetchGrades()
})
</script>