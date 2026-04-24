<template>
  <BaseModal 
    :show="show" 
    :title="isEditing ? 'Update Student Profile' : 'Register New Student'" 
    @close="$emit('close')"
  >
    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <BaseInput 
          v-model="form.name" 
          label="Full Name" 
          placeholder="e.g. John Doe"
          required 
        />
        <BaseInput 
          v-model="form.email" 
          label="Email Address" 
          type="email" 
          placeholder="j.doe@school.com"
          required 
        />
        <BaseInput 
          v-model="form.password" 
          label="Password" 
          type="password" 
          :placeholder="isEditing ? 'Leave blank to keep current' : 'Enter strong password'"
          :required="!isEditing" 
        />
        <BaseInput 
          v-model="form.phone" 
          label="Phone Number" 
          placeholder="+1 (555) 000-0000"
        />
        
        <BaseSelect 
          v-model="form.grade_id" 
          label="Grade Assignment" 
          required
        >
          <option value="">Select Grade</option>
          <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
        </BaseSelect>

        <BaseSelect 
          v-model="form.section_id" 
          label="Section Assignment" 
          required
          :disabled="!form.grade_id"
        >
          <option value="">Select Section</option>
          <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
        </BaseSelect>

        <BaseInput 
          v-model="form.admission_number" 
          label="Admission / ID Number" 
          placeholder="ADM-2024-XXX"
          required 
        />

        <BaseSelect 
          v-model="form.guardian_id" 
          label="Linked Guardian"
        >
          <option value="">No Guardian Linked</option>
          <option v-for="g in guardians" :key="g.id" :value="g.id">
            {{ g.user?.name }} ({{ g.relationship || 'Guardian' }})
          </option>
        </BaseSelect>

        <BaseInput 
          v-model="form.date_of_birth" 
          label="Date of Birth" 
          type="date"
        />

        <BaseSelect 
          v-model="form.gender" 
          label="Gender Identity"
        >
          <option value="">Select...</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </BaseSelect>
      </div>

      <div v-if="errors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
        <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Validation Errors</p>
        <ul class="space-y-1">
          <li v-for="(errs, field) in errors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
            <span class="capitalize">{{ field }}:</span> {{ errs.join(', ') }}
          </li>
        </ul>
      </div>

      <div class="flex justify-end gap-3 pt-6 border-t border-secondary-100 dark:border-secondary-800">
        <BaseButton variant="ghost" type="button" @click="$emit('close')">Cancel</BaseButton>
        <BaseButton 
          variant="primary" 
          type="submit" 
          :loading="submitting"
        >
          {{ isEditing ? 'Update Profile' : 'Confirm Registration' }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  show: Boolean,
  isEditing: Boolean,
  initialData: Object,
  grades: Array,
  sections: Array,
  guardians: Array,
  submitting: Boolean,
  errors: Object
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  grade_id: '',
  section_id: '',
  admission_number: '',
  guardian_id: '',
  date_of_birth: '',
  gender: '',
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    form.value = {
      name: '', email: '', password: '', phone: '',
      grade_id: '', section_id: '', admission_number: '',
      guardian_id: '', date_of_birth: '', gender: '',
    }
  }
}, { immediate: true })

const filteredSections = computed(() => {
  if (!form.value.grade_id) return []
  return props.sections.filter(s => s.grade_id === Number(form.value.grade_id))
})

const submit = () => {
  emit('submit', { ...form.value })
}
</script>
