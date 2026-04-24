<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm" @click="$emit('close')"></div>

        <!-- Modal Panel -->
        <Transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
        >
          <div
            v-if="show"
            class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white p-8 shadow-2xl transition-all dark:bg-secondary-900 border border-white/20 dark:border-white/5"
          >
            <!-- Close Button -->
            <button
              @click="$emit('close')"
              class="absolute right-6 top-6 text-secondary-400 hover:text-secondary-600 dark:hover:text-white transition-colors"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Header -->
            <div class="mb-6">
              <h3 v-if="title" class="text-2xl font-black text-secondary-900 dark:text-white tracking-tight">
                {{ title }}
              </h3>
              <p v-if="description" class="mt-1 text-sm text-secondary-500 dark:text-secondary-400">
                {{ description }}
              </p>
            </div>

            <!-- Body -->
            <div class="max-h-[70vh] overflow-y-auto pr-2 custom-scrollbar">
              <slot></slot>
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="mt-8 flex justify-end gap-3">
              <slot name="footer"></slot>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  show: Boolean,
  title: String,
  description: String
})
defineEmits(['close'])
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-secondary-200 dark:bg-secondary-800 rounded-full;
}
</style>
