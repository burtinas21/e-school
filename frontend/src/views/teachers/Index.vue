<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Faculty Management</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Configure and manage teacher profiles, qualifications, and employment records.</p>
      </div>
      
      <div>
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Add Faculty Member
        </BaseButton>
      </div>
    </div>

    <!-- Main Content Table -->
    <TeacherTable 
      :teachers="teachers"
      :meta="meta"
      :loading="loading"
      v-model:search="filters.search"
      @reset="resetFilters"
      @edit="editTeacher"
      @delete="confirmDelete"
      @page-change="goToPage"
    />

    <!-- Create/Edit Form Modal -->
    <TeacherForm 
      :show="modalOpen"
      :is-editing="isEditing"
      :initial-data="editingTeacher"
      :submitting="submitting"
      :errors="formErrors"
      @close="closeModal"
      @submit="handleFormSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Revoke Faculty Access" 
      description="Deleting a teacher record will remove their access to the system and archive their historical assignments."
      @close="deleteModalOpen = false"
    >
      <div v-if="teacherToDelete" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Are you sure you want to delete <span class="font-black underline">{{ teacherToDelete.user?.name }}</span>?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="handleDelete">
          Permanently Delete
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useTeachersStore } from '@/stores/teacher'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import TeacherTable from '@/components/teachers/TeacherTable.vue'
import TeacherForm from '@/components/teachers/TeacherForm.vue'
import { PlusIcon } from '@heroicons/vue/outline'

// Debounce Utility
const debounce = (fn, delay) => {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn.apply(null, args), delay)
  }
}

// Store
const teachersStore = useTeachersStore()
const { teachers, meta, loading } = storeToRefs(teachersStore)

// Filters & Pagination
const filters = ref({
  search: '',
})

const applyFilters = () => {
  teachersStore.fetchTeachers({
    search: filters.value.search,
    page: meta.value.current_page || 1,
    per_page: meta.value.per_page || 10
  })
}

const debouncedSearch = debounce(applyFilters, 500)
watch(() => filters.value.search, debouncedSearch)

const resetFilters = () => {
  filters.value.search = ''
  applyFilters()
}

const goToPage = (page) => {
  meta.value.current_page = page
  applyFilters()
}

// Modal & Form State
const modalOpen = ref(false)
const isEditing = ref(false)
const editingTeacher = ref(null)
const submitting = ref(false)
const formErrors = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  editingTeacher.value = null
  formErrors.value = null
  modalOpen.value = true
}

const editTeacher = (teacher) => {
  isEditing.value = true
  editingTeacher.value = {
    id: teacher.id,
    name: teacher.user?.name || '',
    email: teacher.user?.email || '',
    password: '',
    phone: teacher.user?.phone || '',
    employee_id: teacher.employee_id || '',
    qualification: teacher.qualification || '',
    hire_date: teacher.hire_date ? teacher.hire_date.split('T')[0] : '',
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
      result = await teachersStore.updateTeacher(editingTeacher.value.id, formData)
    } else {
      result = await teachersStore.createTeacher(formData)
    }

    if (result.success) {
      modalOpen.value = false
      applyFilters()
    } else {
      formErrors.value = result.errors || { general: [result.message] }
    }
  } catch (err) {
    formErrors.value = { general: ['An unexpected error occurred.'] }
  } finally {
    submitting.value = false
  }
}

// Delete Logic
const deleteModalOpen = ref(false)
const teacherToDelete = ref(null)

const confirmDelete = (teacher) => {
  teacherToDelete.value = teacher
  deleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!teacherToDelete.value) return
  submitting.value = true
  
  const result = await teachersStore.deleteTeacher(teacherToDelete.value.id)
  if (result.success) {
    deleteModalOpen.value = false
    applyFilters()
  } else {
    alert(result.message || 'Deletion failed')
  }
  submitting.value = false
}

// Lifecycle
onMounted(() => {
  applyFilters()
})
</script>