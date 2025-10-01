import { defineStore } from 'pinia'

const todayIso = () => new Date().toISOString().split('T')[0]
const daysAgoIso = (days) =>
  new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString().split('T')[0]

const STORAGE_KEY = 'global_date_range'

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (parsed?.start && parsed?.end) return parsed
    return null
  } catch (e) {
    return null
  }
}

function savePersisted(range) {
  try {
    if (!range?.start || !range?.end) return
    localStorage.setItem(STORAGE_KEY, JSON.stringify(range))
  } catch (e) {
    // ignore
  }
}

export const useDateRangeStore = defineStore('dateRange', {
  state: () => {
    const persisted = loadPersisted()
    return {
      start: persisted?.start ?? daysAgoIso(7),
      end: persisted?.end ?? todayIso(),
    }
  },
  getters: {
    range: (state) => ({ start: state.start, end: state.end }),
  },
  actions: {
    setRange(next) {
      if (!next || !next.start || !next.end) return
      this.start = next.start
      this.end = next.end
      savePersisted({ start: this.start, end: this.end })
    },
  },
})


