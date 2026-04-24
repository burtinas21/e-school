<template>
  <BaseModal 
    :show="show" 
    :title="event?.title" 
    @close="$emit('close')"
  >
    <div class="space-y-8">
      <!-- Event Header Visual -->
      <div 
        class="h-40 -mt-6 -mx-6 rounded-b-[2.5rem] p-8 flex flex-col justify-end text-white relative overflow-hidden"
        :class="getBgStyle(event?.event_type)"
      >
        <div class="absolute top-6 right-6 flex gap-2">
           <BaseButton v-if="canManage" variant="ghost" size="xs" class="bg-white/20 border-none hover:bg-white/40" @click="$emit('edit', event)">
             <template #icon-left><PencilIcon class="w-4 h-4 text-white" /></template>
           </BaseButton>
           <BaseButton v-if="canManage" variant="danger-ghost" size="xs" class="bg-rose-500/20 border-none hover:bg-rose-500/40" @click="$emit('delete', event)">
             <template #icon-left><TrashIcon class="w-4 h-4 text-white" /></template>
           </BaseButton>
        </div>
        <span class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80">{{ event?.event_type }}</span>
        <h2 class="text-3xl font-black mt-2 leading-none">{{ event?.title }}</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Date Info -->
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 bg-secondary-50 dark:bg-secondary-800 rounded-2xl flex items-center justify-center text-secondary-400 border border-secondary-100 dark:border-secondary-800">
            <CalendarIcon class="w-6 h-6" />
          </div>
          <div>
            <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Schedule Range</p>
            <p class="text-sm font-black text-secondary-900 dark:text-white">{{ formatDisplayDate(event?.start_date, event?.end_date) }}</p>
          </div>
        </div>

        <!-- Meta Info -->
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 bg-secondary-50 dark:bg-secondary-800 rounded-2xl flex items-center justify-center text-secondary-400 border border-secondary-100 dark:border-secondary-800">
            <UserIcon class="w-6 h-6" />
          </div>
          <div>
            <p class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-1">Created By</p>
            <p class="text-sm font-black text-secondary-900 dark:text-white capitalize">{{ event?.creator?.name || 'Academic Administrator' }}</p>
          </div>
        </div>

        <!-- Description -->
        <div v-if="event?.description" class="md:col-span-2 p-6 bg-secondary-50 dark:bg-secondary-800/40 rounded-3xl border border-secondary-100 dark:border-secondary-800">
           <h3 class="text-[10px] font-black text-secondary-400 uppercase tracking-widest mb-3">Event Memo</h3>
           <p class="text-sm font-medium text-secondary-600 dark:text-secondary-300 leading-relaxed">{{ event.description }}</p>
        </div>

        <!-- Status Pill -->
        <div class="md:col-span-2">
           <div class="flex items-center gap-3 p-4 bg-white dark:bg-secondary-900 rounded-2xl border border-secondary-100 dark:border-secondary-800 shadow-sm">
              <div 
                class="w-3 h-3 rounded-full animate-pulse" 
                :class="event?.affects_attendance ? 'bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,0.4)]' : 'bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.4)]'"
              ></div>
              <span class="text-xs font-black uppercase tracking-widest" :class="event?.affects_attendance ? 'text-rose-600' : 'text-emerald-600'">
                {{ event?.affects_attendance ? 'System: Attendance Restricted' : 'System: Normal Operations' }}
              </span>
           </div>
        </div>
      </div>

      <div class="pt-6 border-t border-secondary-100 dark:border-secondary-800">
        <BaseButton variant="ghost" class="w-full h-12" @click="$emit('close')">Dismiss Details</BaseButton>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { CalendarIcon, UserIcon, PencilIcon, TrashIcon } from '@heroicons/vue/outline'

defineProps({
  show: Boolean,
  event: Object,
  canManage: Boolean
})

defineEmits(['close', 'edit', 'delete'])

const formatDisplayDate = (start, end) => {
  if (!start) return ''
  const s = new Date(start).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
  if (!end || start === end) return s
  const e = new Date(end).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
  return `${s} — ${e}`
}

const getBgStyle = (type) => {
  switch (type) {
    case 'holiday': return 'bg-gradient-to-br from-rose-500 to-rose-600'
    case 'exam': return 'bg-gradient-to-br from-emerald-500 to-teal-600'
    case 'closure': return 'bg-gradient-to-br from-secondary-700 to-secondary-900'
    default: return 'bg-gradient-to-br from-primary-500 to-indigo-600'
  }
}
</script>
