
<template>
  <div class="bg-white p-6 rounded-xl shadow-md border border-slate-100 flex items-center justify-between transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-lg">
    <div>
      <p class="text-indigo-500 text-sm font-semibold uppercase tracking-wide">{{ title }}</p>
      <p class="mt-1 text-3xl font-extrabold text-slate-800">{{ value }}</p>
    </div>
    <div class="p-3 bg-indigo-100 rounded-full text-indigo-600">
      <component :is="iconComponent" class="w-6 h-6" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { UsersIcon, AcademicCapIcon, UserGroupIcon, CheckCircleIcon } from '@heroicons/vue/outline'

const props = defineProps({
  title: String,
  value: [Number, String],
  icon: String, // 'users', 'academic-cap', 'user-group', 'check-circle'
})

const iconMap = {
  users: UsersIcon,
  'academic-cap': AcademicCapIcon,
  'user-group': UserGroupIcon,
  'check-circle': CheckCircleIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || UsersIcon)
</script>