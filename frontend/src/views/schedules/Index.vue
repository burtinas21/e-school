<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Academic Timetable</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Strategic orchestration of sessions, classrooms, and faculty resources.</p>
      </div>
      
      <div v-if="canManage">
        <BaseButton variant="primary" size="lg" shadow @click="openCreateModal()">
          <template #icon-left><PlusIcon class="w-5 h-5" /></template>
          Draft Schedule Entry
        </BaseButton>
      </div>
    </div>

    <!-- Section Selection Matrix (Admin Only) -->
    <div v-if="isAdmin" class="glass-card p-6 rounded-3xl border border-secondary-100 dark:border-secondary-800 shadow-sm flex items-end gap-4 max-w-md">
      <BaseSelect v-model="selectedSectionId" label="Academic Section Focus" @change="loadSchedule">
        <option value="">-- Targeted Selection --</option>
        <option v-for="section in (sectionsStore.sections || []).filter(s => s)" :key="section.id" :value="section.id">
          {{ section.grade?.name }} — {{ section.name }}
        </option>
      </BaseSelect>
    </div>

    <!-- Weekly Orchestration View -->
    <div v-if="scheduleData.length || (isAdmin && selectedSectionId)" class="glass-card rounded-[2.5rem] overflow-hidden border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
              <th class="px-8 py-6 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] border-r border-secondary-100 dark:border-secondary-800 bg-secondary-50/80 dark:bg-secondary-900/80 sticky left-0 z-10">Time Vector</th>
              <th v-for="day in weekDays" :key="day" class="px-8 py-6 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] min-w-[200px] text-center">
                {{ day }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
            <tr v-for="period in timeSlots" :key="period.id" class="group transition-colors">
              <td class="px-8 py-8 border-r border-secondary-100 dark:border-secondary-800 bg-secondary-50/30 dark:bg-secondary-900/30 sticky left-0 z-10 backdrop-blur-md">
                <div class="flex flex-col">
                  <span class="text-xs font-black text-secondary-900 dark:text-white uppercase tracking-widest">{{ period.name }}</span>
                  <span class="text-[10px] font-bold text-secondary-400 font-mono mt-1">{{ formatTime(period.start_time) }} — {{ formatTime(period.end_time) }}</span>
                </div>
              </td>
              <td v-for="day in weekDays" :key="day" class="p-2 border-r border-secondary-50 dark:border-secondary-800/50">
                <div v-if="getScheduleEntry(day, period)" class="h-full p-4 rounded-3xl bg-white dark:bg-secondary-900 border border-secondary-100 dark:border-secondary-800 shadow-sm group-hover:shadow-md transition-all duration-300 relative overflow-hidden">
                   <!-- Visual Accent -->
                   <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary-500"></div>
                   
                   <div class="flex flex-col h-full">
                      <h4 class="text-sm font-black text-secondary-900 dark:text-white leading-tight mb-2">{{ getScheduleEntry(day, period).subject?.name }}</h4>
                      
                      <div class="space-y-1 mt-auto">
                        <div class="flex items-center gap-2 text-[10px] font-bold text-secondary-500 dark:text-secondary-400">
                          <UserIcon class="w-3 h-3 text-secondary-300" />
                          <span class="truncate">{{ getScheduleEntry(day, period).teacher?.user?.name }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-bold text-secondary-500 dark:text-secondary-400">
                          <LocationMarkerIcon class="w-3 h-3 text-secondary-300" />
                          <span>{{ getScheduleEntry(day, period).room || 'Main Hall' }}</span>
                        </div>
                      </div>

                      <div v-if="canManage" class="absolute top-2 right-2 flex gap-1 transform translate-x-12 group-hover:translate-x-0 transition-transform duration-300">
                        <button @click="editSchedule(getScheduleEntry(day, period))" class="p-1.5 bg-secondary-50 dark:bg-secondary-800 rounded-lg text-secondary-400 hover:text-primary-600">
                          <PencilIcon class="w-3.5 h-3.5" />
                        </button>
                        <button @click="confirmDelete(getScheduleEntry(day, period))" class="p-1.5 bg-rose-50 dark:bg-rose-900/30 rounded-lg text-rose-400 hover:text-rose-600">
                          <TrashIcon class="w-3.5 h-3.5" />
                        </button>
                      </div>
                   </div>
                </div>
                <!-- Empty State / Add Trigger -->
                <div v-else-if="canManage" class="h-full group/cell">
                  <button 
                    @click="openCreateModalForSlot(day, period)" 
                    class="w-full h-full min-h-[100px] border-2 border-dashed border-secondary-100 dark:border-secondary-800 rounded-3xl flex items-center justify-center opacity-0 group-hover/cell:opacity-100 hover:border-primary-300 hover:bg-primary-50/30 dark:hover:bg-primary-900/5 transition-all text-primary-500 font-black text-xs uppercase tracking-widest"
                  >
                    Allocate Slot
                  </button>
                </div>
                <div v-else class="h-full flex items-center justify-center text-secondary-200 dark:text-secondary-800 text-xs font-black tracking-widest">
                  —
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty Loading/Selection State -->
    <div v-if="loading" class="p-20 glass-card rounded-[3rem] text-center">
       <LoadingSpinner text="Drafting the institutional matrix..." />
    </div>
    <div v-else-if="!scheduleData.length && selectedSectionId && isAdmin" class="p-20 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
       <div class="w-20 h-20 bg-secondary-50 dark:bg-secondary-800 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
         <CalendarIcon class="w-10 h-10 text-secondary-300 dark:text-secondary-600" />
       </div>
       <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">Timeline Deficiency</h4>
       <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto">No strategic sessions have been allocated for this section. Initiate planning with the action button above.</p>
    </div>
    <div v-else-if="isAdmin && !selectedSectionId" class="p-20 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
       <div class="w-20 h-20 bg-primary-50 dark:bg-primary-900/20 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
         <CursorClickIcon class="w-10 h-10 text-primary-300" />
       </div>
       <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">Target Selection Required</h4>
       <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto">Select a specific academic section to analyze or modify its operational timetable.</p>
    </div>

    <!-- Modal Create/Edit -->
    <BaseModal 
      :show="modalOpen" 
      :title="isEditing ? 'Modify Schedule Entry' : 'New Operational Slot'" 
      @close="closeModal"
    >
      <form @submit.prevent="submitForm" class="space-y-6">
        <div v-if="isAdmin" class="p-4 bg-primary-50 dark:bg-primary-900/10 rounded-2xl border border-primary-100 dark:border-primary-800/30">
          <BaseSelect v-model="form.section_id" label="Designated Section" required>
            <option value="">Choose Section</option>
            <option v-for="section in (sectionsStore.sections || []).filter(s => s)" :key="section.id" :value="section.id">
              {{ section.grade?.name }} — {{ section.name }}
            </option>
          </BaseSelect>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <BaseSelect v-model="form.day_of_week" label="Weekly Vector" required>
            <option value="">Select Day</option>
            <option v-for="day in weekDays" :key="day" :value="day">{{ day }}</option>
          </BaseSelect>

          <BaseSelect v-model="form.period_id" label="Institutional Period" required>
            <option value="">Select Slot</option>
            <option v-for="period in timeSlots" :key="period.id" :value="period.id">
               {{ period.name }} ({{ formatTime(period.start_time) }} — {{ formatTime(period.end_time) }})
            </option>
          </BaseSelect>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <BaseSelect v-model="form.subject_id" label="Academic Domain" required @change="updateTeacherList">
            <option value="">Select Subject</option>
            <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
          </BaseSelect>

          <BaseSelect v-model="form.teacher_id" label="Lead Faculty" required :disabled="!form.subject_id">
            <option value="">Select Teacher</option>
            <option v-for="teacher in availableTeachers" :key="teacher.id" :value="teacher.id">{{ teacher.user?.name }}</option>
          </BaseSelect>
        </div>

        <BaseInput 
          v-model="form.room" 
          label="Physical Allocation (Room)" 
          placeholder="e.g. Laboratory 201 / Hall A"
        />

        <div v-if="formErrors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
           <ul class="space-y-1">
             <li v-for="(errs, field) in formErrors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
               <span class="capitalize">{{ field }}:</span> {{ Array.isArray(errs) ? errs.join(', ') : errs }}
             </li>
           </ul>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-secondary-100 dark:border-secondary-800">
          <BaseButton variant="ghost" type="button" @click="closeModal">Cancel</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">
            {{ isEditing ? 'Save Changes' : 'Confirm Allocation' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Deallocate Slot" 
      description="Caution: Removing this schedule entry will dissociate the subject and teacher from this time vector."
      @close="deleteModalOpen = false"
    >
      <div class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6 text-center">
        <TrashIcon class="w-12 h-12 text-rose-500 mx-auto mb-4" />
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Permanently remove this operational allocation?
        </p>
      </div>
      
      <template #footer>
        <BaseButton variant="ghost" @click="deleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="deleteSchedule">
          Confirm Deletion
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useSchedulesStore } from '@/stores/schedules'
import { useSectionsStore } from '@/stores/section'
import { useSubjectsStore } from '@/stores/Subjects'
import { useTeachersStore } from '@/stores/teacher'
import { usePeriodsStore } from '@/stores/periods'
import axiosInstance from '@/utils/axios'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { 
  PlusIcon, ClockIcon, UserIcon, LocationMarkerIcon, 
  PencilIcon, TrashIcon, CalendarIcon, CursorClickIcon 
} from '@heroicons/vue/outline'

// --- Stores ---
const authStore = useAuthStore()
const schedulesStore = useSchedulesStore()
const sectionsStore = useSectionsStore()
const subjectsStore = useSubjectsStore()
const teachersStore = useTeachersStore()
const periodsStore = usePeriodsStore()

const { schedules, loading } = storeToRefs(schedulesStore)
const { userRole } = authStore

// --- Constants ---
const weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']

const timeSlots = computed(() => {
  const periodsResult = periodsStore.periods || []
  const arr = Array.isArray(periodsResult) ? periodsResult : []
  return arr
    .filter(period => period && !period.is_break)
    .sort((a, b) => a.period_number - b.period_number)
    .filter(p => p && p.id)
})

const isAdmin = computed(() => userRole === 1)
const canManage = computed(() => userRole === 1)

const selectedSectionId = ref('')
const scheduleData = computed(() => {
  if (Array.isArray(schedules.value)) return schedules.value
  if (!schedules.value) return []
  return Object.values(schedules.value).flat()
})

const getScheduleEntry = (day, period) => {
  const data = Array.isArray(schedules.value) ? schedules.value : []
  return data.find(entry => 
    entry.day_of_week === day && 
    (entry.period_id === period.id || entry.time_slot === period.time_range)
  )
}

const loadSchedule = async () => {
  if (isAdmin.value && selectedSectionId.value) {
    await schedulesStore.fetchBySection(selectedSectionId.value)
  } else if (!isAdmin.value) {
    const teacher = (teachersStore.teachers || []).find(t => t && t.user_id === authStore.user?.id)
    if (teacher) {
      await schedulesStore.fetchByTeacher(teacher.id)
    }
  }
}

// Modal State
const modalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const form = ref({ section_id: '', day_of_week: '', period_id: '', subject_id: '', teacher_id: '', room: '' })
const editingId = ref(null)
const formErrors = ref(null)

const availableSubjects = computed(() => {
  if (!form.value.section_id) return (subjectsStore.subjects || []).filter(s => s)
  const section = (sectionsStore.sections || []).find(s => s && s.id === Number(form.value.section_id))
  if (!section) return (subjectsStore.subjects || []).filter(s => s)
  return (subjectsStore.subjects || []).filter(s => s && s.grade_id === section.grade_id)
})

const availableTeachers = ref([])

const updateTeacherList = async () => {
  if (!form.value.subject_id || !form.value.section_id) {
    availableTeachers.value = []
    return
  }
  try {
    const response = await axiosInstance.get(`/api/teacher-assignments/by-subject/${form.value.subject_id}`)
    const assignments = response.data.data || []
    const teacherIds = assignments.map(a => a.teacher_id)
    availableTeachers.value = (teachersStore.teachers || []).filter(t => t && teacherIds.includes(t.id))
  } catch (err) {
    availableTeachers.value = []
  }
}

const openCreateModal = (day = null, timeSlot = null) => {
  isEditing.value = false
  editingId.value = null
  form.value = {
    section_id: selectedSectionId.value || '',
    day_of_week: day || '',
    period_id: timeSlot?.id || '',
    subject_id: '',
    teacher_id: '',
    room: '',
  }
  formErrors.value = null
  modalOpen.value = true
}

const openCreateModalForSlot = (day, period) => openCreateModal(day, period)

const editSchedule = (schedule) => {
  isEditing.value = true
  editingId.value = schedule.id
  form.value = {
    section_id: schedule.section_id,
    day_of_week: schedule.day_of_week,
    period_id: schedule.period_id || schedule.period?.id || '',
    subject_id: schedule.subject_id,
    teacher_id: schedule.teacher_id,
    room: schedule.room || '',
  }
  formErrors.value = null
  modalOpen.value = true
  updateTeacherList()
}

const closeModal = () => {
  modalOpen.value = false
}

const submitForm = async () => {
  submitting.value = true
  formErrors.value = null
  const data = { ...form.value }

  if (data.section_id) {
    const section = (sectionsStore.sections || []).find(s => s.id === Number(data.section_id))
    if (section) data.grade_id = section.grade_id
  }

  try {
    let result
    if (isEditing.value) {
      result = await schedulesStore.update(editingId.value, data)
    } else {
      result = await schedulesStore.create(data)
    }

    if (result.success) {
      modalOpen.value = false
      await loadSchedule()
    } else {
      formErrors.value = result.errors
    }
  } catch (err) {
    formErrors.value = { general: ['Matrix submission failed.'] }
  } finally {
    submitting.value = false
  }
}

const deleteModalOpen = ref(false)
const scheduleToDeleteId = ref(null)

const confirmDelete = (schedule) => {
  scheduleToDeleteId.value = schedule.id
  deleteModalOpen.value = true
}

const deleteSchedule = async () => {
  submitting.value = true
  const result = await schedulesStore.delete(scheduleToDeleteId.value)
  if (result.success) {
    deleteModalOpen.value = false
    await loadSchedule()
  }
  submitting.value = false
}

const formatTime = (time) => {
  if (!time) return ''
  return time.substring(0, 5)
}

onMounted(async () => {
  await Promise.all([
    sectionsStore.fetchSections(),
    subjectsStore.fetchSubjects(),
    teachersStore.fetchTeachers(),
    periodsStore.fetchPeriods(),
  ])
  if (!isAdmin.value) await loadSchedule()
})
</script>