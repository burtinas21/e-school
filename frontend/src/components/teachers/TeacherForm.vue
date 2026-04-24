<template>
  <BaseModal 
    :show="show" 
    :title="isEditing ? 'Update Faculty Profile' : 'Register New Teacher'" 
    @close="$emit('close')"
  >
    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <BaseInput 
          v-model="form.name" 
          label="Full Name" 
          placeholder="e.g. Dr. Jane Smith"
          required 
        />
        <BaseInput 
          v-model="form.email" 
          label="Professional Email" 
          type="email" 
          placeholder="j.smith@school.com"
          required 
        />
        <BaseInput 
          v-model="form.password" 
          label="Access Password" 
          type="password" 
          :placeholder="isEditing ? 'Leave blank to keep current' : 'Enter strong password'"
          :required="!isEditing" 
        />
        <BaseInput 
          v-model="form.phone" 
          label="Contact Number" 
          placeholder="+1 (555) 000-0000"
        />
        <BaseInput 
          v-model="form.employee_id" 
          label="Employee ID / Faculty Code" 
          placeholder="EMP-2024-XXX"
          required 
        />
        <BaseInput 
          v-model="form.qualification" 
          label="Highest Qualification" 
          placeholder="e.g. PhD in Mathematics"
          required 
        />
        <BaseInput 
          v-model="form.hire_date" 
          label="Official Hire Date" 
          type="date"
        />
      </div>

      <!-- Error Display -->
      <div v-if="errors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
        <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Registration Errors</p>
        <ul class="space-y-1">
          <li v-for="(errs, field) in errors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
            <span class="capitalize">{{ field }}:</span> {{ errs.join(', ') }}
          </li>
        </ul>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 pt-6 border-t border-secondary-100 dark:border-secondary-800">
        <BaseButton variant="ghost" type="button" @click="$emit('close')">Discard Changes</BaseButton>
        <BaseButton 
          variant="primary" 
          type="submit" 
          :loading="submitting"
        >
          {{ isEditing ? 'Save Profile' : 'Finalize Registration' }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
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
  name: '',
  email: '',
  password: '',
  phone: '',
  employee_id: '',
  qualification: '',
  hire_date: '',
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    form.value = {
      name: '', email: '', password: '', phone: '',
      employee_id: '', qualification: '', hire_date: '',
    }
  }
}, { immediate: true })

const submit = () => {
  emit('submit', { ...form.value })
}
</script>
