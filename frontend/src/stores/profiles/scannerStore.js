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
    //        → SELECCION_METODO → ESCANER_ACTIVO → OUTPUT | PASE_LISTA
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
    const hayReservaciones  = ref(false)

    const encuentrosHoy     = ref([])
    const encuentrosLoading = ref(false)
    const hayEncuentros     = ref(false)

    // ---- US-33: Pase de lista -----------------------------------------------
    const listaInscritos    = ref([])   // precargada para clases cerradas
    const listaLoading      = ref(false)
    const aforoLleno        = ref(false)
    const listaConfirmada   = ref(false)
    // Set local para deduplicación sin request HTTP
    const codigosYaVistos   = ref(new Set())

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------
    const esCategoriaClases   = computed(() => categoriaActiva.value === 'CLASES')
    const esCategoriaReservas = computed(() => categoriaActiva.value === 'RESERVACIONES')
    const esCategoriaTorneo   = computed(() => categoriaActiva.value === 'TORNEO')

    // Tipo e ID de actividad normalizados para el payload del backend
    const tipoActividadActual = computed(() => {
        if (esCategoriaClases.value)   return 'sesion'
        if (esCategoriaReservas.value) return 'reserva'
        if (esCategoriaTorneo.value)   return 'torneo'
        return null
    })
    const idActividadActual = computed(() => {
        if (esCategoriaClases.value)   return sesionActivaId.value
        if (esCategoriaReservas.value) return reservaActivaId.value
        if (esCategoriaTorneo.value)   return encuentroActivoId.value
        return null
    })
    const haySesionesHoy      = computed(() => sesionesHoy.value.length > 0)

    const sesionActiva = computed(() =>
        sesionesHoy.value.find(s => s.id_sesion === sesionActivaId.value) ?? null
    )

    const esSesionCerrada = computed(() => sesionActiva.value?.requiere_inscripcion === true)

    const reservaActiva = computed(() =>
        reservacionesHoy.value.find(r => r.id_reserva === reservaActivaId.value) ?? null
    )
    const encuentroActivo = computed(() =>
        encuentrosHoy.value.find(e => e.id_encuentro === encuentroActivoId.value) ?? null
    )

    const totalRegistrados = computed(() => {
        if (esSesionCerrada.value) {
            return listaInscritos.value.filter(i => i.estatus_asistencia === 'PRESENTE').length
        }
        return resultados.value.filter(r => r.success).length
    })

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

    async function seleccionarSesion(id) {
        sesionActivaId.value = id
        paso.value = 'SELECCION_METODO'

        // Precargar lista de inscritos si la sesión es cerrada
        const sesion = sesionesHoy.value.find(s => s.id_sesion === id)
        if (sesion?.requiere_inscripcion) {
            await fetchListaInscritos(id)
        }
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
            case 'PASE_LISTA':
                // Desde pase de lista volver al escáner (permite seguir escaneando)
                paso.value = 'ESCANER_ACTIVO'
                break

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
                sesionActivaId.value    = null
                reservaActivaId.value   = null
                encuentroActivoId.value = null
                listaInscritos.value    = []
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
        // US-33
        listaInscritos.value    = []
        listaLoading.value      = false
        aforoLleno.value        = false
        listaConfirmada.value   = false
        codigosYaVistos.value   = new Set()
        // Listas del menú: NO se limpian (caché de sesión del Hub)
    }

    // -------------------------------------------------------------------------
    // Validación QR
    // -------------------------------------------------------------------------
    function validarFormatoQRLocal(codigo) {
        return validarFormatoQR(codigo)
    }

    // -------------------------------------------------------------------------
    // Procesamiento de código — punto de entrada único para cámara y manual
    // -------------------------------------------------------------------------
    async function procesarCodigo(codigo) {
        if (aforoLleno.value) return

        const codigoNormalizado = (codigo ?? '').toUpperCase()

        if (!validarFormatoQR(codigoNormalizado)) {
            error.value = 'Formato de código inválido. Verifique e intente de nuevo.'
            actionToast(error.value, 'error')
            return
        }

        // Deduplicación local — no consume red en el segundo intento
        if (codigosYaVistos.value.has(codigoNormalizado)) {
            actionToast('Este socio ya fue registrado en esta sesión.', 'warning')
            return
        }

        codigoEscaneado.value = codigoNormalizado

        if (esCategoriaClases.value) {
            await procesarCodigoClase(codigoNormalizado)
        } else {
            await enviarRegistro(codigoNormalizado)
        }
    }

    // ---- Lógica diferenciada para Mis Clases --------------------------------
    async function procesarCodigoClase(codigo) {
        if (esSesionCerrada.value) {
            // Clase cerrada: buscar en la lista preloaded
            const inscrito = listaInscritos.value.find(i => i.codigo_qr === codigo)

            if (!inscrito) {
                actionToast('Socio no inscrito en esta clase.', 'error')
                return
            }

            if (inscrito.estatus_asistencia === 'PRESENTE') {
                actionToast('Este socio ya fue registrado en esta sesión.', 'warning')
                return
            }

            // Enviar al backend y actualizar localmente
            await enviarRegistroClase(codigo, inscrito)
        } else {
            // Clase abierta: lista dinámica
            await enviarRegistroClase(codigo, null)
        }
    }

    async function enviarRegistroClase(codigo, inscritoLocal) {
        loading.value = true
        error.value   = null

        try {
            const response = await api.post('instructor/register-event', {
                tipo_actividad: tipoActividadActual.value,
                id_actividad:   idActividadActual.value,
                codigos_qr:     [codigo],
                metodo:         metodoIngreso.value === 'CAMARA' ? 'ESCANER_QR' : 'INGRESO_MANUAL',
            })

            const data = response.data

            // Marcar como visto para deduplicación futura
            codigosYaVistos.value.add(codigo)

            if (esSesionCerrada.value && inscritoLocal) {
                // Actualizar la lista preloaded localmente — sin refetch
                inscritoLocal.estatus_asistencia = 'PRESENTE'
            } else {
                // Clase abierta: agregar a resultados dinámicos
                resultados.value = [data, ...resultados.value]
            }

            // Navegar al pase de lista en clases
            paso.value = 'PASE_LISTA'

            // Verificar si el aforo se llenó
            if (data.aforo_lleno) {
                setAforoLleno(true)
            } else if (sesionActiva.value?.cupo_maximo) {
                const registrados = esSesionCerrada.value
                    ? listaInscritos.value.filter(i => i.estatus_asistencia === 'PRESENTE').length
                    : resultados.value.filter(r => r.success).length

                if (registrados >= sesionActiva.value.cupo_maximo) {
                    setAforoLleno(true)
                }
            }

        } catch (err) {
            const status  = err.response?.status
            const message = err.response?.data?.message

            if (status === 409 && message === 'Aforo máximo alcanzado.') {
                setAforoLleno(true)
                paso.value = 'PASE_LISTA'
            } else {
                error.value = message || 'Error al registrar el acceso.'
                actionToast(error.value, 'error')
            }
        } finally {
            loading.value = false
        }
    }

    async function enviarRegistro(codigo) {
        loading.value = true
        error.value   = null
        try {
            const response = await api.post('instructor/register-event', {
                tipo_actividad: tipoActividadActual.value,
                id_actividad:   idActividadActual.value,
                codigos_qr:     [codigo],
                metodo:         metodoIngreso.value === 'CAMARA' ? 'ESCANER_QR' : 'INGRESO_MANUAL',
            })
            codigosYaVistos.value.add(codigo)
            resultados.value = [response.data, ...resultados.value]

            // Marcar la reserva como COMPLETADA en el caché local
            // para que la lista refleje el cambio sin recargar
            if (esCategoriaReservas.value && reservaActivaId.value) {
                const reserva = reservacionesHoy.value.find(r => r.id_reserva === reservaActivaId.value)
                if (reserva) reserva.estatus_operativo = 'COMPLETADA'
            }

            paso.value = 'OUTPUT'
        } catch (err) {
            const status  = err.response?.status
            const message = err.response?.data?.message ?? ''

            // QR válido pero no corresponde al titular de la reserva
            if (status === 403 && message.includes('no corresponde')) {
                resultados.value = [{
                    success:  false,
                    qr_mismatch: true,
                    message,
                    data: { codigo_qr: codigo },
                }, ...resultados.value]
                paso.value = 'OUTPUT'
                return
            }

            error.value = message || 'Error al registrar el acceso.'
            actionToast(error.value, 'error')
        } finally {
            loading.value = false
        }
    }

    // -------------------------------------------------------------------------
    // US-33: acciones de pase de lista
    // -------------------------------------------------------------------------
    async function fetchListaInscritos(idSesion) {
        listaLoading.value = true
        try {
            const res = await api.get(`instructor/sesiones/${idSesion}/lista-inscriptos`)
            listaInscritos.value = res.data?.data ?? []

            // Reconstruir Set de códigos ya vistos desde el estado del servidor
            codigosYaVistos.value = new Set(
                listaInscritos.value
                    .filter(i => i.estatus_asistencia === 'PRESENTE')
                    .map(i => i.codigo_qr)
                    .filter(Boolean)
            )
        } catch {
            actionToast('No se pudo cargar la lista de inscritos.', 'error')
            listaInscritos.value = []
        } finally {
            listaLoading.value = false
        }
    }

    function setAforoLleno(valor) {
        aforoLleno.value = valor
    }

    async function confirmarAsistencia() {
        if (!sesionActivaId.value) return

        loading.value = true
        try {
            await api.post(`instructor/sesiones/${sesionActivaId.value}/confirmar-asistencia`)
            listaConfirmada.value = true
            actionToast('Lista de asistencia confirmada exitosamente.', 'success')
            resetHub()
        } catch (err) {
            const message = err.response?.data?.message || 'No se pudo confirmar la lista. Intente de nuevo.'
            actionToast(message, 'error')
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

            hayReservaciones.value = reservacionesHoy.value.some(r => r.estatus_operativo === 'ACTIVA')
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
        // Listas del menú
        sesionesHoy, sesionesLoading,
        reservacionesHoy, reservacionesLoading, hayReservaciones,
        encuentrosHoy, encuentrosLoading, hayEncuentros,
        // US-33
        listaInscritos, listaLoading, aforoLleno, listaConfirmada,
        totalRegistrados,
        // Getters
        esCategoriaClases, esCategoriaReservas, esCategoriaTorneo,
        haySesionesHoy, sesionActiva, esSesionCerrada,
        reservaActiva, encuentroActivo,
        tipoActividadActual, idActividadActual,
        // Navegación
        seleccionarCategoria, seleccionarSesion,
        seleccionarReserva, seleccionarEncuentro,
        seleccionarMetodo, irAtras, resetHub,
        // Acciones QR
        validarFormatoQR: validarFormatoQRLocal,
        procesarCodigo,
        // US-33 acciones
        fetchListaInscritos, setAforoLleno, confirmarAsistencia,
        // Fetches
        fetchDatosMenu,
    }
})
