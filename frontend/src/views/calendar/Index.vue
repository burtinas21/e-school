<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Academic Calendar</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Institutional timeline for holidays, examinations, and events.</p>
      </div>
      
      <!-- Month Navigation Controls -->
      <div class="flex items-center gap-3">
        <div class="flex items-center bg-white dark:bg-secondary-900 rounded-2xl shadow-sm border border-secondary-100 dark:border-secondary-800 p-1.5">
          <BaseButton variant="ghost" size="xs" @click="prevMonth" class="rounded-xl">
            <template #icon-left><ChevronLeftIcon class="w-4 h-4" /></template>
          </BaseButton>
          <div class="px-6 text-sm font-black text-secondary-900 dark:text-white min-w-[160px] text-center uppercase tracking-widest">
            {{ currentMonthName }} {{ currentYear }}
          </div>
          <BaseButton variant="ghost" size="xs" @click="nextMonth" class="rounded-xl">
            <template #icon-left><ChevronRightIcon class="w-4 h-4" /></template>
          </BaseButton>
        </div>

        <div v-if="canManage" class="hidden md:block">
          <BaseButton variant="primary" size="lg" shadow @click="openCreateModal">
            <template #icon-left><PlusIcon class="w-5 h-5" /></template>
            New Event
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Mobile Action Button -->
    <div v-if="canManage" class="md:hidden">
       <BaseButton variant="primary" class="w-full" @click="openCreateModal">
         <template #icon-left><PlusIcon class="w-5 h-5" /></template>
         New Event
       </BaseButton>
    </div>

    <!-- Main Calendar Grid -->
    <CalendarGrid 
      :month-dates="monthDates"
      :events="events"
      :loading="loading"
      @view-event="viewEvent"
    />

    <!-- Legend Section -->
    <div class="flex flex-wrap items-center gap-6 pt-4">
      <div v-for="type in eventTypes" :key="type.label" class="flex items-center gap-2">
        <div class="w-3 h-3 rounded-full" :class="type.color"></div>
        <span class="text-[10px] font-black text-secondary-400 uppercase tracking-widest">{{ type.label }}</span>
      </div>
    </div>

    <!-- Modals -->
    <EventModal 
      :show="modalOpen"
      :is-editing="isEditing"
      :initial-data="editingEvent"
      :submitting="submitting"
      :errors="formErrors"
      @close="closeModal"
      @submit="handleFormSubmit"
    />

    <EventDetailModal 
      :show="viewModalOpen"
      :event="viewingEvent"
      :can-manage="canManage"
      @close="viewModalOpen = false"
      @edit="handleEditFromView"
      @delete="confirmDelete"
    />

    <!-- Delete Confirmation -->
    <BaseModal 
      :show="deleteModalOpen" 
      title="Delete Calendar Event" 
      description="This will permanently remove the event from the academic timeline. If attendance was blocked, it will be restored."
      @close="deleteModalOpen = false"
    >
      <div v-if="eventToDelete" class="p-6 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-3xl mb-6">
        <p class="text-sm font-bold text-rose-700 dark:text-rose-300">
          Confirm permanent removal of <span class="font-black underline">{{ eventToDelete.title }}</span>?
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
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useCalendarEventsStore } from '@/stores/calendarEvents'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import CalendarGrid from '@/components/calendar/CalendarGrid.vue'
import EventModal from '@/components/calendar/EventModal.vue'
import EventDetailModal from '@/components/calendar/EventDetailModal.vue'
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from '@heroicons/vue/outline'

// Stores
const authStore = useAuthStore()
const calendarStore = useCalendarEventsStore()
const { events, loading } = storeToRefs(calendarStore)

// State
const currentDate = ref(new Date())
const modalOpen = ref(false)
const viewModalOpen = ref(false)
const deleteModalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const editingEvent = ref(null)
const viewingEvent = ref(null)
const eventToDelete = ref(null)
const formErrors = ref(null)

const canManage = computed(() => authStore.userRole === 1)

const eventTypes = [
  { label: 'General Event', color: 'bg-primary-500' },
  { label: 'Official Holiday', color: 'bg-rose-500' },
  { label: 'Examination', color: 'bg-emerald-500' },
  { label: 'School Closure', color: 'bg-secondary-900 dark:bg-white' },
]

// Date Computeds
const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonth = computed(() => currentDate.value.getMonth() + 1)
const currentMonthName = computed(() => currentDate.value.toLocaleString('default', { month: 'long' }))

const monthDates = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month - 1, 1)
  const lastDay = new Date(year, month, 0)
  
  const dates = []
  
  // Fill leading days
  const startDay = firstDay.getDay()
  const prevLastDay = new Date(year, month - 1, 0).getDate()
  for (let i = startDay - 1; i >= 0; i--) {
    const d = new Date(year, month - 2, prevLastDay - i)
    dates.push(createDateObj(d, false))
  }
  
  // Current month
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const d = new Date(year, month - 1, i)
    dates.push(createDateObj(d, true))
  }
  
  // Trailing days
  const remaining = 42 - dates.length
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(year, month, i)
    dates.push(createDateObj(d, false))
  }
  
  return dates
})

const createDateObj = (date, isCurrentMonth) => {
  const dStr = date.toISOString().split('T')[0]
  return {
    date: new Date(date),
    dateStr: dStr,
    day: date.getDate(),
    isCurrentMonth,
    isToday: dStr === new Date().toISOString().split('T')[0]
  }
}

// Actions
const loadEvents = () => {
  calendarStore.fetchMonthEvents(currentYear.value, currentMonth.value)
}

const prevMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 2, 1)
  loadEvents()
}

const nextMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value, 1)
  loadEvents()
}

const openCreateModal = () => {
  isEditing.value = false
  editingEvent.value = null
  formErrors.value = null
  modalOpen.value = true
}

const viewEvent = (event) => {
  viewingEvent.value = event
  viewModalOpen.value = true
}

const handleEditFromView = (event) => {
  isEditing.value = true
  editingEvent.value = { 
    ...event,
    is_recurring: !!event.is_recurring,
    affects_attendance: !!event.affects_attendance
  }
  formErrors.value = null
  viewModalOpen.value = false
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
      result = await calendarStore.updateEvent(editingEvent.value.id, formData)
    } else {
      result = await calendarStore.createEvent(formData)
    }

    if (result.success) {
      modalOpen.value = false
      loadEvents()
    } else {
      formErrors.value = result.errors
    }
  } catch (err) {
    formErrors.value = { general: ['Submission failed. Please try again.'] }
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (event) => {
  eventToDelete.value = event
  viewModalOpen.value = false
  deleteModalOpen.value = true
}

const handleDelete = async () => {
  submitting.value = true
  const result = await calendarStore.deleteEvent(eventToDelete.value.id)
  if (result.success) {
    deleteModalOpen.value = false
    loadEvents()
  }
  submitting.value = false
}

onMounted(() => {
  loadEvents()
})
</script>