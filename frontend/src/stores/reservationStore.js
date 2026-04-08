import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";
import { useProfileStore } from "@/stores/profileStore"; // <--- ASEGÚRATE DE TENER ESTA LÍNEA AQUÍ TAMBIÉN

export const useReservationStore = defineStore("reservation", () => {
  // --- STATE ---
  const pasoActual = ref("1");
  const espaciosDisponibles = ref([]);
  const horariosDisponibles = ref([]);
  const cargando = ref(false);
  const errorApi = ref(null);
  const errorNavegacion = ref(null);

  const reservaPayload = ref({
    espacioSeleccionado: null,
    disciplinaSeleccionada: null,
    id_espacio: null,
    id_disciplina: null,
    hora_inicio: null,
    hora_fin: null,
    acompanantes: [],
    id_reserva: null, // <-- 2. NUEVO ESPACIO PARA GUARDAR EL ID
  });

  // --- VARIABLES DEL STEP 3 (HORARIOS) ---
  // Solo agrégale el ref( y el ) al final
  const opcionesHoras = ref([
    "07:00",
    "08:00",
    "09:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
    "18:00",
    "19:00",
    "20:00",
    "21:00",
    "22:00",
  ]);

  const horaInicioTemp = ref(null);
  const horaFinTemp = ref(null);

  // 1. ELIMINA "const errorValidacion = ref(null);" (si la tienes arriba)
  // 2. CREA la validación de forma REACTIVA (computed):
  const errorValidacion = computed(() => {
    // Si aún no elige ambas horas, no mostramos error
    if (!horaInicioTemp.value || !horaFinTemp.value) return null;

    const numInicio = parseInt(horaInicioTemp.value.split(":")[0]);
    const numFin = parseInt(horaFinTemp.value.split(":")[0]);

    // A) Validar inicio < fin
    if (numInicio >= numFin) {
      return "La hora de inicio debe ser menor a la hora de fin.";
    }

    // B) Validar máximo 2 horas
    if (numFin - numInicio > 2) {
      return "La reserva máxima permitida es de 2 horas.";
    }

    // C) Validar empalmes con el backend en tiempo real
    const hayEmpalme = horariosDisponibles.value.some((bloque) => {
      const bloqueInicio = parseInt(bloque.inicio.split(":")[0]);
      const bloqueFin = parseInt(bloque.fin.split(":")[0]);
      // Fórmula para detectar intersección de rangos de tiempo
      return numInicio < bloqueFin && numFin > bloqueInicio;
    });

    if (hayEmpalme) {
      return "El horario seleccionado ya ha sido ocupado. Elija uno nuevo para continuar.";
    }

    // Todo está perfecto
    return null;
  });

  // 3. ACTUALIZAR la variable del Preview y del Botón
  const esHorarioValidoParaPreview = computed(() => {
    // El horario es válido SOLO si tiene horas asignadas y NO hay ningún error
    return (
      horaInicioTemp.value &&
      horaFinTemp.value &&
      errorValidacion.value === null
    );
  });

  // 4. SIMPLIFICAR tu acción, ya que el 'computed' hace todo el trabajo pesado
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
      // Buscamos si la cancha tiene al menos una disciplina que coincida
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
      console.log(horariosDisponibles.value);
    } catch (error) {
      console.error("Error al cargar horarios:", error);
      errorApi.value = "No se pudieron cargar los horarios del espacio elegido";
    } finally {
      cargando.value = false;
    }
  };

  // MANEJAR RESERVAS PENDIENTES (ACTIVE DRAFTS)

  const buscarReservaActiva = async () => {
    // Ya no necesitamos validar if(!profileStore.idSocio) porque 
    // Laravel sabrá quiénes somos gracias a la cookie/token de sesión.


    // -- Se busca su última reserva que dejó como PENDIENTE
    try {
      const res = await api.get('/reservations/draft/active');

      if (res.data.success && res.data.reserva) {
        const r = res.data.reserva;

        const horaInicioLimpia = r.hora_inicio.substring(0, 5); // Pasa de "11:00:00" a "11:00"
        const horaFinLimpia = r.hora_fin.substring(0, 5);

        //En automatico se guardan los datos en reservaPayload

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

  // -- FLUJO A: Cuando el usuario decide continuar con su reserva
  // Los datos que llevaba se guardan en reservaPayload para que pueda continuar


  // -- FLUJO B: Cuando el usuario descarta su reserva PENDIENTE
  // Es devuelto al principio de la reserva
  const descartarBorrador = async () => {
    if (reservaPayload.value.id_reserva) {
      try {
        // Le avisamos al backend que la cancele para liberar la cancha
        await api.post("/reservations/cancel", {
          id_reserva: reservaPayload.value.id_reserva,
        });
      } catch (e) {
        console.error("Error al cancelar el borrador");
      }
    }
    resetearReserva();
    fetchDisponibilidadEspacios(); // Cargamos las canchas normales
  };

  // GO FOWARD
  // --- 3. LIMPIAR EL ERROR CUANDO EL USUARIO HACE LO CORRECTO ---
  // Actualiza tus funciones de selección para que limpien 'errorNavegacion'

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
    // Magia pro: Buscar la disciplina exacta dentro de la cancha seleccionada
    const canchaSeleccionada = espaciosDisponibles.value.find(e => e.id_espacio === id_espacio);
    
    
    if (canchaSeleccionada) {
      
        const disciplinaExacta = canchaSeleccionada.disciplinas.find(d => 
            d.nombre_disciplina.includes(reservaPayload.value.disciplinaSeleccionada)
        );
        // Guardamos el ID real en el payload
        reservaPayload.value.id_disciplina = disciplinaExacta ? disciplinaExacta.id_disciplina : null;
        reservaPayload.value.espacioSeleccionado = canchaSeleccionada.nombre_espacio;

      }

    fetchHorarioEspacio(id_espacio);
    reservaPayload.value.id_espacio = id_espacio;
    
    errorNavegacion.value = null; 
    pasoActual.value = "3";
  };

  // reservationStore.js

  const seleccionarHorario = async (hora_inicio, hora_fin) => {
    const profileStore = useProfileStore();

    // 1. EL GUARDIÁN: Si el ID es null, esperamos a que el perfil se cargue
    if (!profileStore.idSocio) {
      await profileStore.fetchProfile();
    }

    // Si después de intentar cargar sigue sin haber ID (ej. sesión expirada), cancelamos
    if (!profileStore.idSocio) {
      errorNavegacion.value =
        "No se pudo identificar al socio. Por favor, reintenta iniciar sesión.";
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

      // Usamos la ruta en inglés como la definiste en api.php
      const res = await api.post("/reservations", payloadBackend);

      if (res.data.success) {
        reservaPayload.value.id_reserva = res.data.id_reserva;
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

  // GO BACK
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

  // --- 2. EL GUARDIÁN DE RUTAS (Sin animaciones que desaparecen) ---
  const intentarCambioPaso = (nuevoPaso) => {
    const destino = parseInt(nuevoPaso);
    const actual = parseInt(pasoActual.value);

    // Si va hacia atrás, limpiamos errores y lo dejamos pasar
    if (destino < actual) {
      errorNavegacion.value = null;
      pasoActual.value = nuevoPaso;
      return;
    }

    // Reglas de validación
    if (destino >= 2 && !reservaPayload.value.disciplinaSeleccionada) {
      errorNavegacion.value = "Primero debes elegir un deporte para continuar.";
      return;
    }

    if (destino >= 3 && !reservaPayload.value.id_espacio) {
      errorNavegacion.value = "Primero debes seleccionar una cancha disponible";
      return;
    }

    if (
      destino >= 4 &&
      (!horaInicioTemp.value || !horaFinTemp.value || errorValidacion.value)
    ) {
      errorNavegacion.value =
        "Primero debes elegir y confirmar un horario válido.";
      return;
    }

    // Si todo está bien, limpiamos el error y avanzamos
    errorNavegacion.value = null;
    pasoActual.value = nuevoPaso;
  };

  // Cancelar reserva

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

    horaInicioTemp.value = null
    horaFinTemp.value = null
    errorNavegacion.value = null
    horariosDisponibles.value = null
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
    // --- NUEVAS VARIABLES DEL STEP 3 ---
    opcionesHoras,
    horaInicioTemp,
    horaFinTemp,
    esHorarioValidoParaPreview,
    validarHorario,
    errorValidacion,
    errorNavegacion,
    intentarCambioPaso,
    // -----------------------------------
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
  };
});
