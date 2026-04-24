

<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Attendance Report</h1>

    <!-- Filter form -->
    <div class="bg-white p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
        <input v-model="filters.start_date" type="date" class="w-full border rounded-md px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
        <input v-model="filters.end_date" type="date" class="w-full border rounded-md px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
        <select v-model="filters.grade_id" class="w-full border rounded-md px-3 py-2">
          <option value="">All Grades</option>
          <option v-for="g in gradesStore.grades" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
        <select v-model="filters.section_id" class="w-full border rounded-md px-3 py-2">
          <option value="">All Sections</option>
          <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
      </div>
      <div class="md:col-span-3 flex justify-end">
        <button @click="generateReport" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
          Generate Report
        </button>
      </div>
    </div>

    <!-- Report result -->
    <div v-if="report" class="bg-white rounded-lg shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Report Summary</h2>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-50 p-4 rounded">
          <p class="text-sm text-gray-500">Total Records</p>
          <p class="text-2xl font-bold">{{ report.statistics.total }}</p>
        </div>
        <div class="bg-green-50 p-4 rounded">
          <p class="text-sm text-green-600">Present</p>
          <p class="text-2xl font-bold text-green-700">{{ report.statistics.present }}</p>
        </div>
        <div class="bg-red-50 p-4 rounded">
          <p class="text-sm text-red-600">Absent</p>
          <p class="text-2xl font-bold text-red-700">{{ report.statistics.absent }}</p>
        </div>
        <div class="bg-yellow-50 p-4 rounded">
          <p class="text-sm text-yellow-600">Late</p>
          <p class="text-2xl font-bold text-yellow-700">{{ report.statistics.late }}</p>
        </div>
      </div>

      <h3 class="font-semibold mb-2">Detailed Records</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left">Student</th>
              <th class="px-4 py-2 text-left">Date</th>
              <th class="px-4 py-2 text-left">Subject</th>
              <th class="px-4 py-2 text-left">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in report.records" :key="record.id" class="border-b">
              <td class="px-4 py-2">{{ record.student?.user?.name }}</td>
              <td class="px-4 py-2">{{ record.date }}</td>
              <td class="px-4 py-2">{{ record.subject?.name }}</td>
              <td class="px-4 py-2">
                <span :class="statusClass(record.status)" class="px-2 py-1 rounded-full text-xs">
                  {{ record.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-else-if="loading" class="text-center p-6">Loading report...</div>
    <div v-else class="bg-white p-6 text-center text-gray-500">Select filters and click Generate Report.</div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAttendanceStore } from '@/stores/attendance'
import { useGradesStore } from '@/stores/grades'
import { useSectionsStore } from '@/stores/section'

const attendanceStore = useAttendanceStore()
const gradesStore = useGradesStore()
const sectionsStore = useSectionsStore()

const filters = ref({
  start_date: new Date(new Date().setDate(1)).toISOString().slice(0, 10), // first day of month
  end_date: new Date().toISOString().slice(0, 10),
  grade_id: '',
  section_id: '',
})

const loading = computed(() => attendanceStore.loading)
const report = computed(() => attendanceStore.reportData)

const filteredSections = computed(() => {
  if (!filters.value.grade_id) return sectionsStore.sections
  return sectionsStore.sections.filter(s => s.grade_id === Number(filters.value.grade_id))
})

const statusClass = (status) => {
  const classes = {
    present: 'bg-green-100 text-green-800',
    absent: 'bg-red-100 text-red-800',
    late: 'bg-yellow-100 text-yellow-800',
    permission: 'bg-blue-100 text-blue-800',
  }
  return classes[status] || 'bg-gray-100'
}

const generateReport = async () => {
  await attendanceStore.fetchReport({
    start_date: filters.value.start_date,
    end_date: filters.value.end_date,
    grade_id: filters.value.grade_id,
    section_id: filters.value.section_id,
  })
}
</script>