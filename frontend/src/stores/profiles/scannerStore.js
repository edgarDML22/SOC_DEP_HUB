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
    //                                            → PASE_LISTA → CONFIRMACION_PREVIA → OUTPUT
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
    // listaInscritos contiene objetos con la forma del backend:
    //   { id_inscripcion?, id_usuario, tipo_usuario, nombre, codigo_qr, asistencia: bool }
    // Para sesiones CERRADAS arranca poblada (inscritos preloaded).
    // Para sesiones ABIERTAS arranca vacía y crece con fetchQrLookup().
    const listaInscritos    = ref([])
    const listaLoading      = ref(false)
    const aforoLleno        = ref(false)
    const listaConfirmada   = ref(false)
    // Set local para deduplicación O(1) sin request HTTP
    const codigosYaVistos   = ref(new Set())

    // ---- Lookup dinámico para sesiones ABIERTAS -----------------------------
    const lookupLoading     = ref(false)
    const codigoEnLookup    = ref(null)

    // ---- Alerta contextual bajo el visor del escáner ------------------------
    // { tipo: 'success'|'warning'|'error', mensaje: string } | null
    const alertaEscaneo     = ref(null)
    let _alertaTimer        = null

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

    // ---- Segmentación de la lista para el Segmented Control -----------------
    const inscritosConfirmados = computed(() =>
        listaInscritos.value.filter(i => i.asistencia === true)
    )
    const inscritosNoConfirmados = computed(() =>
        listaInscritos.value.filter(i => i.asistencia === false)
    )

    const totalRegistrados = computed(() => inscritosConfirmados.value.length)

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

        // Precargar inscritos en sesiones cerradas; en abiertas la lista arranca vacía
        // y crece dinámicamente vía fetchQrLookup().
        const sesion = sesionesHoy.value.find(s => s.id_sesion === id)
        if (sesion?.requiere_inscripcion) {
            await fetchListaInscritos(id)
        } else {
            listaInscritos.value  = []
            codigosYaVistos.value = new Set()
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
            case 'CONFIRMACION_PREVIA':
                // Volver a editar el pase de lista
                paso.value = 'PASE_LISTA'
                break

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
        // Lookup dinámico
        lookupLoading.value     = false
        codigoEnLookup.value    = null
        // Alerta contextual
        clearAlerta()
        // Listas del menú: NO se limpian (caché de sesión del Hub)
    }

    // -------------------------------------------------------------------------
    // Validación QR
    // -------------------------------------------------------------------------
    function validarFormatoQRLocal(codigo) {
        return validarFormatoQR(codigo)
    }

    // -------------------------------------------------------------------------
    // Alerta contextual — barra inline bajo el visor del escáner
    // -------------------------------------------------------------------------
    function setAlerta(tipo, mensaje, ttl = 3000) {
        alertaEscaneo.value = { tipo, mensaje }
        if (_alertaTimer) clearTimeout(_alertaTimer)
        if (ttl > 0) {
            _alertaTimer = setTimeout(() => { alertaEscaneo.value = null }, ttl)
        }
    }

    function clearAlerta() {
        if (_alertaTimer) {
            clearTimeout(_alertaTimer)
            _alertaTimer = null
        }
        alertaEscaneo.value = null
    }

    // -------------------------------------------------------------------------
    // Procesamiento de código — punto de entrada único para cámara y manual
    // -------------------------------------------------------------------------
    async function procesarCodigo(codigo) {
        if (aforoLleno.value) return
        if (lookupLoading.value) return  // evitar lookups concurrentes

        const codigoNormalizado = (codigo ?? '').toUpperCase()

        if (!validarFormatoQR(codigoNormalizado)) {
            setAlerta('error', 'Código QR no reconocido')
            return
        }

        // Deduplicación local — no consume red en el segundo intento
        if (codigosYaVistos.value.has(codigoNormalizado)) {
            setAlerta('warning', 'Este participante ya fue confirmado')
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
    //
    // SESIÓN CERRADA: validación 100% local contra la lista preloaded.
    //   - Match en lista → marcar asistencia=true (sin POST individual)
    //   - No match → alerta de no inscrito
    //   El POST consolidado ocurre al confirmar la lista (enviarListaFinal).
    //
    // SESIÓN ABIERTA: la lista crece dinámicamente.
    //   - Lookup local primero (evita request si ya está)
    //   - GET /qr-lookup para resolver el QR contra codigos_qr o pases_diarios
    //   - El POST consolidado ocurre al confirmar (enviarListaFinal).
    //
    async function procesarCodigoClase(codigo) {
        if (esSesionCerrada.value) {
            // Validación local contra inscritos preloaded
            const inscrito = listaInscritos.value.find(i => i.codigo_qr === codigo)

            if (!inscrito) {
                setAlerta('error', 'No está inscrito en esta sesión')
                return
            }

            if (inscrito.asistencia === true) {
                setAlerta('warning', 'Este participante ya fue confirmado')
                return
            }

            // Mutar localmente — no hay request al backend hasta el "Enviar" final
            inscrito.asistencia = true
            codigosYaVistos.value.add(codigo)
            setAlerta('success', `${inscrito.nombre} agregado a la lista`)

            // Verificar aforo (sesiones cerradas con cupo definido)
            if (sesionActiva.value?.cupo_maximo) {
                const confirmados = listaInscritos.value.filter(i => i.asistencia === true).length
                if (confirmados >= sesionActiva.value.cupo_maximo) {
                    setAforoLleno(true)
                }
            }
        } else {
            // Sesión abierta: lookup local primero
            if (listaInscritos.value.find(i => i.codigo_qr === codigo)) {
                setAlerta('warning', 'Este participante ya fue agregado')
                return
            }

            // Lookup dinámico contra el backend
            await fetchQrLookup(codigo)
        }
    }

    // -------------------------------------------------------------------------
    // Lookup dinámico — solo sesiones ABIERTAS
    // Resuelve un QR desconocido contra codigos_qr / pases_diarios y lo
    // inyecta en listaInscritos con asistencia=true.
    // -------------------------------------------------------------------------
    async function fetchQrLookup(codigo) {
        if (!sesionActivaId.value) return

        lookupLoading.value  = true
        codigoEnLookup.value = codigo

        try {
            const res = await api.get(
                `instructor/sesiones/${sesionActivaId.value}/qr-lookup/${codigo}`
            )

            if (res.data?.encontrado) {
                listaInscritos.value.push({
                    id_usuario:   res.data.id_usuario,
                    tipo_usuario: res.data.tipo_usuario,
                    nombre:       res.data.nombre,
                    codigo_qr:    res.data.codigo_qr,
                    asistencia:   true,
                })
                codigosYaVistos.value.add(codigo)
                setAlerta('success', `${res.data.nombre} agregado a la lista`)
            } else {
                setAlerta('error', 'Código no registrado en el sistema')
            }
        } catch (err) {
            const status  = err.response?.status
            const message = err.response?.data?.message

            if (status === 404) {
                setAlerta('error', message || 'Código no registrado o expirado')
            } else if (status === 422) {
                setAlerta('error', 'Formato de código QR inválido')
            } else {
                setAlerta('error', 'No se pudo verificar el código')
            }
        } finally {
            lookupLoading.value  = false
            codigoEnLookup.value = null
        }
    }

    // -------------------------------------------------------------------------
    // Reservas / Torneos — registro individual e inmediato
    // (Clases no usa este flujo: ver enviarListaFinal)
    // -------------------------------------------------------------------------
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
            // Backend devuelve: [{ id_inscripcion, id_usuario, tipo_usuario, nombre, codigo_qr, asistencia: bool }]
            listaInscritos.value = res.data?.data ?? []

            // Reconstruir Set de códigos ya vistos desde los confirmados
            codigosYaVistos.value = new Set(
                listaInscritos.value
                    .filter(i => i.asistencia === true)
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

    // -------------------------------------------------------------------------
    // POST consolidado final — envía todos los confirmados de una sola vez.
    // Punto de entrada desde la vista CONFIRMACION_PREVIA.
    // -------------------------------------------------------------------------
    async function enviarListaFinal() {
        if (!sesionActivaId.value) return

        const confirmados = listaInscritos.value.filter(i => i.asistencia === true)
        if (confirmados.length === 0) return

        loading.value = true
        error.value   = null

        try {
            const response = await api.post(
                `instructor/sesiones/${sesionActivaId.value}/confirmar-asistencia`,
                {
                    confirmados: confirmados.map(c => ({
                        id_usuario:   c.id_usuario,
                        tipo_usuario: c.tipo_usuario,
                        codigo_qr:    c.codigo_qr,
                    })),
                    metodo: metodoIngreso.value === 'CAMARA' ? 'ESCANER_QR' : 'INGRESO_MANUAL',
                }
            )

            listaConfirmada.value = true

            resultados.value = [{
                success:           true,
                tipo_actividad:    'sesion',
                message:           response.data?.message ?? 'Asistencia confirmada',
                data: {
                    total_confirmados: response.data?.total_registrados ?? confirmados.length,
                },
            }]
            paso.value = 'OUTPUT'

        } catch (err) {
            const status   = err.response?.status
            const data     = err.response?.data ?? {}
            const message  = data.message ?? ''

            // 207 — registro parcial: algunos QR fallaron
            if (status === 207 && data.errores_parciales) {
                listaConfirmada.value = true   // backend marcó lista_asistencia_enviada
                resultados.value = [{
                    success:           false,
                    tipo_actividad:    'sesion',
                    message,
                    total_registrados: data.total_registrados ?? 0,
                    errores_parciales: data.errores_parciales,
                }]
                paso.value = 'OUTPUT'
                return
            }

            // 409 — sesión ya no en curso o lista ya enviada: bloquea el guardado
            if (status === 409) {
                error.value = message || 'La sesión ya no está en curso.'
                resultados.value = [{
                    success:        false,
                    tipo_actividad: 'sesion',
                    message:        error.value,
                    codigo:         data.codigo ?? null,
                }]
                paso.value = 'OUTPUT'
                return
            }

            error.value = message || 'No se pudo confirmar la lista. Intente de nuevo.'
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
        codigosYaVistos,
        totalRegistrados,
        // Lookup dinámico
        lookupLoading, codigoEnLookup,
        // Alerta contextual
        alertaEscaneo,
        // Getters
        esCategoriaClases, esCategoriaReservas, esCategoriaTorneo,
        haySesionesHoy, sesionActiva, esSesionCerrada,
        reservaActiva, encuentroActivo,
        tipoActividadActual, idActividadActual,
        inscritosConfirmados, inscritosNoConfirmados,
        // Navegación
        seleccionarCategoria, seleccionarSesion,
        seleccionarReserva, seleccionarEncuentro,
        seleccionarMetodo, irAtras, resetHub,
        // Acciones QR
        validarFormatoQR: validarFormatoQRLocal,
        procesarCodigo, fetchQrLookup,
        setAlerta, clearAlerta,
        // US-33 acciones
        fetchListaInscritos, setAforoLleno,
        enviarListaFinal,
        // Fetches
        fetchDatosMenu,
    }
})
