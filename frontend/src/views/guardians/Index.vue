<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Guardian Network</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Manage parent and legal guardian profiles linked to the student body.</p>
      </div>
      
      <div v-if="canCreate">
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Register Guardian
        </BaseButton>
      </div>
    </div>

    <!-- Main Content Table -->
    <GuardianTable 
      :guardians="guardians"
      :meta="meta"
      :loading="loading"
      v-model:search="filters.search"
      :can-delete="canDelete"
      @reset="resetFilters"
      @edit="editGuardian"
      @delete="confirmDelete"
      @page-change="goToPage"
    />

    <!-- Create/Edit Form Modal -->
    <GuardianForm 
      :show="modalOpen"
      :is-editing="isEditing"
      :initial-data="editingGuardian"
      :submitting="submitting"
      :errors="formErrors"
      @close="closeModal"
      @submit="handleFormSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Unlink Guardian" 
      description="Removing a guardian profile will unlink them from their associated children. This action is permanent."
      @close="deleteModalOpen = false"
    >
      <div v-if="guardianToDelete" class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Are you sure you want to delete <span class="font-black underline">{{ guardianToDelete.user?.name }}</span>?
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
import { useAuthStore } from '@/stores/auth'
import { useGuardiansStore } from '@/stores/Guardian'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import GuardianTable from '@/components/guardians/GuardianTable.vue'
import GuardianForm from '@/components/guardians/GuardianForm.vue'
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
const guardiansStore = useGuardiansStore()
const { guardians, meta, loading } = storeToRefs(guardiansStore)

// Permissions
const canCreate = computed(() => authStore.userRole === 1)
const canDelete = computed(() => authStore.userRole === 1)

// Filters & Pagination
const filters = ref({
  search: '',
})

const applyFilters = () => {
  guardiansStore.fetchGuardians({
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
const editingGuardian = ref(null)
const submitting = ref(false)
const formErrors = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  editingGuardian.value = null
  formErrors.value = null
  modalOpen.value = true
}

const editGuardian = (guardian) => {
  isEditing.value = true
  editingGuardian.value = {
    id: guardian.id,
    name: guardian.user?.name || '',
    email: guardian.user?.email || '',
    password: '',
    phone: guardian.user?.phone || '',
    relationship: guardian.relationship || 'father',
    occupation: guardian.occupation || '',
    gender: guardian.gender || 'male',
    address: guardian.address || ''
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
      result = await guardiansStore.updateGuardian(editingGuardian.value.id, formData)
    } else {
      result = await guardiansStore.createGuardian(formData)
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
const guardianToDelete = ref(null)

const confirmDelete = (guardian) => {
  guardianToDelete.value = guardian
  deleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!guardianToDelete.value) return
  submitting.value = true
  
  const result = await guardiansStore.deleteGuardian(guardianToDelete.value.id)
  if (result.success) {
    deleteModalOpen.value = false
    applyFilters()
  } else {
    alert(result.message || 'Deletion failed')
  }
  submitting.value = false
}

import { computed } from 'vue'

onMounted(() => {
  applyFilters()
})
</script>
