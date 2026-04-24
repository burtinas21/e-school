<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Students Registry</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Manage student enrollment, profiles, and academic assignments.</p>
      </div>
      
      <div v-if="canCreate">
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Register Student
        </BaseButton>
      </div>
    </div>

    <!-- Main Content Table -->
    <StudentTable 
      :students="students"
      :meta="meta"
      :loading="loading"
      :grades="grades"
      :sections="sections"
      v-model:search="filters.search"
      v-model:grade-id="filters.grade_id"
      v-model:section-id="filters.section_id"
      :can-delete="canDelete"
      @reset="resetFilters"
      @edit="editStudent"
      @delete="confirmDelete"
      @page-change="goToPage"
    />

    <!-- Create/Edit Form Modal -->
    <StudentForm 
      :show="modalOpen"
      :is-editing="isEditing"
      :initial-data="editingStudent"
      :grades="grades"
      :sections="sections"
      :guardians="guardians"
      :submitting="submitting"
      :errors="formErrors"
      @close="closeModal"
      @submit="handleFormSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Confirm Deletion" 
      description="This action is permanent and will remove all student related records from the system."
      @close="deleteModalOpen = false"
    >
      <div v-if="studentToDelete" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Are you sure you want to delete <span class="font-black underline">{{ studentToDelete.user?.name }}</span>?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="handleDelete">
          Delete student
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useStudentsStore } from '@/stores/students'
import { useGradesStore } from '@/stores/grades'
import { useSectionsStore } from '@/stores/section'
import { useGuardiansStore } from '@/stores/Guardian'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import StudentTable from '@/components/students/StudentTable.vue'
import StudentForm from '@/components/students/StudentForm.vue'
import { PlusIcon } from '@heroicons/vue/outline'

// Debounce Utility
const debounce = (fn, delay) => {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn.apply(null, args), delay)
  }
}

// Stores
const authStore = useAuthStore()
const studentsStore = useStudentsStore()
const gradesStore = useGradesStore()
const sectionsStore = useSectionsStore()
const guardiansStore = useGuardiansStore()

// State
const { students, meta, loading } = storeToRefs(studentsStore)
const { grades } = storeToRefs(gradesStore)
const { sections } = storeToRefs(sectionsStore)
const { guardians } = storeToRefs(guardiansStore)

const canCreate = computed(() => authStore.userRole === 1)
const canDelete = computed(() => authStore.userRole === 1)

// Filters & Pagination
const filters = ref({
  search: '',
  grade_id: '',
  section_id: '',
})

const applyFilters = () => {
  studentsStore.fetchStudents({
    search: filters.value.search,
    grade_id: filters.value.grade_id,
    section_id: filters.value.section_id,
    page: meta.value.current_page || 1,
    per_page: meta.value.per_page || 10
  })
}

const debouncedSearch = debounce(applyFilters, 500)
watch(() => filters.value.search, debouncedSearch)
watch([() => filters.value.grade_id, () => filters.value.section_id], applyFilters)

const resetFilters = () => {
  filters.value = { search: '', grade_id: '', section_id: '' }
  applyFilters()
}

const goToPage = (page) => {
  meta.value.current_page = page
  applyFilters()
}

// Modal & Form State
const modalOpen = ref(false)
const isEditing = ref(false)
const editingStudent = ref(null)
const submitting = ref(false)
const formErrors = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  editingStudent.value = null
  formErrors.value = null
  modalOpen.value = true
}

const editStudent = (student) => {
  isEditing.value = true
  editingStudent.value = {
    id: student.id,
    name: student.user?.name || '',
    email: student.user?.email || '',
    password: '',
    phone: student.user?.phone || '',
    grade_id: student.grade_id || '',
    section_id: student.section_id || '',
    admission_number: student.admission_number || '',
    guardian_id: student.guardian_id || '',
    date_of_birth: student.date_of_birth ? student.date_of_birth.split('T')[0] : '',
    gender: student.gender || '',
  }
  formErrors.value = null
  modalOpen.value = true
}

const closeModal = () => {
  modalOpen.value = false
}

const handleFormSubmit = async (formData) => {
  submitting.value = true
  formErrors.value = null
  
  try {
    let result
    if (isEditing.value) {
      if (!formData.password) delete formData.password
      result = await studentsStore.updateStudent(editingStudent.value.id, formData)
    } else {
      result = await studentsStore.createStudent(formData)
    }

    if (result.success) {
      modalOpen.value = false
      // Refresh list
      applyFilters()
    } else {
      formErrors.value = result.errors || { message: [result.message] }
    }
  } catch (err) {
    formErrors.value = { general: ['An unexpected error occurred.'] }
  } finally {
    submitting.value = false
  }
}

// Delete Logic
const deleteModalOpen = ref(false)
const studentToDelete = ref(null)

const confirmDelete = (student) => {
  studentToDelete.value = student
  deleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!studentToDelete.value) return
  submitting.value = true
  
  const result = await studentsStore.deleteStudent(studentToDelete.value.id)
  if (result.success) {
    deleteModalOpen.value = false
    applyFilters()
  } else {
    alert(result.message || 'Deletion failed')
  }
  submitting.value = false
}

// Lifecycle
onMounted(async () => {
  // Ensure we have current permissions
  if (!authStore.user) await authStore.fetchUser()
  
  await Promise.all([
    gradesStore.fetchGrades(),
    guardiansStore.fetchGuardians(),
    sectionsStore.fetchSections(),
    applyFilters()
  ])
})
</script>