import { ref, watchEffect, onMounted } from 'vue'

export function useDarkMode() {
  const isDark = ref(localStorage.getItem('theme') === 'dark' || 
    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches))

  const toggleDarkMode = () => {
    isDark.value = !isDark.value
  }

  // Watch for changes and update the DOM + localStorage
  watchEffect(() => {
    if (isDark.value) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  })

  // Optional: Listen for system theme changes if no manual preference is set
  onMounted(() => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handler = (e) => {
      if (!localStorage.getItem('theme')) {
        isDark.value = e.matches
      }
    }
    mediaQuery.addEventListener('change', handler)
  })

  return {
    isDark,
    toggleDarkMode
  }
}
