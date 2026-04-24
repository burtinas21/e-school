<template>
  <div class="space-y-8 pb-12 overflow-x-hidden">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Mark Attendance</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Select class parameters to record student presence for today's sessions.</p>
      </div>
      
      <div v-if="attendanceStore.classAttendance.length" class="flex items-center gap-3 bg-white dark:bg-secondary-900 p-2 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-premium transition-all">
         <div class="px-5 py-2 text-center border-r border-secondary-100 dark:border-secondary-800">
            <span class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest leading-none mb-1">Total</span>
            <span class="text-lg font-black text-secondary-900 dark:text-white leading-none">{{ attendanceStore.classAttendance.length }}</span>
         </div>
         <div class="px-5 py-2 text-center border-r border-secondary-100 dark:border-secondary-800">
            <span class="block text-[10px] font-black text-emerald-500 uppercase tracking-widest leading-none mb-1">Present</span>
            <span class="text-lg font-black text-emerald-600 leading-none">{{ presentCount }}</span>
         </div>
         <div class="px-5 py-2 text-center">
            <span class="block text-[10px] font-black text-rose-500 uppercase tracking-widest leading-none mb-1">Absent</span>
            <span class="text-lg font-black text-rose-600 leading-none">{{ absentCount }}</span>
         </div>
      </div>
    </div>

    <!-- Error state -->
    <Transition name="fade">
      <div v-if="error" class="bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 p-4 rounded-xl shadow-sm flex items-center gap-3">
        <ExclamationIcon class="h-6 w-6 text-rose-500" />
        <span class="text-sm text-rose-700 dark:text-rose-400 font-bold">{{ error }}</span>
        <button @click="error = ''" class="ml-auto text-rose-400 hover:text-rose-600">
          <XIcon class="w-4 h-4" />
        </button>
      </div>
    </Transition>

    <!-- Selection Filters -->
    <div class="glass-card p-8 rounded-[2rem] grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 items-end">
      <BaseSelect 
        label="Grade Level" 
        v-model="form.grade_id" 
        @change="onGradeChange"
      >
        <option value="">Select Grade</option>
        <option v-for="g in gradesStore.grades" :key="g.id" :value="g.id">{{ g.name }}</option>
      </BaseSelect>

      <BaseSelect 
        label="Section" 
        v-model="form.section_id" 
        :disabled="!form.grade_id"
      >
        <option value="">Select Section</option>
        <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
      </BaseSelect>

      <BaseSelect 
        label="Subject" 
        v-model="form.subject_id" 
        :disabled="!form.grade_id"
      >
        <option value="">Select Subject</option>
        <option v-for="subj in filteredSubjects" :key="subj.id" :value="subj.id">{{ subj.name }}</option>
      </BaseSelect>

      <BaseSelect 
        label="Period" 
        v-model="form.period_id"
      >
        <option value="">Select Period</option>
        <option v-for="p in availablePeriods" :key="p?.id" :value="p?.id">
          {{ p?.name }} ({{ p?.start_time }})
        </option>
      </BaseSelect>

      <BaseInput 
        label="Session Date" 
        type="date" 
        v-model="form.date" 
      />

      <div class="lg:col-span-5 flex justify-end pt-4 border-t border-secondary-100 dark:border-secondary-800">
        <BaseButton 
          variant="primary" 
          size="lg"
          @click="loadStudents" 
          :disabled="!isFormValid"
          :loading="attendanceStore.loading"
        >
          Initialize Student List
        </BaseButton>
      </div>
    </div>

    <!-- Marking Table Section -->
    <Transition name="slide-up">
      <div v-if="attendanceStore.classAttendance.length" class="glass-card rounded-[2.5rem] overflow-hidden">
        <!-- Table Control Header -->
        <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex flex-col sm:flex-row sm:items-center justify-between gap-6 bg-secondary-50/20 dark:bg-secondary-800/20">
          <div class="relative w-full sm:w-80">
             <BaseInput 
                v-model="searchQuery" 
                placeholder="Filter students by name or ID..." 
             >
                <template #icon><SearchIcon class="w-4 h-4" /></template>
             </BaseInput>
          </div>
          
          <div class="flex gap-3">
             <BaseButton variant="secondary" size="md" @click="markAll('present')">
               Mark All Present
             </BaseButton>
             <BaseButton variant="outline" size="md" @click="markAll('absent')">
               Reset All
             </BaseButton>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Student Registration</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-center">Status Selection</th>
                <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Internal Remarks</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
              <AttendanceRow 
                v-for="student in filteredStudents" 
                :key="student.student_id"
                :student="student"
                v-model:status="student.status"
                v-model:remarks="student.remarks"
              />
            </tbody>
          </table>
        </div>
        
        <!-- Action Footer -->
        <div class="px-10 py-10 bg-secondary-50/50 dark:bg-secondary-800/50 border-t border-secondary-100 dark:border-secondary-800 flex flex-col sm:flex-row items-center justify-between gap-8">
           <div class="flex items-center gap-6">
              <div class="flex items-center gap-2 text-xs font-black text-secondary-400 uppercase tracking-widest">
                 <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-200"></div> Present 
              </div>
              <div class="flex items-center gap-2 text-xs font-black text-secondary-400 uppercase tracking-widest">
                 <div class="w-3 h-3 rounded-full bg-rose-500 shadow-sm shadow-rose-200"></div> Absent
              </div>
              <div class="flex items-center gap-2 text-xs font-black text-secondary-400 uppercase tracking-widest">
                 <div class="w-3 h-3 rounded-full bg-amber-500 shadow-sm shadow-amber-200"></div> Late
              </div>
           </div>
           
           <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
             <BaseButton 
              variant="primary" 
              size="lg"
              class="px-12"
              @click="submitAttendance" 
              :disabled="submitting || !attendanceStore.classAttendance.length"
              :loading="submitting"
             >
                {{ submitting ? 'Finalizing Logs...' : 'Submit Records' }}
             </BaseButton>
           </div>
        </div>
      </div>
    </Transition>

    <!-- Empty State -->
    <div v-else-if="!attendanceStore.loading" class="p-20 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
       <div class="w-24 h-24 bg-secondary-50 dark:bg-secondary-800 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
         <ClipboardIcon class="w-10 h-10 text-secondary-300 dark:text-secondary-600" />
       </div>
       <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">Ready to Mark Attendance?</h4>
       <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto leading-relaxed">
         Select a Grade, Section, and Subject from the filters above to retrieve the current enrollment list.
       </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAttendanceStore } from '@/stores/attendance'
import { useGradesStore } from '@/stores/grades'
import { useSectionsStore } from '@/stores/section'
import { useSubjectsStore } from '@/stores/Subjects'
import { usePeriodsStore } from '@/stores/periods'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import AttendanceRow from '@/components/attendance/AttendanceRow.vue'
import { SearchIcon, ExclamationIcon, XIcon, ClipboardIcon } from '@heroicons/vue/outline'

const attendanceStore = useAttendanceStore()
const gradesStore = useGradesStore()
const sectionsStore = useSectionsStore()
const subjectsStore = useSubjectsStore()
const periodsStore = usePeriodsStore()

const submitting = ref(false)
const error = ref('')
const searchQuery = ref('')

const form = ref({
  grade_id: '',
  section_id: '',
  subject_id: '',
  date: new Date().toISOString().slice(0, 10),
  period_id: '',
})

// Real-time Counters
const presentCount = computed(() => attendanceStore.classAttendance.filter(s => s.status === 'present').length)
const absentCount = computed(() => attendanceStore.classAttendance.filter(s => s.status === 'absent').length)

const filteredStudents = computed(() => {
  if (!searchQuery.value) return attendanceStore.classAttendance
  const q = searchQuery.value.toLowerCase()
  return attendanceStore.classAttendance.filter(s => 
    s.student_name.toLowerCase().includes(q) || 
    s.admission_number.toLowerCase().includes(q)
  )
})

const filteredSections = computed(() => {
  if (!form.value.grade_id) return []
  return sectionsStore.sections.filter(s => s.grade_id === Number(form.value.grade_id))
})

const filteredSubjects = computed(() => {
  if (!form.value.grade_id) return []
  return subjectsStore.subjects.filter(s => s.grade_id === Number(form.value.grade_id))
})

const availablePeriods = computed(() => {
  const p = periodsStore.periods
  return Array.isArray(p) ? p.filter(period => period && period.id) : []
})

const isFormValid = computed(() => {
  return form.value.grade_id && form.value.section_id && form.value.subject_id && form.value.date && form.value.period_id
})

// Actions
const onGradeChange = () => {
  form.value.section_id = ''
  form.value.subject_id = ''
}

const loadStudents = async () => {
  if (!isFormValid.value) return
  error.value = ''
  await attendanceStore.getClassAttendance({
    grade_id: form.value.grade_id,
    section_id: form.value.section_id,
    subject_id: form.value.subject_id,
    date: form.value.date,
    period_id: form.value.period_id,
  })
}

const markAll = (status) => {
  attendanceStore.classAttendance.forEach(s => s.status = status)
}

const submitAttendance = async () => {
  if (submitting.value) return
  submitting.value = true
  error.value = ''
  
  try {
    const attendances = attendanceStore.classAttendance.map(s => ({
      student_id: s.student_id,
      status: s.status || 'present',
      remarks: s.remarks || '',
    }))
    
    const result = await attendanceStore.markAttendance({
      grade_id: form.value.grade_id,
      section_id: form.value.section_id,
      subject_id: form.value.subject_id,
      date: form.value.date,
      period_id: form.value.period_id,
      attendances,
    })
    
    if (result.success) {
      alert(result.message)
      await loadStudents()
    } else {
      error.value = result.message || 'Failed to save attendance'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'An unexpected error occurred marking attendance'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    gradesStore.fetchGrades(),
    sectionsStore.fetchSections(),
    subjectsStore.fetchSubjects(),
    periodsStore.fetchPeriods(),
  ])
})
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(40px) scale(0.98);
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>