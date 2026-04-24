<template>
  <BaseModal 
    :show="show" 
    :title="isEditing ? 'Update Schedule' : 'Schedule New Event'" 
    @close="$emit('close')"
  >
    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
          <BaseInput 
            v-model="form.title" 
            label="Event Title" 
            placeholder="e.g. Independence Day Holiday"
            required 
          />
        </div>
        
        <BaseSelect v-model="form.event_type" label="Event Category" required>
          <option value="event">General Event</option>
          <option value="holiday">Official Holiday</option>
          <option value="exam">Examination Period</option>
          <option value="closure">School Closure</option>
        </BaseSelect>

        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="form.start_date" type="date" label="Start Date" required />
          <BaseInput v-model="form.end_date" type="date" label="End Date" required />
        </div>

        <div class="md:col-span-2">
          <div class="p-4 bg-secondary-50 dark:bg-secondary-800/50 rounded-2xl border border-secondary-100 dark:border-secondary-800">
            <label class="flex items-center cursor-pointer group">
              <div class="relative">
                <input v-model="form.affects_attendance" type="checkbox" class="sr-only" />
                <div class="w-10 h-5 bg-secondary-200 dark:bg-secondary-700 rounded-full transition-colors group-hover:bg-secondary-300" :class="{'bg-primary-600': form.affects_attendance}"></div>
                <div class="absolute inset-y-0 left-0 w-5 h-5 bg-white rounded-full shadow-sm transform transition-transform" :class="{'translate-x-5': form.affects_attendance}"></div>
              </div>
              <div class="ml-3">
                <span class="block text-xs font-black text-secondary-900 dark:text-white uppercase tracking-widest">Restrict Attendance</span>
                <span class="block text-[10px] text-secondary-500 font-bold uppercase tracking-tight">Block marking for this date range.</span>
              </div>
            </label>
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="block text-[10px] font-black text-secondary-400 uppercase tracking-widest ml-1 mb-2">Description</label>
          <textarea 
            v-model="form.description" 
            rows="3" 
            placeholder="Provide additional context for this event..."
            class="w-full bg-secondary-50 dark:bg-secondary-800 border-none rounded-2xl px-5 py-4 text-sm font-bold text-secondary-700 dark:text-secondary-100 focus:ring-4 focus:ring-primary-100 dark:focus:ring-primary-900/40 outline-none transition-all resize-none shadow-inner"
          ></textarea>
        </div>

        <div class="md:col-span-2 flex items-center gap-4">
           <div class="flex items-center gap-2">
              <input type="checkbox" v-model="form.is_recurring" class="rounded border-secondary-300 text-primary-600" />
              <span class="text-xs font-black text-secondary-600 dark:text-secondary-400 uppercase tracking-widest">Recurring Event</span>
           </div>
           <BaseSelect v-if="form.is_recurring" v-model="form.recurring_pattern" class="flex-1">
             <option value="yearly">Yearly</option>
             <option value="monthly">Monthly</option>
             <option value="weekly">Weekly</option>
           </BaseSelect>
        </div>
      </div>

      <!-- Error Feedback -->
      <div v-if="errors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
        <ul class="space-y-1">
          <li v-for="(errs, field) in errors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
            <span class="capitalize">{{ field }}:</span> {{ errs.join(', ') }}
          </li>
        </ul>
      </div>

      <!-- Footer Actions -->
      <div class="flex justify-end gap-3 pt-6 border-t border-secondary-100 dark:border-secondary-800">
        <BaseButton variant="ghost" type="button" @click="$emit('close')">Cancel</BaseButton>
        <BaseButton 
          variant="primary" 
          type="submit" 
          :loading="submitting"
        >
          {{ isEditing ? 'Update Schedule' : 'Confirm Event' }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  initialData: Object,
  submitting: Boolean,
  errors: Object
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
  title: '',
  description: '',
  event_type: 'event',
  start_date: '',
  end_date: '',
  is_recurring: false,
  recurring_pattern: 'yearly',
  affects_attendance: true,
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    const today = new Date().toISOString().split('T')[0]
    form.value = {
      title: '', description: '', event_type: 'event',
      start_date: today, end_date: today,
      is_recurring: false, recurring_pattern: 'yearly',
      affects_attendance: true
    }
  }
}, { immediate: true })

const submit = () => {
  emit('submit', { ...form.value })
}
</script>
