import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";

/**
 * scheduleStore — Pinia store dedicado para la vista de calendarización de torneos.
 *
 * Gestiona:
 *  - Lista de espacios físicos (cacheada)
 *  - Encuentros del torneo seleccionado
 *  - Pool de árbitros con disponibilidad
 *  - Lista ligera de todos los torneos (vista general)
 */
export const useScheduleStore = defineStore("schedule", () => {
    // ── STATE ────────────────────────────────────────────────────
    const espacios = ref([]);
    const encuentros = ref([]);
    const arbitrosTotales = ref([]);  // Todos los árbitros del torneo
    const arbitrosPool = ref({ disponibles: [], ocupados: [] });  // Filtrando por horario
    const torneoSeleccionado = ref(null);
    const todosLosTorneos = ref([]);
    const fechaActiva = ref(new Date());

    const loadingStates = ref({
        espacios: false,
        encuentros: false,
        arbitros: false,
        torneos: false,
        asignando: false,
    });

    const error = ref(null);

    // ── PALETA DE COLORES PARA TORNEOS ──────────────────────────
    const TOURNAMENT_COLORS = [
        { bg: '#3b82f6', gradient: ['#3b82f6', '#1d4ed8'], text: '#fff' },  // Blue
        { bg: '#8b5cf6', gradient: ['#8b5cf6', '#6d28d9'], text: '#fff' },  // Purple
        { bg: '#ec4899', gradient: ['#ec4899', '#be185d'], text: '#fff' },  // Pink
        { bg: '#f59e0b', gradient: ['#f59e0b', '#d97706'], text: '#fff' },  // Amber
        { bg: '#10b981', gradient: ['#10b981', '#059669'], text: '#fff' },  // Emerald
        { bg: '#06b6d4', gradient: ['#06b6d4', '#0891b2'], text: '#fff' },  // Cyan
        { bg: '#f43f5e', gradient: ['#f43f5e', '#e11d48'], text: '#fff' },  // Rose
        { bg: '#6366f1', gradient: ['#6366f1', '#4f46e5'], text: '#fff' },  // Indigo
    ];

    const UNASSIGNED_COLOR = { bg: '#94a3b8', gradient: ['#94a3b8', '#64748b'], text: '#fff' };

    const getTournamentColor = (idTorneo) => {
        if (!idTorneo) return UNASSIGNED_COLOR;
        return TOURNAMENT_COLORS[idTorneo % TOURNAMENT_COLORS.length];
    };

    // ── GETTERS ─────────────────────────────────────────────────
    const espaciosActivos = computed(() =>
        espacios.value.filter(e => {
            const status = (e.estatus || e.data?.estatus || '').toString().toUpperCase().trim();
            return status === 'ACTIVO';
        })
    );

    const isLoading = computed(() =>
        Object.values(loadingStates.value).some(v => v)
    );

    // Convierte los encuentros a eventos de FullCalendar
    const calendarEvents = computed(() => {
        return encuentros.value
            .filter(e => !e.es_bye)
            .map(enc => {
                const isAssigned = !!enc.fecha_hora_inicio;
                const color = isAssigned
                    ? getTournamentColor(enc.id_torneo)
                    : UNASSIGNED_COLOR;

                const comp1 = enc.competidor1?.equipo?.nombre_equipo
                    || enc.competidor1?.participante?.nombre_equipo
                    || enc.competidor1?.participante?.nombre_completo
                    || enc.competidor1?.participante?.nombre
                    || enc.competidor1?.nombre_completo
                    || (enc.competidor1?.id_interno ? `Participante #${enc.competidor1.id_interno}` : 'TBD');
                const comp2 = enc.competidor2?.equipo?.nombre_equipo
                    || enc.competidor2?.participante?.nombre_equipo
                    || enc.competidor2?.participante?.nombre_completo
                    || enc.competidor2?.participante?.nombre
                    || enc.competidor2?.nombre_completo
                    || (enc.competidor2?.id_interno ? `Participante #${enc.competidor2.id_interno}` : 'TBD');

                return {
                    id: String(enc.id_encuentro),
                    title: `${comp1} vs ${comp2}`,
                    start: enc.fecha_hora_inicio ? enc.fecha_hora_inicio.replace(' ', 'T') : null,
                    end: enc.fecha_hora_fin ? enc.fecha_hora_fin.replace(' ', 'T') : null,
                    resourceId: enc.id_espacio ? String(enc.id_espacio) : 'sin-asignar',
                    backgroundColor: color.bg,
                    borderColor: isAssigned ? color.gradient[1] : 'transparent',
                    textColor: color.text,
                    classNames: isAssigned ? [] : ['event-unassigned'],
                    extendedProps: {
                        ...enc,
                        isAssigned,
                        comp1Name: comp1,
                        comp2Name: comp2,
                        faseBracket: enc.fase_bracket || enc.fase || 'N/A',
                    },
                };
            });
    });

    // Eventos de todos los torneos para la vista general
    const allTournamentsEvents = computed(() => {
        const events = [];
        todosLosTorneos.value.forEach(torneo => {
            const encuentrosTorneo = torneo._encuentros || [];
            const color = getTournamentColor(torneo.id_torneo);

            encuentrosTorneo
                .filter(e => !e.es_bye && e.fecha_hora_inicio)
                .forEach(enc => {
                    const comp1 = enc.competidor1?.equipo?.nombre_equipo
                        || enc.competidor1?.participante?.nombre_equipo
                        || enc.competidor1?.participante?.nombre_completo
                        || enc.competidor1?.participante?.nombre
                        || enc.competidor1?.nombre_completo
                        || (enc.competidor1?.id_interno ? `Participante #${enc.competidor1.id_interno}` : 'TBD');
                    const comp2 = enc.competidor2?.equipo?.nombre_equipo
                        || enc.competidor2?.participante?.nombre_equipo
                        || enc.competidor2?.participante?.nombre_completo
                        || enc.competidor2?.participante?.nombre
                        || enc.competidor2?.nombre_completo
                        || (enc.competidor2?.id_interno ? `Participante #${enc.competidor2.id_interno}` : 'TBD');

                    events.push({
                        id: `${torneo.id_torneo}-${enc.id_encuentro}`,
                        title: `${comp1} vs ${comp2}`,
                        start: enc.fecha_hora_inicio ? enc.fecha_hora_inicio.replace(' ', 'T') : null,
                        end: enc.fecha_hora_fin ? enc.fecha_hora_fin.replace(' ', 'T') : null,
                        backgroundColor: color.bg,
                        borderColor: color.gradient[1],
                        textColor: color.text,
                        extendedProps: {
                            id_torneo: torneo.id_torneo,
                            nombre_torneo: torneo.nombre_torneo,
                            id_encuentro: enc.id_encuentro,
                            comp1Name: comp1,
                            comp2Name: comp2,
                        },
                    });
                });
        });
        return events;
    });

    // ── ACTIONS ──────────────────────────────────────────────────

    /**
     * Obtiene todos los espacios físicos (cacheado).
     */
    const fetchEspacios = async () => {
        if (espacios.value.length > 0) return;
        loadingStates.value.espacios = true;
        try {
            const res = await api.get("/spaces/all");
            const data = res.data?.data ?? res.data;
            espacios.value = Array.isArray(data) ? data : [];
        } catch (err) {
            console.error("Error fetching espacios:", err);
            error.value = "Error al cargar los espacios físicos.";
        } finally {
            loadingStates.value.espacios = false;
        }
    };

    /**
     * Obtiene los encuentros de un torneo específico.
     */
    const fetchEncuentrosTorneo = async (idTorneo) => {
        loadingStates.value.encuentros = true;
        error.value = null;
        try {
            const res = await api.get(`/torneos/${idTorneo}`);
            const data = res.data?.data ?? res.data;

            torneoSeleccionado.value = {
                id_torneo: data.id_torneo || data.id,
                nombre_torneo: data.nombre_torneo,
                disciplina: data.disciplina,
                id_disciplina: data.id_disciplina,
                categoria: data.categoria,
                estado: data.estado || data.estatus_torneo,
                fecha_inicio: data.fecha_inicio,
                fecha_fin: data.fecha_fin,
            };

            // Extraer encuentros del bracket (puede venir agrupado o plano)
            const bracket = data.bracket || {};
            if (Array.isArray(bracket)) {
                encuentros.value = bracket.map(e => ({ ...e, id_torneo: idTorneo }));
            } else {
                // Bracket agrupado por fase → aplanar
                const flat = [];
                Object.entries(bracket).forEach(([fase, matches]) => {
                    if (Array.isArray(matches)) {
                        matches.forEach(m => flat.push({ ...m, fase_bracket: fase, id_torneo: idTorneo }));
                    }
                });
                encuentros.value = flat;
            }
        } catch (err) {
            console.error(`Error fetching encuentros torneo ${idTorneo}:`, err);
            error.value = err.response?.data?.message || "Error al cargar el torneo.";
            encuentros.value = [];
            torneoSeleccionado.value = null;
        } finally {
            loadingStates.value.encuentros = false;
        }
    };

    /**
     * Obtiene TODOS los árbitros/instructores designados para un torneo (sin filtro horario).
     */
    const fetchArbitrosTorneo = async (idTorneo) => {
        loadingStates.value.arbitros = true;
        try {
            const res = await api.get(`/torneos/${idTorneo}/referees`);
            arbitrosTotales.value = res.data?.arbitros || [];
            // Mostrar todos como disponibles por defecto (sin horario seleccionado)
            arbitrosPool.value = {
                disponibles: arbitrosTotales.value.map(a => ({ ...a, ocupado: false })),
                ocupados: [],
            };
        } catch (err) {
            console.error(`Error fetching árbitros torneo ${idTorneo}:`, err);
            arbitrosTotales.value = [];
            arbitrosPool.value = { disponibles: [], ocupados: [] };
        } finally {
            loadingStates.value.arbitros = false;
        }
    };

    /**
     * Obtiene la disponibilidad de árbitros para un rango horario.
     */
    const fetchArbitrosDisponibles = async (idTorneo, fechaInicio, fechaFin) => {
        loadingStates.value.arbitros = true;
        try {
            const res = await api.get(`/torneos/${idTorneo}/available-referees`, {
                params: {
                    fecha_hora_inicio: fechaInicio,
                    fecha_hora_fin: fechaFin,
                },
            });
            arbitrosPool.value = {
                disponibles: res.data?.disponibles || [],
                ocupados: res.data?.ocupados || [],
            };
        } catch (err) {
            console.error("Error fetching árbitros disponibles:", err);
            arbitrosPool.value = { disponibles: [], ocupados: [] };
        } finally {
            loadingStates.value.arbitros = false;
        }
    };

    /**
     * Asigna espacio, horario y árbitro a un encuentro (con actualización optimista).
     */
    const asignarEncuentro = async (idEncuentro, payload) => {
        loadingStates.value.asignando = true;
        error.value = null;

        // Snapshot para rollback
        const prevEncuentros = [...encuentros.value];

        // Mutación optimista
        const idx = encuentros.value.findIndex(e => e.id_encuentro === idEncuentro);
        if (idx !== -1) {
            encuentros.value[idx] = {
                ...encuentros.value[idx],
                id_arbitro_asignado: payload.id_arbitro,
                id_espacio: payload.id_espacio,
                fecha_hora_inicio: payload.fecha_hora_inicio,
                fecha_hora_fin: payload.fecha_hora_fin,
                estatus_encuentro: 'PENDIENTE',
            };
        }

        try {
            const res = await api.patch(`/encuentros/${idEncuentro}/assign`, payload);
            const updated = res.data?.encuentro;

            // Actualizar con datos reales del servidor
            if (updated && idx !== -1) {
                encuentros.value[idx] = { ...encuentros.value[idx], ...updated };
            }

            return { success: true, data: updated };
        } catch (err) {
            // Rollback
            encuentros.value = prevEncuentros;
            const msg = err.response?.data?.message || "Error al asignar el encuentro.";
            error.value = msg;
            return { success: false, message: msg, status: err.response?.status };
        } finally {
            loadingStates.value.asignando = false;
        }
    };

    /**
     * Obtiene lista ligera de todos los torneos con sus encuentros para la vista general.
     */
    const fetchTodosLosTorneos = async () => {
        loadingStates.value.torneos = true;
        error.value = null;
        try {
            // Obtenemos todos los torneos y sus encuentros en una sola llamada optimizada
            const listRes = await api.get("/torneos", { params: { per_page: 100, with_encuentros: 1 } });
            const listData = listRes.data?.data ?? listRes.data;

            let torneosList = [];
            if (listData && typeof listData === 'object' && Array.isArray(listData.data)) {
                torneosList = listData.data;
            } else if (Array.isArray(listData)) {
                torneosList = listData;
            }

            // Ya no es necesario hacer N+1 llamadas paralelas!
            // Simplemente mapeamos los torneos y sus encuentros.
            todosLosTorneos.value = torneosList.map(torneo => {
                const id = torneo.id_torneo || torneo.id;
                return {
                    ...torneo,
                    id_torneo: id,
                    _encuentros: torneo._encuentros || [],
                };
            });
        } catch (err) {
            console.error("Error fetching todos los torneos:", err);
            error.value = "Error al cargar los torneos.";
        } finally {
            loadingStates.value.torneos = false;
        }
    };

    /**
     * Limpia el estado del torneo seleccionado.
     */
    const clearSelection = () => {
        torneoSeleccionado.value = null;
        encuentros.value = [];
        arbitrosPool.value = { disponibles: [], ocupados: [] };
    };

    return {
        // State
        espacios,
        encuentros,
        arbitrosPool,
        arbitrosTotales,
        torneoSeleccionado,
        todosLosTorneos,
        fechaActiva,
        loadingStates,
        error,

        // Getters
        espaciosActivos,
        isLoading,
        calendarEvents,
        allTournamentsEvents,
        TOURNAMENT_COLORS,
        UNASSIGNED_COLOR,

        // Actions
        getTournamentColor,
        fetchEspacios,
        fetchEncuentrosTorneo,
        fetchArbitrosTorneo,
        fetchArbitrosDisponibles,
        asignarEncuentro,
        fetchTodosLosTorneos,
        clearSelection,
    };
});
