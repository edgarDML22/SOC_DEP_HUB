import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";

export const useReservationStore = defineStore("reservation", () => {
  // --- STATE ---
  const pasoActual = ref("1");
  const espaciosDisponibles = ref([]);
  const horariosDisponibles = ref([]);
  const cargando = ref(false);
  const errorApi = ref(null);
  const errorValidacion = ref(null);

  const reservaPayload = ref({
    disciplinaSeleccionada: null,
    id_espacio: null,
    hora_inicio: null,
    hora_fin: null,
    acompanantes: [],
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

  // Computada para saber si podemos previsualizar (y si el botón se habilita)
  const esHorarioValidoParaPreview = computed(() => {
    if (!horaInicioTemp.value || !horaFinTemp.value) return false;
    return horaInicioTemp.value < horaFinTemp.value;
  });

  // --- ACCIÓN DE VALIDACIÓN ---
  const validarHorario = () => {
    // 0. Limpiamos cualquier error previo
    errorValidacion.value = null;

    // 1. Validar inicio < fin
    if (!esHorarioValidoParaPreview.value) {
      errorValidacion.value =
        "La hora de inicio debe ser menor a la hora de fin.";
      return;
    }

    // 2. Validar máximo 2 horas
    const numInicio = parseInt(horaInicioTemp.value.split(":")[0]);
    const numFin = parseInt(horaFinTemp.value.split(":")[0]);
    if (numFin - numInicio > 2) {
      errorValidacion.value = "La reserva máxima permitida es de 2 horas.";
      return;
    }

    // 3. Validar empalmes con el backend
    const hayEmpalme = horariosDisponibles.value.some((bloque) => {
      const bloqueInicio = parseInt(bloque.inicio.split(":")[0]);
      const bloqueFin = parseInt(bloque.fin.split(":")[0]);
      return numInicio < bloqueFin && numFin > bloqueInicio;
    });

    if (hayEmpalme) {
      errorValidacion.value =
        "El horario choca con otra actividad. Por favor, elige otro.";
    } else {
      // Todo en orden, pasamos al Step 4
      seleccionarHorario(horaInicioTemp.value, horaFinTemp.value);
    }
  };

  // --- GETTERS ---
  const disciplinasUnicas = computed(() => {
    if (!espaciosDisponibles.value.length) return [];

    // Disciplinas
    const todasLasDisciplinas = espaciosDisponibles.value.flatMap(
      (espacio) => espacio.disciplinas,
    );

    const disciplinasAgrupadas = todasLasDisciplinas.map((disciplina) => {
      if (disciplina === "Futbol Adultos" || disciplina === "Futbol Infantil") {
        return "Futbol";
      }
      return disciplina;
    });

    return [...new Set(disciplinasAgrupadas)].filter((d) => d !== "N/A");
  });

  const espaciosPorDisciplina = computed(() => {
    const seleccion = reservaPayload.value.disciplinaSeleccionada;
    if (!seleccion) return [];

    return espaciosDisponibles.value.filter((espacio) => {
      if (seleccion === "Futbol") {
        return (
          espacio.disciplinas.includes("Futbol Adultos") ||
          espacio.disciplinas.includes("Futbol Infantil")
        );
      }
      return espacio.disciplinas.includes(seleccion);
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
      const res = await api.get("/espacios/disponibilidad", {
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
      const res = await api.get("horarios/disponibilidad", {
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

  const handleHorario = async (hora_inicio, hora_fin) => {
    // VALIDACION hora_inicio < hora_fin
    // VALIDACION hora_inicio - hora_fin <= 2 horas
    // VALIDACION empalme con otros bloques

    const freeHour = false;

    // 1. Validar que la hora inicio sea menor a la hora fin
    if (horaInicioTemp.value >= horaFinTemp.value) {
      console.warn(
        "FUNCIÓN B: Horario Inválido. La hora inicio debe ser menor a la hora fin.",
      );
      // Aquí luego pondremos una alerta visual (Toast)
      return;
    }

    // 2. Aquí iría el algoritmo para revisar si se empalma con 'horariosDisponibles'
    const hayEmpalme = false; // Simulación

    if (hayEmpalme) {
      console.warn("FUNCIÓN B: Horario Ocupado. ¡Choca con otro bloque!");
      // Aquí luego pondremos otra alerta
    } else {
      console.log("FUNCIÓN A: Horario Válido. ¡Avanzamos!");
      seleccionarHorario(horaInicioTemp.value, horaFinTemp.value);
    }
  };

  // GO FOWARD
  const seleccionarDisciplina = (disciplina) => {
    reservaPayload.value.disciplinaSeleccionada = disciplina;
    pasoActual.value = "2";
  };

  const seleccionarEspacio = (id_espacio) => {
    fetchHorarioEspacio(id_espacio);
    reservaPayload.value.id_espacio = id_espacio;
    pasoActual.value = "3";
  };

  const seleccionarHorario = (hora_inicio, hora_fin) => {
    reservaPayload.value.hora_inicio = hora_inicio;
    reservaPayload.value.hora_inicio = hora_fin;
    pasoActual.value = "4";
    // agregar la lógica de pendiente que hizo Gabo
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

  // Cancelar reserva

  const resetearReserva = () => {
    pasoActual.value = "1";
    reservaPayload.value = {
      disciplinaSeleccionada: null,
      id_espacio: null,
      hora_inicio: null,
      hora_fin: null,
      acompanantes: [],
    };
  };

  const obtenerIconoName = (disciplina) => {
    // Mapea el nombre de la disciplina de tu BD con el nombre EXACTO de tu archivo .vue
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

    // Si no encuentra el deporte, carga un ícono genérico (asegúrate de crear DefaultIcon.vue)
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
    handleHorario,
  };
});
