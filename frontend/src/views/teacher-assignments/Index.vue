<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Faculty Assignments</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Designate subject leads and classroom responsibilities for academic faculty.</p>
      </div>
      
      <div>
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Launch New Assignment
        </BaseButton>
      </div>
    </div>

    <!-- Multi-Filter Board -->
    <div class="glass-card p-8 rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
        <BaseSelect v-model="filters.teacher_id" label="Academic Teacher" @change="applyFilters">
          <option value="">All Faculty</option>
          <option v-for="teacher in teachersStore.teachers" :key="teacher.id" :value="teacher.id">
            {{ teacher.user?.name }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="filters.grade_id" label="Academic Grade" @change="applyFilters">
          <option value="">All Grades</option>
          <option v-for="grade in gradesStore.grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.section_id" label="Assigned Section" @change="applyFilters">
          <option value="">All Sections</option>
          <option v-for="section in filteredSections" :key="section.id" :value="section.id">{{ section.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.subject_id" label="Subject Domain" @change="applyFilters">
          <option value="">All Subjects</option>
          <option v-for="subject in filteredSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </BaseSelect>

        <div class="lg:col-span-4 flex justify-end pt-4 border-t border-secondary-100 dark:border-secondary-800">
          <BaseButton variant="ghost" size="sm" @click="resetFilters">
            Reset Analytical Matrix
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Assignment Registry -->
    <div class="glass-card rounded-[2.5rem] overflow-hidden border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div v-if="loading" class="p-20 flex justify-center">
        <LoadingSpinner text="Synchronizing faculty matrix..." />
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Lead Educator</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Institutional Level</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Class Section</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em]">Subject Domain</th>
              <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <tr v-for="assignment in assignments.filter(a => a)" :key="assignment.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-xs font-black text-secondary-400 border border-secondary-200 dark:border-secondary-700 shadow-sm">
                    {{ assignment.teacher?.user?.name.charAt(0) }}
                  </div>
                  <span class="text-sm font-black text-secondary-900 dark:text-white">{{ assignment.teacher?.user?.name }}</span>
                </div>
              </td>
              <td class="px-8 py-5">
                <BaseBadge variant="primary">{{ assignment.grade?.name }}</BaseBadge>
              </td>
              <td class="px-8 py-5">
                <span class="text-xs font-black text-secondary-600 dark:text-secondary-400 uppercase tracking-widest">{{ assignment.section?.name }}</span>
              </td>
              <td class="px-8 py-5">
                <span class="text-xs font-black text-primary-600 dark:text-primary-400 italic">"{{ assignment.subject?.name }}"</span>
              </td>
              <td class="px-8 py-5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <BaseButton variant="ghost" size="xs" @click="editAssignment(assignment)">
                    Edit
                  </BaseButton>
                  <BaseButton variant="danger-ghost" size="xs" @click="confirmDelete(assignment)">
                    Delete
                  </BaseButton>
                </div>
              </td>
            </tr>
            <tr v-if="assignments.length === 0">
              <td colspan="5" class="px-8 py-20 text-center">
                 <p class="text-secondary-400 font-bold uppercase tracking-widest text-xs">No assignments mapped in registry</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <BaseModal 
      :show="modalOpen" 
      :title="isEditing ? 'Modify Faculty Assignment' : 'New Strategic Assignment'" 
      @close="closeModal"
    >
      <form @submit.prevent="submitForm" class="space-y-6">
        <BaseSelect v-model="form.teacher_id" label="Select Lead Educator" required>
          <option value="">Choose Teacher</option>
          <option v-for="teacher in teachersStore.teachers" :key="teacher.id" :value="teacher.id">
            {{ teacher.user?.name }}
          </option>
        </BaseSelect>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <BaseSelect v-model="form.grade_id" label="Academic Level" required @change="loadSectionsAndSubjects">
            <option value="">Select Grade</option>
            <option v-for="grade in gradesStore.grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
          </BaseSelect>

          <BaseSelect v-model="form.section_id" label="Classroom Section" required :disabled="!form.grade_id">
            <option value="">Select Section</option>
            <option v-for="section in sectionsForGrade" :key="section.id" :value="section.id">{{ section.name }}</option>
          </BaseSelect>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <BaseSelect v-model="form.subject_id" label="Subject Domain" required :disabled="!form.grade_id">
            <option value="">Select Subject</option>
            <option v-for="subject in subjectsForGrade" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
          </BaseSelect>

          <BaseInput 
            v-model="form.academic_year" 
            label="Academic Term / Year" 
            type="number" 
            required 
          />
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
            {{ isEditing ? 'Save Changes' : 'Confirm Assignment' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Unmap Assignment" 
      description="Caution: Removing this mapping will dissociate the teacher from all logs and reports in this domain."
      @close="deleteModalOpen = false"
    >
      <div v-if="deletingAssignment" class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Confirm permanent removal of this strategic mapping?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="deleteAssignment">
          Confirm Unmapping
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useTeacherAssignmentsStore } from '@/stores/teacherAssignments'
import { useTeachersStore } from '@/stores/teacher'
import { useGradesStore } from '@/stores/grades'
import { useSectionsStore } from '@/stores/section'
import { useSubjectsStore } from '@/stores/Subjects'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { PlusIcon } from '@heroicons/vue/outline'

const assignmentsStore = useTeacherAssignmentsStore()
const teachersStore = useTeachersStore()
const gradesStore = useGradesStore()
const sectionsStore = useSectionsStore()
const subjectsStore = useSubjectsStore()

const { assignments, loading } = storeToRefs(assignmentsStore)

// Filters
const filters = ref({
  teacher_id: '',
  grade_id: '',
  section_id: '',
  subject_id: '',
})

const filteredSections = computed(() => {
  if (filters.value.grade_id) {
    return (sectionsStore.sections || []).filter(section => section.grade_id == filters.value.grade_id)
  }
  return sectionsStore.sections || []
})

const filteredSubjects = computed(() => {
  if (filters.value.grade_id) {
    return (subjectsStore.subjects || []).filter(subject => subject.grade_id == filters.value.grade_id)
  }
  return subjectsStore.subjects || []
})

const sectionsForGrade = computed(() => {
  const gradeIdNum = parseInt(form.value.grade_id, 10)
  if (!gradeIdNum || !(sectionsStore.sections || []).length) return []
  return sectionsStore.sections.filter((s) => parseInt(s.grade_id, 10) === gradeIdNum)
})

const subjectsForGrade = computed(() => {
  const gradeIdNum = parseInt(form.value.grade_id, 10)
  if (!gradeIdNum || !(subjectsStore.subjects || []).length) return []
  return subjectsStore.subjects.filter((s) => parseInt(s.grade_id, 10) === gradeIdNum)
})

const applyFilters = () => {
  assignmentsStore.fetchAssignments({
    teacher_id: filters.value.teacher_id,
    grade_id: filters.value.grade_id,
    section_id: filters.value.section_id,
    subject_id: filters.value.subject_id,
  })
}

const resetFilters = () => {
  filters.value = { teacher_id: '', grade_id: '', section_id: '', subject_id: '' }
  applyFilters()
}

// Modal State
const modalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const form = ref({ teacher_id: '', grade_id: '', section_id: '', subject_id: '', academic_year: new Date().getFullYear() })
const formErrors = ref(null)
const editingId = ref(null)

const openCreateModal = () => {
  isEditing.value = false
  form.value = { teacher_id: '', grade_id: '', section_id: '', subject_id: '', academic_year: new Date().getFullYear() }
  formErrors.value = null
  modalOpen.value = true
}

const editAssignment = (assignment) => {
  isEditing.value = true
  editingId.value = assignment.id
  form.value = {
    teacher_id: assignment.teacher_id,
    grade_id: assignment.grade_id,
    section_id: assignment.section_id,
    subject_id: assignment.subject_id,
    academic_year: assignment.academic_year || new Date().getFullYear(),
  }
  formErrors.value = null
  modalOpen.value = true
}

watch(() => form.value.grade_id, (newVal) => {
  if (newVal && !isEditing.value) {
    form.value.section_id = ''
    form.value.subject_id = ''
  }
})

const closeModal = () => {
  modalOpen.value = false
}

const submitForm = async () => {
  submitting.value = true
  formErrors.value = null
  try {
    let result
    if (isEditing.value) {
      result = await assignmentsStore.updateAssignment(editingId.value, form.value)
    } else {
      result = await assignmentsStore.createAssignment(form.value)
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
const deletingAssignment = ref(null)

const confirmDelete = (assignment) => {
  deletingAssignment.value = assignment
  deleteModalOpen.value = true
}

const deleteAssignment = async () => {
  submitting.value = true
  const result = await assignmentsStore.deleteAssignment(deletingAssignment.value.id)
  if (result.success) {
    deleteModalOpen.value = false
  }
  submitting.value = false
}

onMounted(async () => {
  await Promise.all([
    teachersStore.fetchTeachers(),
    gradesStore.fetchGrades(),
    sectionsStore.fetchSections(),
    subjectsStore.fetchSubjects(),
    assignmentsStore.fetchAssignments(),
  ])
})
</script>