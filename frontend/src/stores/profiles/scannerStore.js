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
    // listaInscritos contiene objetos con la forma:
    //   { id_inscripcion?, id_usuario, tipo_usuario, nombre, codigo_qr, estado_asistencia }
    // estado_asistencia:
    //   'PENDIENTE'         → inscrito que NO ha confirmado (precarga backend, asistencia=false)
    //   'NUEVO_CONFIRMADO'  → confirmado en esta sesión del Hub (debe enviarse al backend)
    //   'YA_REGISTRADO'     → ya tenía asistencia registrada antes de abrir el Hub (NO reenviar)
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

    // ---- Segmentación de la lista por estado_asistencia ---------------------
    const inscritosPendientes = computed(() =>
        listaInscritos.value.filter(i => i.estado_asistencia === 'PENDIENTE')
    )
    const inscritosNuevos = computed(() =>
        listaInscritos.value.filter(i => i.estado_asistencia === 'NUEVO_CONFIRMADO')
    )
    const inscritosHistoricos = computed(() =>
        listaInscritos.value.filter(i => i.estado_asistencia === 'YA_REGISTRADO')
    )

    // Total confirmados visibles en la sesión actual (nuevos + históricos)
    const totalRegistrados = computed(() =>
        inscritosNuevos.value.length + inscritosHistoricos.value.length
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

    async function seleccionarSesion(id) {
        sesionActivaId.value = id

        const sesion = sesionesHoy.value.find(s => s.id_sesion === id)
        if (sesion?.requiere_inscripcion) {
            // Fetch primero: el botón en ListaClases muestra el spinner mientras carga.
            // Solo avanzamos el paso cuando la lista está lista (o falló con lista vacía).
            await fetchListaInscritos(id)
        } else {
            listaInscritos.value  = []
            codigosYaVistos.value = new Set()
        }

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
    // Navegación hacia atrás
    // -------------------------------------------------------------------------
    function irAtras() {
        switch (paso.value) {
            case 'CONFIRMACION_PREVIA':
                paso.value = 'PASE_LISTA'
                break

            case 'PASE_LISTA':
                paso.value = 'SELECCION_METODO'
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
        listaInscritos.value    = []
        listaLoading.value      = false
        aforoLleno.value        = false
        listaConfirmada.value   = false
        codigosYaVistos.value   = new Set()
        lookupLoading.value     = false
        codigoEnLookup.value    = null
        clearAlerta()
    }

    // -------------------------------------------------------------------------
    // Validación QR
    // -------------------------------------------------------------------------
    function validarFormatoQRLocal(codigo) {
        return validarFormatoQR(codigo)
    }

    // -------------------------------------------------------------------------
    // Alerta contextual
    // -------------------------------------------------------------------------
    function setAlerta(tipo, mensaje, ttl = 3500) {
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
    // Procesamiento de código — punto de entrada único
    // -------------------------------------------------------------------------
    async function procesarCodigo(codigo) {
        if (aforoLleno.value) return
        if (lookupLoading.value) return

        const codigoNormalizado = (codigo ?? '').toUpperCase()

        if (!validarFormatoQR(codigoNormalizado)) {
            setAlerta('error', 'Código QR no reconocido')
            return
        }

        codigoEscaneado.value = codigoNormalizado

        if (esCategoriaClases.value) {
            await procesarCodigoClase(codigoNormalizado)
        } else {
            // Deduplicación local solo aplica a reservas/torneo (POST individual)
            if (codigosYaVistos.value.has(codigoNormalizado)) {
                setAlerta('warning', 'Este participante ya fue confirmado')
                return
            }
            await enviarRegistro(codigoNormalizado)
        }
    }

    // ---- Lógica diferenciada para Mis Clases --------------------------------
    //
    // SESIÓN CERRADA: validación 100% local contra la lista preloaded.
    //   - Si el inscrito está en 'YA_REGISTRADO' → alerta histórico
    //   - Si está en 'NUEVO_CONFIRMADO' → alerta ya confirmado en esta sesión
    //   - Si está en 'PENDIENTE' → mutar a 'NUEVO_CONFIRMADO'
    //   - Si no existe → alerta de no inscrito
    //
    // SESIÓN ABIERTA: la lista crece dinámicamente. Lookup local primero;
    // si no está, GET /qr-lookup. Los nuevos entran con 'NUEVO_CONFIRMADO'.
    //
    async function procesarCodigoClase(codigo) {
        if (esSesionCerrada.value) {
            const inscrito = listaInscritos.value.find(i => i.codigo_qr === codigo)

            if (!inscrito) {
                setAlerta('error', 'No está inscrito en esta sesión')
                return
            }

            if (inscrito.estado_asistencia === 'YA_REGISTRADO') {
                setAlerta('warning', 'Participante ya tenía asistencia registrada')
                return
            }

            if (inscrito.estado_asistencia === 'NUEVO_CONFIRMADO') {
                setAlerta('warning', 'Participante ya fue confirmado en esta sesión')
                return
            }

            // PENDIENTE → NUEVO_CONFIRMADO
            inscrito.estado_asistencia = 'NUEVO_CONFIRMADO'
            codigosYaVistos.value.add(codigo)
            setAlerta('success', `${inscrito.nombre} agregado a la lista`)

            // Verificar aforo: nuevos + históricos
            if (sesionActiva.value?.cupo_maximo) {
                const confirmados = listaInscritos.value.filter(
                    i => i.estado_asistencia === 'NUEVO_CONFIRMADO' ||
                         i.estado_asistencia === 'YA_REGISTRADO'
                ).length
                if (confirmados >= sesionActiva.value.cupo_maximo) {
                    setAforoLleno(true)
                }
            }
        } else {
            // Sesión abierta: lookup local primero
            const existente = listaInscritos.value.find(i => i.codigo_qr === codigo)
            if (existente) {
                if (existente.estado_asistencia === 'YA_REGISTRADO') {
                    setAlerta('warning', 'Participante ya tenía asistencia registrada')
                } else {
                    setAlerta('warning', 'Participante ya fue confirmado en esta sesión')
                }
                return
            }

            await fetchQrLookup(codigo)
        }
    }

    // -------------------------------------------------------------------------
    // Lookup dinámico — solo sesiones ABIERTAS
    // Inyecta el participante con estado_asistencia='NUEVO_CONFIRMADO'.
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
                    id_usuario:        res.data.id_usuario,
                    tipo_usuario:      res.data.tipo_usuario,
                    nombre:            res.data.nombre,
                    codigo_qr:         res.data.codigo_qr,
                    foto_perfil:       res.data.foto_perfil,
                    estado_asistencia: 'NUEVO_CONFIRMADO',
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

            if (esCategoriaReservas.value && reservaActivaId.value) {
                const reserva = reservacionesHoy.value.find(r => r.id_reserva === reservaActivaId.value)
                if (reserva) reserva.estatus_operativo = 'COMPLETADA'
            }

            paso.value = 'OUTPUT'
        } catch (err) {
            const status  = err.response?.status
            const message = err.response?.data?.message ?? ''

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
    // Backend devuelve `asistencia: bool`. Lo mapeamos a estado_asistencia:
    //   asistencia=true  → 'YA_REGISTRADO' (no se reenvía)
    //   asistencia=false → 'PENDIENTE'     (candidato a confirmar en esta sesión)
    async function fetchListaInscritos(idSesion) {
        listaLoading.value = true
        try {
            const res = await api.get(`instructor/sesiones/${idSesion}/lista-inscriptos`)
            const raw = res.data?.data ?? []

            listaInscritos.value = raw.map(i => ({
                id_inscripcion: i.id_inscripcion,
                id_usuario:     i.id_usuario,
                tipo_usuario:   i.tipo_usuario,
                nombre:         i.nombre,
                codigo_qr:      i.codigo_qr,
                foto_perfil:    i.foto_perfil,
                estado_asistencia: i.asistencia === true ? 'YA_REGISTRADO' : 'PENDIENTE',
            }))

            // Reconstruir Set de códigos ya vistos desde los históricos
            codigosYaVistos.value = new Set(
                listaInscritos.value
                    .filter(i => i.estado_asistencia === 'YA_REGISTRADO')
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
    // POST consolidado final — envía SOLO los 'NUEVO_CONFIRMADO'.
    // Los 'YA_REGISTRADO' se omiten para evitar duplicados/envíos en cero.
    // -------------------------------------------------------------------------
    async function enviarListaFinal() {
        if (!sesionActivaId.value) return

        const nuevos = inscritosNuevos.value
        if (nuevos.length === 0) return

        loading.value = true
        error.value   = null

        try {
            const response = await api.post(
                `instructor/sesiones/${sesionActivaId.value}/confirmar-asistencia`,
                {
                    confirmados: nuevos.map(c => ({
                        id_usuario:   c.id_usuario,
                        tipo_usuario: c.tipo_usuario,
                        codigo_qr:    c.codigo_qr,
                    })),
                    metodo: metodoIngreso.value === 'CAMARA' ? 'ESCANER_QR' : 'INGRESO_MANUAL',
                }
            )

            // Solo marcar como enviada la primera vez: si ya había históricos el backend
            // ya tiene lista_asistencia_enviada=true, no hace falta volver a indicarlo.
            if (inscritosHistoricos.value.length === 0) {
                listaConfirmada.value = true
            }

            // Migrar los nuevos a 'YA_REGISTRADO' para reflejar el estado real post-POST
            nuevos.forEach(n => { n.estado_asistencia = 'YA_REGISTRADO' })

            resultados.value = [{
                success:           true,
                tipo_actividad:    'sesion',
                message:           response.data?.message ?? 'Asistencia confirmada',
                data: {
                    total_confirmados: response.data?.total_registrados ?? nuevos.length,
                },
            }]
            paso.value = 'OUTPUT'

        } catch (err) {
            const status   = err.response?.status
            const data     = err.response?.data ?? {}
            const message  = data.message ?? ''

            if (status === 207 && data.errores_parciales) {
                if (inscritosHistoricos.value.length === 0) {
                    listaConfirmada.value = true
                }
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
    async function refreshDatosMenu() {
        sesionesHoy.value      = []
        reservacionesHoy.value = []
        encuentrosHoy.value    = []
        await fetchDatosMenu()
    }

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
        inscritosPendientes, inscritosNuevos, inscritosHistoricos,
        // Navegación
        seleccionarCategoria, seleccionarSesion,
        seleccionarReserva, seleccionarEncuentro,
        seleccionarMetodo, irAtras, resetHub, refreshDatosMenu,
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
