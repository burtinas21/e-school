<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-black text-secondary-900 dark:text-white tracking-tight">Analytical Reports</h1>
        <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">Generate comprehensive attendance aggregates and institutional summaries.</p>
      </div>
      
      <div v-if="reportData" class="flex gap-3">
        <BaseButton variant="ghost" @click="printReport">
          <template #icon-left><PrinterIcon class="w-4 h-4" /></template>
          Universal Print
        </BaseButton>
      </div>
    </div>

    <!-- Filter Panel -->
    <div class="glass-card p-10 rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 shadow-premium">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
        <BaseSelect v-model="form.grade_id" label="Academic Grade" @change="onGradeChange">
          <option value="">Select Grade</option>
          <option v-for="g in gradesStore.grades" :key="g.id" :value="g.id">{{ g.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="form.section_id" label="Assigned Section" :disabled="!form.grade_id">
          <option value="">Select Section</option>
          <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="form.subject_id" label="Specific Subject" :disabled="!form.grade_id">
          <option value="">Select Subject</option>
          <option v-for="subj in filteredSubjects" :key="subj.id" :value="subj.id">{{ subj.name }}</option>
        </BaseSelect>

        <div class="flex flex-col gap-2">
          <div class="grid grid-cols-2 gap-2">
            <BaseInput v-model="form.start_date" type="date" label="Range From" />
            <BaseInput v-model="form.end_date" type="date" label="Range To" />
          </div>
        </div>

        <div class="lg:col-span-4 flex justify-end pt-4 border-t border-secondary-100 dark:border-secondary-800">
           <BaseButton 
            variant="primary" 
            size="lg"
            @click="generateReport" 
            :disabled="!isFormValid || loading" 
            :loading="loading"
            class="px-12"
           >
              Process Analytics
           </BaseButton>
        </div>
      </div>
    </div>

    <!-- Report Output -->
    <div v-if="reportData" class="space-y-8 print-section anim-slide-up">
      <!-- High-Level Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <StatCard 
          title="Presence Velocity" 
          :value="reportData.totals.presence_percentage + '%'" 
          subtitle="Average attendance rate"
          variant="success"
        />
        <StatCard 
          title="Absence Volatility" 
          :value="reportData.totals.absent_count" 
          subtitle="Total negative marks"
          variant="danger"
        />
        <StatCard 
          title="Chronometric Lates" 
          :value="reportData.totals.late_count" 
          subtitle="Delayed session arrivals"
          variant="warning"
        />
      </div>

      <!-- Student Summary Table -->
      <div class="glass-card rounded-[2.5rem] overflow-hidden border border-secondary-100 dark:border-secondary-800">
         <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex flex-col md:flex-row justify-between items-center gap-4 bg-secondary-50/20 dark:bg-secondary-800/20">
            <h3 class="text-lg font-black text-secondary-900 dark:text-white tracking-tight italic">Granular Audit Log</h3>
            <div class="relative w-full md:w-64">
               <BaseInput v-model="reportSearch" placeholder="Find student..." size="sm" />
            </div>
         </div>
         
         <div class="overflow-x-auto">
           <table class="w-full text-left">
              <thead>
                 <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest">Enrolled Student</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-center">Sessions</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-center text-emerald-500">Present</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-center text-rose-500">Absent</th>
                    <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-widest text-right">Reliability Index</th>
                 </tr>
              </thead>
              <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
                 <tr v-for="student in filteredReportData" :key="student.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
                    <td class="px-8 py-5">
                       <div class="flex items-center gap-4">
                          <div class="w-10 h-10 rounded-2xl bg-secondary-100 dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 flex items-center justify-center text-xs font-black text-secondary-400">
                             {{ student.name.charAt(0) }}
                          </div>
                          <div>
                             <p class="text-sm font-black text-secondary-900 dark:text-white leading-none mb-1">{{ student.name }}</p>
                             <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest italic">{{ student.admission_number }}</p>
                          </div>
                       </div>
                    </td>
                    <td class="px-8 py-5 text-center font-bold text-secondary-600 dark:text-secondary-400 text-xs">{{ student.total_sessions }}</td>
                    <td class="px-8 py-5 text-center font-black text-emerald-600 text-sm italic">{{ student.present_count }}</td>
                    <td class="px-8 py-5 text-center font-black text-rose-600 text-sm italic">{{ student.absent_count }}</td>
                    <td class="px-8 py-5 text-right">
                       <div class="flex flex-col items-end gap-1.5">
                          <span class="text-xs font-black" :class="percentageStyle(student.percentage)">{{ student.percentage }}%</span>
                          <div class="w-24 bg-secondary-100 dark:bg-secondary-800 h-1.5 rounded-full overflow-hidden shadow-inner">
                             <div class="h-full rounded-full transition-all duration-1000 ease-out shadow-sm" :class="bgStyle(student.percentage)" :style="{ width: student.percentage + '%' }"></div>
                          </div>
                       </div>
                    </td>
                 </tr>
              </tbody>
           </table>
         </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && formSubmitted" class="p-24 text-center glass-card rounded-[3rem] border-2 border-dashed border-secondary-200 dark:border-secondary-800">
       <div class="w-24 h-24 bg-secondary-50 dark:bg-secondary-800 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-inner">
         <SearchIcon class="w-10 h-10 text-secondary-300 dark:text-secondary-600" />
       </div>
       <h4 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">Analytical Void</h4>
       <p class="text-secondary-500 dark:text-secondary-400 text-sm mt-3 max-w-sm mx-auto leading-relaxed">
         No records matched your search parameters. Try adjusting the period or class filters to broaden the scope.
       </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAttendanceStore } from '@/stores/attendance'
import { useGradesStore } from '@/stores/grades'
import { useSectionsStore } from '@/stores/section'
import { useSubjectsStore } from '@/stores/Subjects'

// UI Components
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import StatCard from '@/components/ui/StatCard.vue'
import { PrinterIcon, SearchIcon } from '@heroicons/vue/outline'

const attendanceStore = useAttendanceStore()
const gradesStore = useGradesStore()
const sectionsStore = useSectionsStore()
const subjectsStore = useSubjectsStore()

const { reportData, loading } = storeToRefs(attendanceStore)
const formSubmitted = ref(false)
const reportSearch = ref('')

const form = ref({
  grade_id: '',
  section_id: '',
  subject_id: '',
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  end_date: new Date().toISOString().slice(0, 10),
})

const isFormValid = computed(() => {
  return form.value.grade_id && form.value.section_id && form.value.subject_id && form.value.start_date && form.value.end_date
})

const filteredSections = computed(() => {
  if (!form.value.grade_id) return []
  return sectionsStore.sections.filter(s => s.grade_id === Number(form.value.grade_id))
})

const filteredSubjects = computed(() => {
  if (!form.value.grade_id) return []
  return subjectsStore.subjects.filter(s => s.grade_id === Number(form.value.grade_id))
})

const filteredReportData = computed(() => {
  if (!reportData.value?.students) return []
  if (!reportSearch.value) return reportData.value.students
  const q = reportSearch.value.toLowerCase()
  return reportData.value.students.filter(s => 
    s.name.toLowerCase().includes(q) || 
    s.admission_number.toLowerCase().includes(q)
  )
})

const onGradeChange = () => {
  form.value.section_id = ''
  form.value.subject_id = ''
}

const generateReport = async () => {
  if (!isFormValid.value) return
  formSubmitted.value = true
  await attendanceStore.fetchReport(form.value)
}

const percentageStyle = (pct) => {
  if (pct >= 90) return 'text-emerald-600'
  if (pct >= 75) return 'text-primary-600'
  if (pct >= 60) return 'text-amber-600'
  return 'text-rose-600'
}

const bgStyle = (pct) => {
  if (pct >= 90) return 'bg-emerald-500 shadow-emerald-200'
  if (pct >= 75) return 'bg-primary-500 shadow-primary-200'
  if (pct >= 60) return 'bg-amber-500 shadow-amber-200'
  return 'bg-rose-500 shadow-rose-200'
}

const printReport = () => {
  window.print()
}

onMounted(async () => {
  await Promise.all([
    gradesStore.fetchGrades(),
    sectionsStore.fetchSections(),
    subjectsStore.fetchSubjects(),
  ])
})
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  .print-section, .print-section * {
    visibility: visible;
  }
  .print-section {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}

.anim-slide-up {
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
