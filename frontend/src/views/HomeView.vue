<template>
  <div class="relative min-h-[calc(100vh-8rem)] flex flex-col items-center justify-center p-6 overflow-hidden selection:bg-primary-500/30">
    <!-- Immersive Background Elements -->
    <div class="absolute inset-0 z-0 pointer-events-none">
      <div class="absolute top-[-10%] left-[-5%] w-[50%] h-[50%] bg-primary-500/10 blur-[120px] rounded-full animate-pulse-slow"></div>
      <div class="absolute bottom-[-10%] right-[-5%] w-[50%] h-[50%] bg-indigo-500/10 blur-[120px] rounded-full animate-pulse-delayed"></div>
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 text-center max-w-4xl space-y-12">
      <div class="space-y-6">
        <div class="inline-flex items-center gap-3 px-6 py-2 rounded-full bg-primary-50 dark:bg-primary-900/20 border border-primary-100 dark:border-primary-800 text-primary-600 dark:text-primary-400 animate-fade-in">
          <SparklesIcon class="w-5 h-5 animate-spin-slow" />
          <span class="text-xs font-black uppercase tracking-[0.2em]">Next-Gen Academic Protocol</span>
        </div>

        <h1 class="text-6xl md:text-8xl font-black text-secondary-900 dark:text-white tracking-tighter leading-[1.1] animate-fade-in-up">
          REIMAGINING <br />
          <span class="bg-gradient-to-r from-primary-600 to-indigo-600 bg-clip-text text-transparent">ATTENDANCE</span>
        </h1>

        <p class="text-lg md:text-xl text-secondary-500 dark:text-secondary-400 font-bold max-w-2xl mx-auto leading-relaxed animate-fade-in-delayed">
          A premium, high-fidelity orchestration system for educational institutions. Track, analyze, and optimize institutional engagement with precision.
        </p>
      </div>

      <!-- Action Portal -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-6 animate-fade-in-late">
        <BaseButton variant="primary" size="xl" shadow class="min-w-[200px]" @click="$router.push('/dashboard')">
          Access Dashboard
          <template #icon-right><ArrowRightIcon class="w-5 h-5" /></template>
        </BaseButton>
        
        <BaseButton variant="ghost" size="xl" class="min-w-[200px]" @click="$router.push('/about')">
          Protocol Details
        </BaseButton>
      </div>

      <!-- Feature Teasers -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-20 animate-fade-in-last">
        <div v-for="(feature, index) in features" :key="index" class="glass-card p-8 rounded-[2.5rem] border border-secondary-100 dark:border-secondary-800 text-left hover:border-primary-300 transition-all duration-300">
           <div class="w-12 h-12 bg-secondary-50 dark:bg-secondary-800 rounded-2xl flex items-center justify-center text-primary-600 dark:text-primary-400 mb-6 group-hover:scale-110 transition-transform">
             <component :is="feature.icon" class="w-6 h-6" />
           </div>
           <h3 class="text-lg font-black text-secondary-900 dark:text-white tracking-tight">{{ feature.title }}</h3>
           <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-2 font-bold leading-relaxed">{{ feature.description }}</p>
        </div>
      </div>
    </div>

    <!-- Footer Decoration -->
    <div class="absolute bottom-12 left-1/2 -translate-x-1/2 opacity-20 hidden lg:block">
      <div class="flex items-center gap-12 grayscale">
        <div class="h-6 w-32 bg-secondary-300 rounded-full"></div>
        <div class="h-6 w-32 bg-secondary-300 rounded-full"></div>
        <div class="h-6 w-32 bg-secondary-300 rounded-full"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue'
import { 
  SparklesIcon, ArrowRightIcon, ChartBarIcon, 
  ShieldCheckIcon, LightningBoltIcon 
} from '@heroicons/vue/outline'

const features = [
  {
    title: 'Real-time Analytics',
    description: 'Instant visualization of institutional patterns and engagement metrics.',
    icon: ChartBarIcon
  },
  {
    title: 'Enterprise Security',
    description: 'Role-based access control and high-fidelity data integrity protocols.',
    icon: ShieldCheckIcon
  },
  {
    title: 'Precision Marking',
    description: 'Rapid, optimized attendance marking system for Faculty and Staff.',
    icon: LightningBoltIcon
  }
]
</script>

<style scoped>
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(40px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes pulse-slow {
  0%, 100% { opacity: 0.1; transform: scale(1); }
  50% { opacity: 0.15; transform: scale(1.1); }
}

.animate-fade-in { animation: fadeIn 1s ease-out both; }
.animate-fade-in-up { animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) both; }
.animate-fade-in-delayed { animation: fadeIn 1s ease-out 0.4s both; }
.animate-fade-in-late { animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both; }
.animate-fade-in-last { animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.8s both; }
.animate-pulse-slow { animation: pulse-slow 8s infinite ease-in-out; }
.animate-pulse-delayed { animation: pulse-slow 8s infinite ease-in-out 4s; }
.animate-spin-slow { animation: spin 4s linear infinite; }
</style>
