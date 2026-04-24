<template>
  <div class="glass-card rounded-[2.5rem] overflow-hidden">
    <!-- Table Header with Search/Filter -->
    <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-secondary-50/20 dark:bg-secondary-800/20">
      <div class="relative w-full lg:w-96">
        <BaseInput 
          :model-value="search" 
          @update:model-value="$emit('update:search', $event)"
          placeholder="Search by name, email or ID..."
        >
          <template #icon><SearchIcon class="w-4 h-4" /></template>
        </BaseInput>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <BaseSelect 
          :model-value="gradeId" 
          @update:model-value="$emit('update:gradeId', $event)"
          class="min-w-[140px]"
        >
          <option value="">All Grades</option>
          <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
        </BaseSelect>

        <BaseSelect 
          :model-value="sectionId" 
          @update:model-value="$emit('update:sectionId', $event)"
          class="min-w-[140px]"
          :disabled="!gradeId"
        >
          <option value="">All Sections</option>
          <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
        </BaseSelect>

        <BaseButton variant="ghost" size="sm" @click="$emit('reset')">
          Reset
        </BaseButton>
      </div>
    </div>

    <!-- Table Body -->
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Student</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Academic Info</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Guardian</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
          <tr v-for="student in students" :key="student.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
            <!-- Student Identity -->
            <td class="px-8 py-5">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-sm font-black text-secondary-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600 transition-all border border-secondary-200 dark:border-secondary-700 shadow-sm">
                  {{ student.user?.name.charAt(0) }}
                </div>
                <div>
                  <p class="text-sm font-black text-secondary-900 dark:text-white leading-none mb-1">
                    {{ student.user?.name }}
                  </p>
                  <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest">
                    {{ student.user?.email }}
                  </p>
                </div>
              </div>
            </td>

            <!-- Grade/Section -->
            <td class="px-8 py-5">
              <div class="flex flex-col gap-1">
                <BaseBadge variant="primary">{{ student.grade?.name }}</BaseBadge>
                <span class="text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1">
                  Section: {{ student.section?.name }}
                </span>
              </div>
            </td>

            <!-- Guardian -->
            <td class="px-8 py-5">
              <div v-if="student.guardian" class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-secondary-50 dark:bg-secondary-800 flex items-center justify-center text-[10px] font-black text-secondary-400">
                  {{ student.guardian.user?.name.charAt(0) }}
                </div>
                <span class="text-xs font-bold text-secondary-600 dark:text-secondary-300">
                  {{ student.guardian.user?.name }}
                </span>
              </div>
              <span v-else class="text-xs italic text-secondary-300">No Guardian Linked</span>
            </td>

            <!-- Actions -->
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <BaseButton variant="ghost" size="xs" @click="$emit('edit', student)">
                  Edit
                </BaseButton>
                <BaseButton v-if="canDelete" variant="danger-ghost" size="xs" @click="$emit('delete', student)">
                  Delete
                </BaseButton>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="!students.length && !loading">
            <td colspan="4" class="px-8 py-20 text-center">
               <div class="text-4xl mb-4">🔍</div>
               <p class="text-secondary-500 font-black text-sm uppercase tracking-widest">No matching students found</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="px-8 py-6 bg-secondary-50/30 dark:bg-secondary-800/20 border-t border-secondary-100 dark:border-secondary-800 flex flex-col md:flex-row items-center justify-between gap-4">
      <div class="text-xs font-bold text-secondary-400 uppercase tracking-widest">
        Showing <span class="text-secondary-900 dark:text-white font-black">{{ (meta.current_page - 1) * meta.per_page + 1 }}</span>
        to <span class="text-secondary-900 dark:text-white font-black">{{ Math.min(meta.current_page * meta.per_page, meta.total) }}</span>
        of <span class="text-secondary-900 dark:text-white font-black">{{ meta.total }}</span> Students
      </div>
      
      <div class="flex items-center gap-2">
        <BaseButton 
          variant="outline" 
          size="sm" 
          :disabled="meta.current_page === 1"
          @click="$emit('page-change', meta.current_page - 1)"
        >
          Previous
        </BaseButton>
        <div class="px-4 py-2 bg-white dark:bg-secondary-900 rounded-xl border border-secondary-100 dark:border-secondary-800 text-xs font-black text-secondary-900 dark:text-white">
          {{ meta.current_page }} / {{ meta.last_page }}
        </div>
        <BaseButton 
          variant="outline" 
          size="sm" 
          :disabled="meta.current_page === meta.last_page"
          @click="$emit('page-change', meta.current_page + 1)"
        >
          Next
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import { SearchIcon } from '@heroicons/vue/outline'

const props = defineProps({
  students: Array,
  meta: Object,
  loading: Boolean,
  grades: Array,
  sections: Array,
  search: String,
  gradeId: [String, Number],
  sectionId: [String, Number],
  canDelete: Boolean
})

defineEmits(['update:search', 'update:gradeId', 'update:sectionId', 'reset', 'edit', 'delete', 'page-change'])

const filteredSections = computed(() => {
  if (!props.gradeId) return []
  return props.sections.filter(s => s.grade_id === Number(props.gradeId))
})
</script>
