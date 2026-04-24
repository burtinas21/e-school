<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Academic Subjects</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Catalog and manage the curriculum offered across all academic levels.</p>
      </div>
      
      <div>
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Register Subject
        </BaseButton>
      </div>
    </div>

    <!-- Filter Panel -->
    <div class="glass-card p-6 rounded-3xl border border-secondary-100 dark:border-secondary-800 shadow-sm flex items-end gap-4 max-w-sm">
      <BaseSelect v-model="selectedGradeId" label="Filter by Grade Level">
        <option value="">All Academic Grades</option>
        <option v-for="grade in gradesStore.activeGrades" :key="grade.id" :value="grade.id">
          {{ grade.name }}
        </option>
      </BaseSelect>
    </div>

    <!-- Main Table -->
    <div class="glass-card rounded-[2.5rem] overflow-hidden border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div v-if="loading" class="p-20 flex justify-center">
        <LoadingSpinner text="Cataloging academic subjects..." />
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Subject Title</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Code</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Academic Level</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-center">Status</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <tr v-for="subject in filteredSubjects" :key="subject.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex flex-col">
                  <span class="text-sm font-black text-secondary-900 dark:text-white">{{ subject.name }}</span>
                  <span class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest truncate max-w-[200px]" v-if="subject.description">
                    {{ subject.description }}
                  </span>
                </div>
              </td>
              <td class="px-8 py-5">
                <span class="text-xs font-black text-primary-600 dark:text-primary-400 font-mono">{{ subject.code || 'N/A' }}</span>
              </td>
              <td class="px-8 py-5">
                <BaseBadge variant="primary">{{ subject.grade?.name }}</BaseBadge>
              </td>
              <td class="px-8 py-5 text-center">
                <BaseBadge :variant="subject.is_active ? 'success' : 'danger'">
                  {{ subject.is_active ? 'Active' : 'Archived' }}
                </BaseBadge>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <BaseButton variant="ghost" size="xs" @click="editSubject(subject)">
                    Edit
                  </BaseButton>
                  <BaseButton variant="danger-ghost" size="xs" @click="confirmDelete(subject)">
                    Delete
                  </BaseButton>
                </div>
              </td>
            </tr>
            <tr v-if="filteredSubjects.length === 0">
              <td colspan="5" class="px-8 py-20 text-center">
                 <p class="text-secondary-400 font-bold uppercase tracking-widest text-xs">No subjects found for this selection</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <BaseModal 
      :show="modalOpen" 
      :title="isEditing ? 'Modify Subject Info' : 'Catalog New Subject'" 
      @close="closeModal"
    >
      <form @submit.prevent="submitForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-2">
            <BaseInput 
              v-model="form.name" 
              label="Subject Title" 
              placeholder="e.g. Advanced Mathematics"
              required 
            />
          </div>
          <BaseInput 
            v-model="form.code" 
            label="Internal Code" 
            placeholder="e.g. MATH-101"
          />
        </div>

        <BaseSelect v-model="form.grade_id" label="Academic Level Alignment" required>
          <option value="">Select Grade</option>
          <option v-for="grade in gradesStore.activeGrades" :key="grade.id" :value="grade.id">
            {{ grade.name }}
          </option>
        </BaseSelect>

        <div class="space-y-2">
          <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1">Learning Description</label>
          <textarea 
            v-model="form.description" 
            rows="3" 
            placeholder="Briefly describe the learning objectives..."
            class="w-full bg-secondary-50 dark:bg-secondary-800 border-none rounded-2xl px-5 py-4 text-sm font-bold text-secondary-700 dark:text-secondary-100 focus:ring-4 focus:ring-primary-100 dark:focus:ring-primary-900/40 outline-none transition-all resize-none shadow-inner"
          ></textarea>
        </div>

        <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl border border-secondary-100 dark:border-secondary-800">
          <label class="flex items-center cursor-pointer group">
            <div class="relative">
              <input v-model="form.is_active" type="checkbox" class="sr-only" />
              <div class="w-10 h-5 bg-secondary-200 dark:bg-secondary-700 rounded-full transition-colors group-hover:bg-secondary-300" :class="{'bg-emerald-500': form.is_active}"></div>
              <div class="absolute inset-y-0 left-0 w-5 h-5 bg-white rounded-full shadow-sm transform transition-transform" :class="{'translate-x-5': form.is_active}"></div>
            </div>
            <div class="ml-3">
              <span class="block text-xs font-black text-secondary-900 dark:text-white uppercase tracking-widest">Active Curriculum</span>
              <span class="block text-[10px] text-secondary-500 font-bold uppercase tracking-tight">Allow sessions and assignments for this subject.</span>
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
            {{ isEditing ? 'Save Changes' : 'Confirm Subject' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Archive Subject" 
      description="Warning: Archiving a subject will prevent new session marks but preserve historical data."
      @close="deleteModalOpen = false"
    >
      <div v-if="deletingSubject" class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Confirm deletion of <span class="font-black underline">{{ deletingSubject.name }}</span> ({{ deletingSubject.code }})?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="deleteSubject">
          Confirm Deletion
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useSubjectsStore } from '@/stores/Subjects'
import { useGradesStore } from '@/stores/grades'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { PlusIcon } from '@heroicons/vue/outline'

const subjectsStore = useSubjectsStore()
const gradesStore = useGradesStore()
const { subjects, loading } = storeToRefs(subjectsStore)

const selectedGradeId = ref('')
const filteredSubjects = computed(() => {
  if (!selectedGradeId.value) return subjects.value || []
  return (subjects.value || []).filter(s => s.grade_id === Number(selectedGradeId.value))
})

const modalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const form = ref({ name: '', code: '', grade_id: '', description: '', is_active: true })
const formErrors = ref(null)
const editingId = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  form.value = { name: '', code: '', grade_id: '', description: '', is_active: true }
  formErrors.value = null
  modalOpen.value = true
}

const editSubject = (subject) => {
  isEditing.value = true
  editingId.value = subject.id
  form.value = { 
    name: subject.name, 
    code: subject.code || '', 
    grade_id: subject.grade_id, 
    description: subject.description || '', 
    is_active: subject.is_active 
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
  try {
    let result
    if (isEditing.value) {
      result = await subjectsStore.updateSubject(editingId.value, form.value)
    } else {
      result = await subjectsStore.createSubject(form.value)
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
const deletingSubject = ref(null)

const confirmDelete = (subject) => {
  deletingSubject.value = subject
  deleteModalOpen.value = true
}

const deleteSubject = async () => {
  submitting.value = true
  const result = await subjectsStore.deleteSubject(deletingSubject.value.id)
  if (result.success) {
    deleteModalOpen.value = false
  }
  submitting.value = false
}

onMounted(async () => {
  await gradesStore.fetchGrades()
  await subjectsStore.fetchSubjects()
})
</script>