<template>
  <BaseModal 
    :show="show" 
    :title="isEditing ? 'Update Guardian Profile' : 'Link New Guardian'" 
    @close="$emit('close')"
  >
    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
          <BaseInput 
            v-model="form.name" 
            label="Full Legal Name" 
            placeholder="e.g. Robert Wilson"
            required 
          />
        </div>
        
        <BaseInput 
          v-model="form.email" 
          label="Email Address" 
          type="email" 
          placeholder="r.wilson@example.com"
          required 
        />
        
        <BaseInput 
          v-model="form.phone" 
          label="Primary Contact Number" 
          placeholder="+1 (555) 000-0000"
        />

        <BaseInput 
          v-if="!isEditing"
          v-model="form.password" 
          label="Access Password" 
          type="password" 
          placeholder="Minimum 8 characters"
          required 
        />

        <BaseSelect v-model="form.relationship" label="Relationship to Child" required>
          <option value="father">Father</option>
          <option value="mother">Mother</option>
          <option value="brother">Brother</option>
          <option value="sister">Sister</option>
          <option value="guardian">Legal Guardian</option>
          <option value="other">Other Relative</option>
        </BaseSelect>

        <BaseInput 
          v-model="form.occupation" 
          label="Occupation" 
          placeholder="e.g. Civil Engineer"
        />

        <BaseSelect v-model="form.gender" label="Gender Identity">
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </BaseSelect>

        <div class="md:col-span-2">
          <BaseInput 
            v-model="form.address" 
            label="Home / Permanent Address" 
            placeholder="123 Academic Way, Education District"
          />
        </div>
      </div>

      <!-- Validation Errors -->
      <div v-if="errors" class="p-4 bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 rounded-xl">
        <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-2">Registration Errors</p>
        <ul class="space-y-1">
          <li v-for="(errs, field) in errors" :key="field" class="text-xs font-bold text-rose-700 dark:text-rose-300">
            <span class="capitalize">{{ field }}:</span> {{ Array.isArray(errs) ? errs.join(', ') : errs }}
          </li>
        </ul>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end gap-3 pt-6 border-t border-secondary-100 dark:border-secondary-800">
        <BaseButton variant="ghost" type="button" @click="$emit('close')">Cancel</BaseButton>
        <BaseButton 
          variant="primary" 
          type="submit" 
          :loading="submitting"
        >
          {{ isEditing ? 'Save Changes' : 'Confirm Registration' }}
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
  name: '',
  email: '',
  password: '',
  phone: '',
  relationship: 'father',
  occupation: '',
  gender: 'male',
  address: ''
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    form.value = {
      name: '', email: '', password: '', phone: '',
      relationship: 'father', occupation: '', gender: 'male', address: ''
    }
  }
}, { immediate: true })

const submit = () => {
  emit('submit', { ...form.value })
}
</script>
