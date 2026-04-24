<template>
  <tr class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
    <!-- Student Info -->
    <td class="px-8 py-5">
      <div class="flex items-center gap-4">
        <div 
          class="w-10 h-10 rounded-xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-xs font-black text-secondary-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600 transition-all border border-secondary-200 dark:border-secondary-700"
        >
          {{ student.student_name.charAt(0) }}
        </div>
        <div>
          <p class="text-sm font-black text-secondary-900 dark:text-white leading-none mb-1">
            {{ student.student_name }}
          </p>
          <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest italic">
            {{ student.admission_number }}
          </p>
        </div>
      </div>
    </td>

    <!-- Status Toggles -->
    <td class="px-8 py-5">
      <div class="flex items-center justify-center p-1 bg-secondary-100/50 dark:bg-secondary-800/50 rounded-2xl w-max mx-auto border border-secondary-100 dark:border-secondary-700 shadow-inner">
        <button 
          v-for="s in statusOptions" 
          :key="s.id"
          @click="$emit('update:status', s.id)"
          class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-tight transition-all"
          :class="[
            currentStatus === s.id 
              ? s.activeClass 
              : 'text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 hover:bg-white dark:hover:bg-secondary-700'
          ]"
        >
          {{ s.label }}
        </button>
      </div>
    </td>

    <!-- Remarks -->
    <td class="px-8 py-5">
      <div class="relative">
        <input 
          :value="remarks"
          @input="$emit('update:remarks', $event.target.value)"
          type="text" 
          placeholder="Add observational note..." 
          class="w-full bg-secondary-50/50 dark:bg-secondary-800/50 border-none rounded-xl px-4 py-2.5 text-xs font-bold text-secondary-600 dark:text-secondary-300 placeholder:text-secondary-300 dark:placeholder:text-secondary-600 focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-900/40 focus:bg-white dark:focus:bg-secondary-900 transition-all shadow-inner" 
        />
      </div>
    </td>
  </tr>
</template>

<script setup>
const props = defineProps({
  student: Object,
  currentStatus: String,
  remarks: String
})

defineEmits(['update:status', 'update:remarks'])

const statusOptions = [
  { id: 'present', label: 'Present', activeClass: 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 dark:shadow-none' },
  { id: 'absent', label: 'Absent', activeClass: 'bg-rose-500 text-white shadow-lg shadow-rose-200 dark:shadow-none' },
  { id: 'late', label: 'Late', activeClass: 'bg-amber-500 text-white shadow-lg shadow-amber-200 dark:shadow-none' },
  { id: 'permission', label: 'Leave', activeClass: 'bg-blue-500 text-white shadow-lg shadow-blue-200 dark:shadow-none' }
]
</script>
