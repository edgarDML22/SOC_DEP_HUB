import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'
import { validarFormatoQR } from '@/utils/qrValidator'
import { useAlerts } from '@/composables/useAlerts'

export const useScannerStore = defineStore('scanner', () => {
    const { actionToast } = useAlerts()

    // -------------------------------------------------------------------------
    // Estado de navegación
    // Flujo completo:
    //   MENU → LIST_CLASES / LIST_RESERVACIONES / LIST_TORNEO
    //        → SELECCION_METODO → ESCANER_ACTIVO → OUTPUT
    // -------------------------------------------------------------------------
    const paso              = ref('MENU')
    const categoriaActiva   = ref(null)   // 'CLASES' | 'RESERVACIONES' | 'TORNEO'
    const metodoIngreso     = ref(null)   // 'CAMARA' | 'MANUAL'
    const sesionActivaId    = ref(null)
    const reservaActivaId   = ref(null)
    const encuentroActivoId = ref(null)
    const codigoEscaneado   = ref(null)
    const resultados        = ref([])
    const loading           = ref(false)
    const error             = ref(null)

    // ---- Datos de listas (cacheadas por sesión del Hub) ----------------------
    const sesionesHoy       = ref([])
    const sesionesLoading   = ref(false)

    const reservacionesHoy  = ref([])
    const reservacionesLoading = ref(false)
    const hayReservaciones  = ref(false)   // se resuelve en el fetch

    const encuentrosHoy     = ref([])
    const encuentrosLoading = ref(false)
    const hayEncuentros     = ref(false)   // se resuelve en el fetch

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------
    const esCategoriaClases   = computed(() => categoriaActiva.value === 'CLASES')
    const esCategoriaReservas = computed(() => categoriaActiva.value === 'RESERVACIONES')
    const esCategoriaTorneo   = computed(() => categoriaActiva.value === 'TORNEO')
    const haySesionesHoy      = computed(() => sesionesHoy.value.length > 0)

    // Contexto legible de la reserva seleccionada (para el header del escáner)
    const reservaActiva = computed(() =>
        reservacionesHoy.value.find(r => r.id_reserva === reservaActivaId.value) ?? null
    )
    const encuentroActivo = computed(() =>
        encuentrosHoy.value.find(e => e.id_encuentro === encuentroActivoId.value) ?? null
    )

    // -------------------------------------------------------------------------
    // Navegación hacia adelante
    // -------------------------------------------------------------------------
    function seleccionarCategoria(categoria) {
        if (paso.value !== 'MENU') return
        categoriaActiva.value = categoria

        switch (categoria) {
            case 'CLASES':        paso.value = 'LIST_CLASES';        break
            case 'RESERVACIONES': paso.value = 'LIST_RESERVACIONES'; break
            case 'TORNEO':        paso.value = 'LIST_TORNEO';        break
        }
    }

    function seleccionarSesion(id) {
        sesionActivaId.value = id
        paso.value = 'SELECCION_METODO'
    }

    function seleccionarReserva(id) {
        reservaActivaId.value = id
        paso.value = 'SELECCION_METODO'
    }

    function seleccionarEncuentro(id) {
        encuentroActivoId.value = id
        paso.value = 'SELECCION_METODO'
    }

    function seleccionarMetodo(metodo) {
        if (paso.value !== 'SELECCION_METODO') return
        metodoIngreso.value = metodo
        paso.value = 'ESCANER_ACTIVO'
    }

    // -------------------------------------------------------------------------
    // Navegación hacia atrás — peldaño a peldaño
    // -------------------------------------------------------------------------
    function irAtras() {
        switch (paso.value) {
            case 'OUTPUT':
                codigoEscaneado.value = null
                resultados.value = []
                error.value = null
                paso.value = 'ESCANER_ACTIVO'
                break

            case 'ESCANER_ACTIVO':
                metodoIngreso.value = null
                paso.value = 'SELECCION_METODO'
                break

            case 'SELECCION_METODO':
                // Regresa a la lista de la categoría activa, limpiando la selección puntual
                sesionActivaId.value    = null
                reservaActivaId.value   = null
                encuentroActivoId.value = null
                switch (categoriaActiva.value) {
                    case 'CLASES':        paso.value = 'LIST_CLASES';        break
                    case 'RESERVACIONES': paso.value = 'LIST_RESERVACIONES'; break
                    case 'TORNEO':        paso.value = 'LIST_TORNEO';        break
                    default:              paso.value = 'MENU'
                }
                break

            case 'LIST_CLASES':
            case 'LIST_RESERVACIONES':
            case 'LIST_TORNEO':
                categoriaActiva.value = null
                paso.value = 'MENU'
                break

            case 'MENU':
                // El componente padre navega al Home del instructor
                break
        }
    }

    function resetHub() {
        paso.value              = 'MENU'
        categoriaActiva.value   = null
        metodoIngreso.value     = null
        sesionActivaId.value    = null
        reservaActivaId.value   = null
        encuentroActivoId.value = null
        codigoEscaneado.value   = null
        resultados.value        = []
        loading.value           = false
        error.value             = null
        // Las listas (sesiones/reservaciones/encuentros) NO se limpian — caché de sesión
    }

    // -------------------------------------------------------------------------
    // Validación y envío
    // -------------------------------------------------------------------------
    function validarFormatoQRLocal(codigo) {
        return validarFormatoQR(codigo)
    }

    async function procesarCodigo(codigo) {
        const codigoNormalizado = (codigo ?? '').toUpperCase()

        if (!validarFormatoQR(codigoNormalizado)) {
            error.value = 'Formato de código inválido. Verifique e intente de nuevo.'
            actionToast(error.value, 'error')
            return
        }

        codigoEscaneado.value = codigoNormalizado
        await enviarRegistro(codigoNormalizado)
    }

    async function enviarRegistro(codigo) {
        loading.value = true
        error.value   = null
        try {
            const payload = {
                id:         codigo,
                id_sesion:  sesionActivaId.value ?? reservaActivaId.value ?? encuentroActivoId.value,
                fase:       'ingreso',
            }
            const response = await api.post('instructor/register-event', payload)
            resultados.value = [response.data, ...resultados.value]
            paso.value = 'OUTPUT'
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al registrar el acceso.'
            actionToast(error.value, 'error')
        } finally {
            loading.value = false
        }
    }

    // -------------------------------------------------------------------------
    // Fetch unificado de datos del día
    // -------------------------------------------------------------------------
    async function fetchDatosMenu() {
        if (sesionesHoy.value.length > 0 || reservacionesHoy.value.length > 0 || encuentrosHoy.value.length > 0) return

        sesionesLoading.value      = true
        reservacionesLoading.value = true
        encuentrosLoading.value    = true

        try {
            const res = await api.get('/instructor/datos-hoy')

            sesionesHoy.value      = res.data?.sesiones      ?? []
            reservacionesHoy.value = res.data?.reservaciones ?? []
            encuentrosHoy.value    = res.data?.encuentros    ?? []

            hayReservaciones.value = reservacionesHoy.value.length > 0
            hayEncuentros.value    = encuentrosHoy.value.length > 0
        } catch {
            sesionesHoy.value      = []
            reservacionesHoy.value = []
            encuentrosHoy.value    = []
            hayReservaciones.value = false
            hayEncuentros.value    = false
            actionToast('No se pudieron cargar los datos del día', 'error')
        } finally {
            sesionesLoading.value      = false
            reservacionesLoading.value = false
            encuentrosLoading.value    = false
        }
    }

    return {
        // Estado
        paso, categoriaActiva, metodoIngreso,
        sesionActivaId, reservaActivaId, encuentroActivoId,
        codigoEscaneado, resultados, loading, error,
        // Listas
        sesionesHoy, sesionesLoading,
        reservacionesHoy, reservacionesLoading, hayReservaciones,
        encuentrosHoy, encuentrosLoading, hayEncuentros,
        // Getters
        esCategoriaClases, esCategoriaReservas, esCategoriaTorneo,
        haySesionesHoy, reservaActiva, encuentroActivo,
        // Navegación
        seleccionarCategoria, seleccionarSesion,
        seleccionarReserva, seleccionarEncuentro,
        seleccionarMetodo, irAtras, resetHub,
        // Acciones QR
        validarFormatoQR: validarFormatoQRLocal,
        procesarCodigo,
        // Fetches
        fetchDatosMenu,
    }
})
