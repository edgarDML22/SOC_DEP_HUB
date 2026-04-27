import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";
import { useProfileStore } from "@/stores/profiles/socioStore"; 

export const useReservationStore = defineStore("reservation", () => {
  // --- STATE ---
  const pasoActual = ref("1");
  const espaciosDisponibles = ref([]);
  const horariosDisponibles = ref([]);
  const cargando = ref(false);
  const errorApi = ref(null);
  const errorNavegacion = ref(null);
  const mostrarModalDraft = ref(false);

  const reservaPayload = ref({
    espacioSeleccionado: null,
    disciplinaSeleccionada: null,
    id_espacio: null,
    id_disciplina: null,
    hora_inicio: null,
    hora_fin: null,
    acompanantes: [],
    id_reserva: null, 
  });

  const acompanantesSeleccionados = ref([]);
  const capacidadMaximaEspacio = ref(0);
  
  // ELIMINADO: let debounceTimeout = null; (Ya no necesitamos auto-guardado)

  // --- VARIABLES PARA LISTA LOCAL ---
  const misReservacionesTotales = ref([]);
  const misReservacionesCargadas = ref(false);

  // --- VARIABLES DEL STEP 3 (HORARIOS) ---
  const opcionesHoras = ref([
    "07:00", "08:00", "09:00", "10:00", "11:00", "12:00",
    "13:00", "14:00", "15:00", "16:00", "17:00", "18:00",
    "19:00", "20:00", "21:00", "22:00",
  ]);

  const horaInicioTemp = ref(null);
  const horaFinTemp = ref(null);

  const errorValidacion = computed(() => {
    if (!horaInicioTemp.value || !horaFinTemp.value) return null;

    const numInicio = parseInt(horaInicioTemp.value.split(":")[0]);
    const numFin = parseInt(horaFinTemp.value.split(":")[0]);

    if (numInicio >= numFin) {
      return "La hora de inicio debe ser menor a la hora de fin.";
    }

    if (numFin - numInicio > 2) {
      return "La reserva máxima permitida es de 2 horas.";
    }

    const hayEmpalme = horariosDisponibles.value.some((bloque) => {
      const bloqueInicio = parseInt(bloque.inicio.split(":")[0]);
      const bloqueFin = parseInt(bloque.fin.split(":")[0]);
      return numInicio < bloqueFin && numFin > bloqueInicio;
    });

    if (hayEmpalme) {
      return "El horario seleccionado ya ha sido ocupado. Elija uno nuevo para continuar.";
    }

    return null;
  });

  const esHorarioValidoParaPreview = computed(() => {
    return (
      horaInicioTemp.value &&
      horaFinTemp.value &&
      errorValidacion.value === null
    );
  });

  const validarHorario = () => {
    if (!errorValidacion.value && esHorarioValidoParaPreview.value) {
      seleccionarHorario(horaInicioTemp.value, horaFinTemp.value);
    }
  };

  // --- GETTERS ---
  const disciplinasUnicas = computed(() => {
    if (!espaciosDisponibles.value.length) return [];

    const nombresDisciplinas = espaciosDisponibles.value.flatMap((espacio) =>
      espacio.disciplinas.map((d) => {
        if (d.nombre_disciplina.includes("Futbol")) return "Futbol";
        return d.nombre_disciplina;
      })
    );

    return [...new Set(nombresDisciplinas)].filter((d) => d !== "N/A");
  });

  const espaciosPorDisciplina = computed(() => {
    const seleccion = reservaPayload.value.disciplinaSeleccionada;
    if (!seleccion) return [];

    return espaciosDisponibles.value.filter((espacio) => {
      return espacio.disciplinas.some((d) => d.nombre_disciplina.includes(seleccion));
    });
  });

  const fechaHoy = () => {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, "0");
    const dia = String(hoy.getDate()).padStart(2, "0");
    return `${anio}-${mes}-${dia}`;
  };

  // --- ACTIONS ---
  const fetchDisponibilidadEspacios = async () => {
    if (disciplinasUnicas.value && disciplinasUnicas.value.length > 0) return;

    cargando.value = true;
    errorApi.value = null;
    try {
      const hoy = fechaHoy();
      const res = await api.get("/spaces/availability", {
        params: {
          date: hoy,
          espacio_type: "RESERVA_ON_DEMAND",
        },
      });
      espaciosDisponibles.value = res.data.data;
    } catch (error) {
      console.error("Error al cargar disponibilidad:", error);
      errorApi.value = "No se pudieron cargar las canchas.";
    } finally {
      cargando.value = false;
    }
  };

  const fetchMisReservaciones = async (forceRefresh = false) => {
    if (!forceRefresh && misReservacionesCargadas.value) return;

    cargando.value = true;
    try {
      const res = await api.get('/reservations/my-list', { params: { limit: 20 } });
      if (res.data.success) {
        misReservacionesTotales.value = res.data.data;
        misReservacionesCargadas.value = true;
      }
    } catch (error) {
      console.error("Error fetching reservations list:", error);
    } finally {
      cargando.value = false;
    }
  };

  const fetchHorarioEspacio = async (id_espacio) => {
    cargando.value = true;
    errorApi.value = null;
    try {
      const hoy = fechaHoy();
      const res = await api.get("schedules/availability", {
        params: {
          date: hoy,
          id_espacio: id_espacio,
        },
      });
      horariosDisponibles.value = res.data.data;
    } catch (error) {
      console.error("Error al cargar horarios:", error);
      errorApi.value = "No se pudieron cargar los horarios del espacio elegido";
    } finally {
      cargando.value = false;
    }
  };

  const buscarReservaActiva = async () => {
    try {
      const res = await api.get('/reservations/draft/active');

      if (res.data.success && res.data.reserva) {
        const r = res.data.reserva;
        const horaInicioLimpia = r.hora_inicio.substring(0, 5); 
        const horaFinLimpia = r.hora_fin.substring(0, 5);

        if (typeof r.acompanantes_draft === 'string') {
          try {
            acompanantesSeleccionados.value = JSON.parse(r.acompanantes_draft);
          } catch (e) {
            acompanantesSeleccionados.value = [];
          }
        } else {
          acompanantesSeleccionados.value = r.acompanantes_draft || [];
        }

        reservaPayload.value = {
          id_disciplina: r.id_disciplina,
          disciplinaSeleccionada: r.disciplina?.nombre_disciplina || "Deporte",
          id_espacio: r.id_espacio,
          espacioSeleccionado: r.espacio_fisico?.nombre_espacio || "Espacio",
          hora_inicio: r.hora_inicio,
          hora_fin: r.hora_fin,
          id_reserva: r.id_reserva,
          acompanantes: [],
        };

        horaInicioTemp.value = horaInicioLimpia;
        horaFinTemp.value = horaFinLimpia;
        fetchHorarioEspacio(r.id_espacio);
        return true;
      }
    } catch (e) {
      console.log("No hay borradores activos para este usuario.");
    }
    return false;
  };

  const descartarBorrador = async (id_reserva_param) => {
    mostrarModalDraft.value = false;
    const idAUsar = id_reserva_param || reservaPayload.value.id_reserva;
    if (idAUsar) {
      try {
        await api.post("/reservations/discard", { id_reserva: idAUsar });
      } catch (e) {
        console.error("Error al descartar el borrador");
      }
    }
    misReservacionesCargadas.value = false; 
    resetearReserva();
    fetchDisponibilidadEspacios();
  };

  const cancelarReservacion = async (id_reserva) => {
    cargando.value = true;
    try {
      const res = await api.post("/reservations/cancel", { id_reserva });
      if (res.data.success) {
        const idx = misReservacionesTotales.value.findIndex(r => r.id_reserva === id_reserva);
        if (idx !== -1) {
          misReservacionesTotales.value[idx].estatus_operativo = res.data.nuevo_estatus;
        }
        return { success: true, nuevo_estatus: res.data.nuevo_estatus, message: res.data.message };
      }
    } catch (e) {
      const msg = e.response?.data?.message || "Error al cancelar la reservación.";
      return { success: false, error: msg };
    } finally {
      cargando.value = false;
    }
  };

  const seleccionarDisciplina = (disciplina) => {
    reservaPayload.value.disciplinaSeleccionada = disciplina;
    reservaPayload.value.id_espacio = null;
    reservaPayload.value.espacioSeleccionado = null;
    horaInicioTemp.value = null;
    horaFinTemp.value = null;
    errorNavegacion.value = null;
    pasoActual.value = "2";
  };

  const seleccionarEspacio = (id_espacio) => {
    const canchaSeleccionada = espaciosDisponibles.value.find(e => e.id_espacio === id_espacio);

    if (canchaSeleccionada) {
      const disciplinaExacta = canchaSeleccionada.disciplinas.find(d =>
        d.nombre_disciplina.includes(reservaPayload.value.disciplinaSeleccionada)
      );
      reservaPayload.value.id_disciplina = disciplinaExacta ? disciplinaExacta.id_disciplina : null;
      reservaPayload.value.espacioSeleccionado = canchaSeleccionada.nombre_espacio;
      capacidadMaximaEspacio.value = canchaSeleccionada.capacidad_maxima || 0;
    }

    fetchHorarioEspacio(id_espacio);
    reservaPayload.value.id_espacio = id_espacio;
    errorNavegacion.value = null;
    pasoActual.value = "3";
  };

  const seleccionarHorario = async (hora_inicio, hora_fin) => {
    const profileStore = useProfileStore();

    if (!profileStore.idSocio) {
      await profileStore.fetchProfile();
    }

    if (!profileStore.idSocio) {
      errorNavegacion.value = "No se pudo identificar al socio. Por favor, reintenta iniciar sesión.";
      return;
    }

    reservaPayload.value.hora_inicio = hora_inicio;
    reservaPayload.value.hora_fin = hora_fin;
    errorNavegacion.value = null;
    cargando.value = true;
    errorApi.value = null;

    try {
      const payloadBackend = {
        id_espacio: reservaPayload.value.id_espacio,
        id_disciplina: reservaPayload.value.id_disciplina,
        fecha_reserva: fechaHoy(),
        hora_inicio: hora_inicio,
        hora_fin: hora_fin,
      };

      const res = await api.post("/reservations", payloadBackend);

      if (res.data.success) {
        reservaPayload.value.id_reserva = res.data.id_reserva;
        misReservacionesCargadas.value = false; 
        pasoActual.value = "4";
      }
    } catch (error) {
      console.error("Error al confirmar horario:", error);
      if (error.response?.data?.message) {
        errorNavegacion.value = error.response.data.message;
      }
    } finally {
      cargando.value = false;
    }
  };

  const volverADisciplinas = () => {
    reservaPayload.value.disciplinaSeleccionada = null;
    pasoActual.value = "1";
  };

  const volverAEspacios = () => {
    reservaPayload.value.id_espacio = null;
    pasoActual.value = "2";
  };

  const volverAHorarios = () => {
    reservaPayload.value.hora_inicio = null;
    reservaPayload.value.hora_fin = null;
    pasoActual.value = "3";
  };

  const intentarCambioPaso = (nuevoPaso) => {
    const destino = parseInt(nuevoPaso);
    const actual = parseInt(pasoActual.value);

    if (destino < actual) {
      errorNavegacion.value = null;
      pasoActual.value = nuevoPaso;
      return;
    }

    if (destino >= 2 && !reservaPayload.value.disciplinaSeleccionada) {
      errorNavegacion.value = "Primero debes elegir un deporte para continuar.";
      return;
    }

    if (destino >= 3 && !reservaPayload.value.id_espacio) {
      errorNavegacion.value = "Primero debes seleccionar una cancha disponible";
      return;
    }

    if (destino >= 4 && (!horaInicioTemp.value || !horaFinTemp.value || errorValidacion.value)) {
      errorNavegacion.value = "Primero debes elegir y confirmar un horario válido.";
      return;
    }

    errorNavegacion.value = null;
    pasoActual.value = nuevoPaso;
  };

  // ----------------------------------------------------------------------
  // NUEVO: LA FUNCIÓN SE EJECUTA SÓLO CUANDO SE DA CLIC EN EL BOTÓN
  // ----------------------------------------------------------------------
  const confirmarAcompanantes = async () => {
    const id_reserva = reservaPayload.value.id_reserva;
    if (!id_reserva) return; 

    cargando.value = true;
    errorNavegacion.value = null;

    try {
      await api.put(`/reservations/${id_reserva}/draft/acompanantes`, {
        acompanantes: acompanantesSeleccionados.value
      });
      
      // Si sale bien, avanzamos al resumen (Paso 5)
      pasoActual.value = "5";
    } catch (error) {
      console.error("Error al sincronizar acompañantes del borrador:", error);
      // Aquí capturamos si el borrador expiró (EJ: Error 400 "La reservación ya no es un borrador...")
      errorNavegacion.value = error.response?.data?.message || "Tu reservación caducó por inactividad. Por favor inicia de nuevo.";
      
      // Si ya expiró, reseteamos la reserva local para obligar al usuario a empezar otra vez
      if (error.response?.status === 400 || error.response?.status === 404) {
          setTimeout(() => {
              resetearReserva();
          }, 3500); // Le damos 3.5 segundos para leer el error y lo sacamos.
      }
    } finally {
      cargando.value = false;
    }
  };

  // MODIFICADO: Ya no hace llamada a la API, solo manipula la lista en Vue (JS).
  const toggleAcompanante = (acompanante) => {
    const maxPermitidos = capacidadMaximaEspacio.value - 1; 

    const index = acompanantesSeleccionados.value.findIndex(a =>
      a.id === acompanante.id && a.tipo === acompanante.tipo
    );

    if (index !== -1) {
      acompanantesSeleccionados.value.splice(index, 1);
    } else {
      if (acompanantesSeleccionados.value.length >= maxPermitidos) {
        return false; 
      }
      acompanantesSeleccionados.value.push(acompanante);
    }

    return true; 
  };

  const confirmarReserva = async () => {
    cargando.value = true;
    try {
      const res = await api.post("/reservations/confirm", {
        id_reserva: reservaPayload.value.id_reserva,
        acompanantes: acompanantesSeleccionados.value
      });

      misReservacionesCargadas.value = false; 

      return {
        success: true,
        data: res.data,
        message: "¡Reservación confirmada exitosamente! Se ha enviado un correo con los detalles."
      };
    } catch (error) {
      console.error("Error al confirmar reserva:", error);
      const msg = error.response?.data?.message || "Error al confirmar.";
      return { success: false, error: msg };
    } finally {
      cargando.value = false;
    }
  };

  const resetearReserva = () => {
    pasoActual.value = "1";
    reservaPayload.value = {
      id_disciplina: null,
      disciplinaSeleccionada: null,
      id_espacio: null,
      espacioSeleccionado: null,
      hora_inicio: null,
      hora_fin: null,
      id_reserva: null,
      acompanantes: [],
    };

    horaInicioTemp.value = null;
    horaFinTemp.value = null;
    errorNavegacion.value = null;
    horariosDisponibles.value = null;
    acompanantesSeleccionados.value = [];
    capacidadMaximaEspacio.value = 0;
  };

  const obtenerIconoName = (disciplina) => {
    const mapaIconos = {
      Tenis: "IconTenis",
      Padel: "IconPadel",
      Squash: "IconSquash",
      "Futbol Adultos": "IconFutbol",
      "Futbol Infantil": "IconFutbol",
      Futbol: "IconFutbol",
      Basquetbol: "IconBasquetbol",
      Voleibol: "IconVoleibol",
      Frontenis: "IconFrontenis",
      Fronton: "IconFrontenis",
    };

    return mapaIconos[disciplina] || "DefaultIcon";
  };

  return {
    pasoActual,
    espaciosDisponibles,
    horariosDisponibles,
    cargando,
    errorApi,
    reservaPayload,
    disciplinasUnicas,
    acompanantesSeleccionados,
    capacidadMaximaEspacio,
    opcionesHoras,
    horaInicioTemp,
    horaFinTemp,
    esHorarioValidoParaPreview,
    validarHorario,
    errorValidacion,
    errorNavegacion,
    intentarCambioPaso,
    misReservacionesTotales,
    misReservacionesCargadas,
    fetchMisReservaciones,
    fetchDisponibilidadEspacios,
    seleccionarDisciplina,
    resetearReserva,
    obtenerIconoName,
    espaciosPorDisciplina,
    volverADisciplinas,
    volverAEspacios,
    volverAHorarios,
    seleccionarEspacio,
    seleccionarHorario,
    buscarReservaActiva,
    descartarBorrador,
    cancelarReservacion,
    toggleAcompanante,
    confirmarReserva,
    confirmarAcompanantes, 
    mostrarModalDraft
  };
});