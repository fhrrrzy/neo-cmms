import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useFullscreenStore = defineStore('fullscreen', () => {
  const isFullscreen = ref(false)

  const request = async (targetEl) => {
    try {
      const el = targetEl || document.documentElement
      if (el.requestFullscreen) await el.requestFullscreen()
      else if (el.webkitRequestFullscreen) await el.webkitRequestFullscreen()
      else if (el.msRequestFullscreen) await el.msRequestFullscreen()
      isFullscreen.value = true
    } catch (_) {
      // ignore
    }
  }

  const exit = async () => {
    try {
      if (document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
        if (document.exitFullscreen) await document.exitFullscreen()
        else if (document.webkitExitFullscreen) await document.webkitExitFullscreen()
        else if (document.msExitFullscreen) await document.msExitFullscreen()
      }
      isFullscreen.value = false
    } catch (_) {
      // ignore
    }
  }

  const toggle = async (targetEl) => {
    if (isFullscreen.value) return exit()
    return request(targetEl)
  }

  const handleChange = () => {
    isFullscreen.value = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement)
  }

  // Setup listeners immediately when store is created
  if (typeof window !== 'undefined') {
    document.addEventListener('fullscreenchange', handleChange)
    document.addEventListener('webkitfullscreenchange', handleChange)
    document.addEventListener('MSFullscreenChange', handleChange)
  }

  return { isFullscreen, request, exit, toggle }
})


