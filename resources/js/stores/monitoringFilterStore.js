import { defineStore } from 'pinia'

const STORAGE_KEY = 'monitoring_filters_v2'

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return {
      regional_ids: Array.isArray(parsed?.regional_ids) ? parsed.regional_ids : [],
      plant_ids: Array.isArray(parsed?.plant_ids) ? parsed.plant_ids : [],
      station_codes: Array.isArray(parsed?.station_codes) ? parsed.station_codes : [],
      search: parsed?.search ?? '',
    }
  } catch {
    return null
  }
}

function savePersisted(filters) {
  try {
    const payload = {
      regional_ids: Array.isArray(filters?.regional_ids) ? filters.regional_ids : [],
      plant_ids: Array.isArray(filters?.plant_ids) ? filters.plant_ids : [],
      station_codes: Array.isArray(filters?.station_codes) ? filters.station_codes : [],
      search: filters?.search ?? '',
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
  } catch {
    // ignore
  }
}

export const useMonitoringFilterStore = defineStore('monitoringFilters', {
  state: () => ({
    regional_ids: [],
    plant_ids: [],
    station_codes: [],
    search: '',
  }),
  actions: {
    load() {
      const persisted = loadPersisted()
      if (persisted) {
        this.regional_ids = persisted.regional_ids
        this.plant_ids = persisted.plant_ids
        this.station_codes = persisted.station_codes
        this.search = persisted.search
      }
    },
    setFilters(next) {
      this.regional_ids = Array.isArray(next?.regional_ids) ? next.regional_ids : []
      this.plant_ids = Array.isArray(next?.plant_ids) ? next.plant_ids : []
      this.station_codes = Array.isArray(next?.station_codes) ? next.station_codes : []
      if (typeof next?.search === 'string') this.search = next.search
      savePersisted(this)
    },
    setSearch(search) {
      this.search = search ?? ''
      savePersisted(this)
    },
    clear() {
      this.regional_ids = []
      this.plant_ids = []
      this.station_codes = []
      this.search = ''
      savePersisted(this)
    },
  },
})


