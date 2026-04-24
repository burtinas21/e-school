<template>
  <div class="glass-card rounded-[2.5rem] overflow-hidden">
    <!-- Table Header with Search -->
    <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-secondary-50/20 dark:bg-secondary-800/20">
      <div class="relative w-full lg:w-96">
        <BaseInput 
          :model-value="search" 
          @update:model-value="$emit('update:search', $event)"
          placeholder="Search teachers by name or email..."
        >
          <template #icon><SearchIcon class="w-4 h-4" /></template>
        </BaseInput>
      </div>

      <div class="flex items-center gap-3">
        <BaseButton variant="ghost" size="sm" @click="$emit('reset')">
          Reset Filters
        </BaseButton>
      </div>
    </div>

    <!-- Table Body -->
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="bg-secondary-50/50 dark:bg-secondary-900/50">
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Faculty Member</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Employment Info</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Contact Details</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
          <tr v-for="teacher in teachers" :key="teacher.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
            <!-- Teacher Identity -->
            <td class="px-8 py-5">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-sm font-black text-secondary-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600 transition-all border border-secondary-200 dark:border-secondary-700 shadow-sm">
                  {{ teacher.user?.name.charAt(0) }}
                </div>
                <div>
                  <p class="text-sm font-black text-secondary-900 dark:text-white leading-none mb-1">
                    {{ teacher.user?.name }}
                  </p>
                  <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest">
                    {{ teacher.user?.email }}
                  </p>
                </div>
              </div>
            </td>

            <!-- Employment Info -->
            <td class="px-8 py-5">
              <div class="flex flex-col gap-1">
                <BaseBadge variant="primary">{{ teacher.employee_id }}</BaseBadge>
                <span class="text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1">
                  {{ teacher.qualification }}
                </span>
              </div>
            </td>

            <!-- Contact -->
            <td class="px-8 py-5">
               <div class="flex items-center gap-2">
                 <div class="p-1.5 bg-secondary-50 dark:bg-secondary-800 rounded-lg text-secondary-400">
                   <PhoneIcon class="w-3.5 h-3.5" />
                 </div>
                 <span class="text-xs font-bold text-secondary-600 dark:text-secondary-300">
                   {{ teacher.user?.phone || 'No phone' }}
                 </span>
               </div>
            </td>

            <!-- Actions -->
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <BaseButton variant="ghost" size="xs" @click="$emit('edit', teacher)">
                  Edit
                </BaseButton>
                <BaseButton variant="danger-ghost" size="xs" @click="$emit('delete', teacher)">
                  Delete
                </BaseButton>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="!teachers.length && !loading">
            <td colspan="4" class="px-8 py-20 text-center">
               <div class="text-4xl mb-4 text-secondary-200">👨‍🏫</div>
               <p class="text-secondary-500 font-black text-sm uppercase tracking-widest">No faculty records found</p>
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
        of <span class="text-secondary-900 dark:text-white font-black">{{ meta.total }}</span> Teachers
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
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import { SearchIcon, PhoneIcon } from '@heroicons/vue/outline'

defineProps({
  teachers: Array,
  meta: Object,
  loading: Boolean,
  search: String
})

defineEmits(['update:search', 'reset', 'edit', 'delete', 'page-change'])
</script>
