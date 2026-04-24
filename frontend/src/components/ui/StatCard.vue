<template>
  <div class="glass-card p-6 rounded-2xl flex items-center justify-between group transition-all duration-300 hover:shadow-premium-hover hover:-translate-y-1">
    <div class="space-y-1">
      <p class="text-[10px] font-black uppercase tracking-widest" :class="colorVariants[variant].text">
        {{ title }}
      </p>
      <div class="flex items-baseline gap-2">
        <h3 class="text-3xl font-black text-secondary-900 dark:text-white leading-none tracking-tighter">
          {{ value }}
        </h3>
        <span v-if="trend" class="text-[10px] font-bold" :class="trend > 0 ? 'text-emerald-500' : 'text-rose-500'">
          {{ trend > 0 ? '+' : '' }}{{ trend }}%
        </span>
      </div>
    </div>
    <div 
      class="p-3.5 rounded-2xl transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 shadow-sm"
      :class="colorVariants[variant].bg"
    >
      <slot name="icon">
        <component :is="icon" class="w-6 h-6" :class="colorVariants[variant].icon" />
      </slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [Number, String],
  trend: [Number, String],
  icon: [Object, Function],
  variant: {
    type: String,
    default: 'primary'
  }
})

const colorVariants = {
  primary: {
    text: 'text-primary-500',
    bg: 'bg-primary-50 dark:bg-primary-900/40',
    icon: 'text-primary-600 dark:text-primary-400'
  },
  success: {
    text: 'text-emerald-500',
    bg: 'bg-emerald-50 dark:bg-emerald-900/40',
    icon: 'text-emerald-600 dark:text-emerald-400'
  },
  warning: {
    text: 'text-amber-500',
    bg: 'bg-amber-50 dark:bg-amber-900/40',
    icon: 'text-amber-600 dark:text-amber-400'
  },
  danger: {
    text: 'text-rose-500',
    bg: 'bg-rose-50 dark:bg-rose-900/40',
    icon: 'text-rose-600 dark:text-rose-400'
  },
  info: {
    text: 'text-blue-500',
    bg: 'bg-blue-50 dark:bg-blue-900/40',
    icon: 'text-blue-600 dark:text-blue-400'
  }
}
</script>