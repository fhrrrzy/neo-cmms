import { defineStore } from 'pinia'

const STORAGE_KEY = 'monitoring_filters_v1'

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return {
      regional_id: parsed?.regional_id ?? undefined,
      plant_id: parsed?.plant_id ?? undefined,
      station_id: parsed?.station_id ?? undefined,
      search: parsed?.search ?? '',
    }
  } catch {
    return null
  }
}

function savePersisted(filters) {
  try {
    const payload = {
      regional_id: filters?.regional_id ?? undefined,
      plant_id: filters?.plant_id ?? undefined,
      station_id: filters?.station_id ?? undefined,
      search: filters?.search ?? '',
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
  } catch {
    // ignore
  }
}

export const useMonitoringFilterStore = defineStore('monitoringFilters', {
  state: () => ({
    regional_id: undefined,
    plant_id: undefined,
    station_id: undefined,
    search: '',
  }),
  actions: {
    load() {
      const persisted = loadPersisted()
      if (persisted) {
        this.regional_id = persisted.regional_id
        this.plant_id = persisted.plant_id
        this.station_id = persisted.station_id
        this.search = persisted.search
      }
    },
    setFilters(next) {
      this.regional_id = next?.regional_id
      this.plant_id = next?.plant_id
      this.station_id = next?.station_id
      if (typeof next?.search === 'string') this.search = next.search
      savePersisted(this)
    },
    setSearch(search) {
      this.search = search ?? ''
      savePersisted(this)
    },
    clear() {
      this.regional_id = undefined
      this.plant_id = undefined
      this.station_id = undefined
      this.search = ''
      savePersisted(this)
    },
  },
})


