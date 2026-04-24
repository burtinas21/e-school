<template>
  <div class="glass-card rounded-[2.5rem] overflow-hidden">
    <!-- Table Header with Search -->
    <div class="px-8 py-6 border-b border-secondary-100 dark:border-secondary-800 flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-secondary-50/20 dark:bg-secondary-800/20">
      <div class="relative w-full lg:w-96">
        <BaseInput 
          :model-value="search" 
          @update:model-value="$emit('update:search', $event)"
          placeholder="Search by name, email or phone..."
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
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Guardian Profile</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Contact Data</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-left">Relationship</th>
            <th class="px-8 py-5 text-[10px] font-black text-secondary-400 uppercase tracking-[0.2em] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800">
          <tr v-for="guardian in guardians" :key="guardian.id" class="group hover:bg-secondary-50 dark:hover:bg-secondary-900/50 transition-colors">
            <!-- Guardian Identity -->
            <td class="px-8 py-5">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center text-sm font-black text-secondary-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600 transition-all border border-secondary-200 dark:border-secondary-700 shadow-sm">
                  {{ guardian.user?.name.charAt(0) }}
                </div>
                <div>
                  <p class="text-sm font-black text-secondary-900 dark:text-white leading-none mb-1">
                    {{ guardian.user?.name }}
                  </p>
                  <p class="text-[10px] font-bold text-secondary-400 uppercase tracking-widest">
                    {{ guardian.gender || 'Not specified' }}
                  </p>
                </div>
              </div>
            </td>

            <!-- Contact Info -->
            <td class="px-8 py-5">
              <div class="flex flex-col gap-1">
                <p class="text-sm font-bold text-secondary-600 dark:text-secondary-300">
                  {{ guardian.user?.email }}
                </p>
                <div class="flex items-center gap-2 text-[10px] font-black text-secondary-400 uppercase tracking-widest">
                  <PhoneIcon class="w-3 h-3" />
                  {{ guardian.user?.phone || 'No direct phone' }}
                </div>
              </div>
            </td>

            <!-- Relationship -->
            <td class="px-8 py-5">
               <BaseBadge variant="primary">{{ guardian.relationship || 'Guardian' }}</BaseBadge>
            </td>

            <!-- Actions -->
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <router-link :to="`/guardians/${guardian.id}/children`" class="p-2 text-secondary-400 hover:text-primary-600 transition-colors" title="View Children">
                   <UsersIcon class="w-5 h-5" />
                </router-link>
                <BaseButton variant="ghost" size="xs" @click="$emit('edit', guardian)">
                  Edit
                </BaseButton>
                <BaseButton v-if="canDelete" variant="danger-ghost" size="xs" @click="$emit('delete', guardian)">
                  Delete
                </BaseButton>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="!guardians.length && !loading">
            <td colspan="4" class="px-8 py-20 text-center">
               <div class="text-4xl mb-4 text-secondary-200">🛡️</div>
               <p class="text-secondary-500 font-black text-sm uppercase tracking-widest">No guardian profiles found</p>
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
        of <span class="text-secondary-900 dark:text-white font-black">{{ meta.total }}</span> Guardians
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
import { SearchIcon, PhoneIcon, UsersIcon } from '@heroicons/vue/outline'

defineProps({
  guardians: Array,
  meta: Object,
  loading: Boolean,
  search: String,
  canDelete: Boolean
})

defineEmits(['update:search', 'reset', 'edit', 'delete', 'page-change'])
</script>
