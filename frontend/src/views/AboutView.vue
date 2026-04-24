<template>
  <div class="relative min-h-[calc(100vh-8rem)] py-20 px-6">
    <div class="max-w-4xl mx-auto space-y-16 relative z-10">
      <!-- Section Header -->
      <div class="text-center space-y-4">
        <h1 class="text-4xl font-black text-secondary-900 dark:text-white tracking-tighter">THE PROTOCOL</h1>
        <p class="text-sm font-bold text-secondary-400 uppercase tracking-[0.4em]">Understanding E-School Architecture</p>
      </div>

      <!-- Core Philosophy -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <div class="space-y-6">
          <h2 class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">Mission Critical Data</h2>
          <p class="text-secondary-600 dark:text-secondary-400 font-bold leading-relaxed">
            E-School Attendance Tracker is engineered to serve as the single source of truth for institutional engagement. By bridging the gap between administrative oversight and classroom reality, we empower educators to focus on what matters most: human development.
          </p>
          <div class="flex items-center gap-4 text-primary-600 font-black text-xs uppercase tracking-widest">
            <ShieldCheckIcon class="w-5 h-5" />
            Vetted Security Standards
          </div>
        </div>
        <div class="glass-card p-10 rounded-[3rem] border border-secondary-100 dark:border-secondary-800 shadow-premium aspect-square flex items-center justify-center">
          <AcademicCapIcon class="w-24 h-24 text-secondary-50 dark:text-secondary-900" />
        </div>
      </div>

      <!-- Institutional Values -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 pt-12">
        <div v-for="value in values" :key="value.title" class="space-y-4 p-6 rounded-3xl bg-secondary-50/50 dark:bg-secondary-900/30">
          <div class="w-10 h-10 rounded-xl bg-white dark:bg-secondary-800 flex items-center justify-center shadow-sm">
            <component :is="value.icon" class="w-5 h-5 text-primary-600" />
          </div>
          <h4 class="text-sm font-black text-secondary-900 dark:text-white uppercase tracking-widest">{{ value.title }}</h4>
          <p class="text-xs text-secondary-500 dark:text-secondary-400 font-bold leading-relaxed">{{ value.desc }}</p>
        </div>
      </div>

      <!-- Call to Action -->
      <div class="pt-16 border-t border-secondary-100 dark:border-secondary-800 text-center">
        <BaseButton variant="primary" size="lg" shadow @click="$router.push('/login')">
          Initialize Platform
        </BaseButton>
      </div>
    </div>

    <!-- Background Accents -->
    <div class="fixed inset-0 z-0 pointer-events-none opacity-40">
      <div class="absolute top-[30%] right-[-10%] w-96 h-96 bg-primary-100/20 dark:bg-primary-900/10 blur-3xl rounded-full"></div>
      <div class="absolute bottom-[20%] left-[-10%] w-96 h-96 bg-indigo-100/20 dark:bg-indigo-900/10 blur-3xl rounded-full"></div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'
import { 
  AcademicCapIcon, ShieldCheckIcon, EyeIcon, 
  BeakerIcon, LibraryIcon 
} from '@heroicons/vue/outline'

const values = [
  {
    title: 'Transparency',
    desc: 'Full visibility for parents, students, and administrators.',
    icon: EyeIcon
  },
  {
    title: 'Innovation',
    desc: 'Utilizing modern protocol standards for institutional data.',
    icon: BeakerIcon
  },
  {
    title: 'Archive',
    desc: 'Historical persistence of attendance and session logs.',
    icon: LibraryIcon
  }
]
</script>
