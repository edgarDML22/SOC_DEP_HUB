import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useReservacionAdminStore = defineStore('reservacionAdmin', () => {
    // State
    const reservaciones = ref([])
    const filtersMeta = ref({
        espacios: [],
        socios: [],
        disciplinas: []
    })
    const statsCache = ref({
        hoy: null,
        semana: null,
        mes: null
    })
    const loading = ref({
        reservaciones: false,
        filtersMeta: false,
        stats: false
    })

    // Actions
    const fetchReservaciones = async (params = {}) => {
        loading.value.reservaciones = true
        try {
            // Limpiar parámetros vacíos para no enviarlos en la query string
            const cleanParams = Object.fromEntries(
                Object.entries(params).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
            )
            
            const response = await api.get('/reservations/admin/list', { params: cleanParams })
            if (response.data && response.data.success) {
                reservaciones.value = response.data.data || []
            }
        } catch (error) {
            console.error('Error fetching reservaciones:', error)
            reservaciones.value = []
        } finally {
            loading.value.reservaciones = false
        }
    }

    const fetchFiltersMeta = async () => {
        loading.value.filtersMeta = true
        try {
            const response = await api.get('/reservations/admin/filters-meta')
            if (response.data && response.data.success) {
                filtersMeta.value = response.data.data || { espacios: [], socios: [], disciplinas: [] }
            }
        } catch (error) {
            console.error('Error fetching filters meta:', error)
        } finally {
            loading.value.filtersMeta = false
        }
    }

    const activeRequests = {}

    const fetchStats = async (rango, silent = false, force = false) => {
        if (!force && statsCache.value[rango]) {
            return statsCache.value[rango]
        }

        if (!force && activeRequests[rango]) {
            if (!silent) loading.value.stats = true
            await activeRequests[rango]
            if (!silent) loading.value.stats = false
            return statsCache.value[rango]
        }

        if (!silent) loading.value.stats = true
        
        activeRequests[rango] = api.get('/reservations/admin/stats', { params: { rango } })
        
        try {
            const response = await activeRequests[rango]
            if (response.data && response.data.success) {
                statsCache.value[rango] = response.data.data
                return statsCache.value[rango]
            }
        } catch (error) {
            console.error('Error fetching stats:', error)
            return null
        } finally {
            delete activeRequests[rango]
            if (!silent) loading.value.stats = false
        }
    }

    return {
        reservaciones,
        filtersMeta,
        statsCache,
        loading,
        fetchReservaciones,
        fetchFiltersMeta,
        fetchStats
    }
})
