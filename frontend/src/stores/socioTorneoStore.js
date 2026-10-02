import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

/**
 * Semillas de `perPage` por listado. Reflejan los defaults del backend
 * (`12` en disponibles, `10` en historial) y solo sirven para el primer render,
 * antes de que llegue la `pagination` real que los sobreescribe.
 *
 * `per_page` NO se envía: cada endpoint define su propio tamaño de página.
 */
const PER_PAGE_SEED_DISPONIBLES = 12;
const PER_PAGE_SEED_HISTORIAL = 10;

/** Crea un objeto de paginación limpio (evita compartir referencias entre listados). */
const createPagination = (perPage) => ({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage,
  search: "",
});

/** Convierte a entero numérico conservando el fallback ante null/undefined/vacío. */
const toInt = (value, fallback) => {
  if (value === null || value === undefined || value === "") return fallback;
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

/** `true` solo para contenedores clave/valor: descarta `null` y arreglos. */
const isPlainObject = (value) =>
  value !== null && !Array.isArray(value) && typeof value === "object";

/**
 * Extrae el arreglo de filas de la respuesta.
 *
 * Cubre `data: [...]` y el paginador anidado `data: { data: [...], ... }`.
 */
const extractRows = (response) => {
  const body = response?.data;
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  if (Array.isArray(body?.data?.data)) return body.data.data;
  return [];
};

/**
 * Normaliza la meta de paginación del backend a un objeto plano.
 *
 * Acepta las cuatro envelope que conviven en la API:
 *  1. `meta` hermano de `data`      → { data: [...], meta: { total, current_page, last_page } }
 *  2. `pagination` hermano de `data`→ { data: [...], pagination: { total, current_page, last_page } }
 *  3. paginador anidado en `data`   → { data: { current_page, last_page, total, data: [...] } }
 *  4. sin paginación                → { data: [...] }
 *
 * El orden importa: `meta` y `pagination` se leen antes de `data` y del body
 * plano para no confundir el objeto contenedor con la meta real. En el caso (3)
 * `body.data` es un contenedor, nunca un arreglo de filas.
 *
 * En el caso (4) degrada a una única página para no renderizar controles inertes:
 * `total` y `perPage` se derivan de las filas recibidas, de modo que el resumen
 * "Mostrando X–Y de Z" siga siendo coherente aunque el backend no pagine.
 */
const normalizeMeta = (response, { page, perPage, search, fallbackRows = 0 }) => {
  const body = response?.data;
  const meta =
    body?.meta ??
    body?.pagination ??
    (isPlainObject(body?.data) ? body.data : null) ??
    (isPlainObject(body) ? body : null);

  const currentPage = Math.max(1, toInt(meta?.current_page ?? meta?.currentPage, page));
  const lastPage = Math.max(1, toInt(meta?.last_page ?? meta?.lastPage, 1));
  const total = Math.max(0, toInt(meta?.total, fallbackRows));

  return {
    currentPage,
    lastPage,
    total,
    perPage: Math.max(1, toInt(meta?.per_page ?? meta?.perPage, fallbackRows || perPage)),
    search,
  };
};

export const useSocioTorneoStore = defineStore("socioTorneo", () => {
  // --- STATE ---
  const disponibles = ref([]);
  const historial = ref([]);
  const loading = ref(false);
  const error = ref(null);

  // Paginación independiente por listado: catálogo e historial.
  const paginationDisponibles = ref(createPagination(PER_PAGE_SEED_DISPONIBLES));
  const paginationHistorial = ref(createPagination(PER_PAGE_SEED_HISTORIAL));

  // --- ACTIONS ---

  /**
   * Carga los torneos disponibles en estatus EN_INSCRIPCION.
   *
   * @param {{page?:number, perPage?:number, search?:string}} options
   *        Si se omite, se reusa el estado de paginación actual.
   *        `perPage` no se envía: el tamaño de página lo define el backend.
   */
  const fetchDisponibles = async (options = {}, isRetry = false) => {
    const current = paginationDisponibles.value;
    const page = Math.max(1, toInt(options.page, current.currentPage));
    const perPage = Math.max(1, toInt(options.perPage, current.perPage));
    const search = (options.search ?? current.search ?? "").trim();

    loading.value = true;
    error.value = null;
    try {
      const res = await api.get("/socio/torneos/disponibles", {
        params: { page, ...(search ? { search } : {}) },
      });

      disponibles.value = extractRows(res);
      paginationDisponibles.value = normalizeMeta(res, {
        page,
        perPage,
        search,
        fallbackRows: disponibles.value.length,
      });

      // Si la búsqueda o una recarga dejó la página actual fuera de rango,
      // se recupera a la última página válida (una sola reentrada).
      const meta = paginationDisponibles.value;
      if (!isRetry && meta.total > 0 && meta.currentPage > meta.lastPage) {
        return fetchDisponibles({ ...options, page: meta.lastPage }, true);
      }
    } catch (err) {
      console.error("Error al cargar torneos disponibles:", err);
      error.value = err.response?.data?.message || "No se pudieron cargar los torneos disponibles.";
    } finally {
      loading.value = false;
    }
  };

  /**
   * Carga el historial de torneos del socio.
   *
   * @param {{page?:number, perPage?:number}} options
   *        `perPage` no se envía: el tamaño de página lo define el backend.
   */
  const fetchHistorial = async (options = {}, isRetry = false) => {
    const current = paginationHistorial.value;
    const page = Math.max(1, toInt(options.page, current.currentPage));
    const perPage = Math.max(1, toInt(options.perPage, current.perPage));

    loading.value = true;
    error.value = null;
    try {
      const res = await api.get("/socio/torneos/historial", {
        params: { page },
      });

      historial.value = extractRows(res);
      paginationHistorial.value = normalizeMeta(res, {
        page,
        perPage,
        search: "",
        fallbackRows: historial.value.length,
      });

      const meta = paginationHistorial.value;
      if (!isRetry && meta.total > 0 && meta.currentPage > meta.lastPage) {
        return fetchHistorial({ ...options, page: meta.lastPage }, true);
      }
    } catch (err) {
      console.error("Error al cargar historial de torneos:", err);
      error.value = err.response?.data?.message || "No se pudo cargar el historial de torneos.";
    } finally {
      loading.value = false;
    }
  };

  /**
   * Inscribe a un participante (Titular / Familiar) en un torneo.
   */
  const inscribir = async (id_torneo, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.post(`/torneos/${id_torneo}/inscripciones`, payload);
      
      // Actualizar localmente el estado ya_inscrito e inscritos_actual del torneo
      const torneo = disponibles.value.find(t => t.id_torneo === id_torneo);
      if (torneo) {
        torneo.ya_inscrito = true;
        torneo.inscritos_actual += 1;
      }
      return { success: true, message: res.data.message || "Inscripción confirmada con éxito!" };
    } catch (err) {
      console.error("Error al inscribir en torneo:", err);
      const motivo = err.response?.data?.motivo || err.response?.data?.message || "Error al realizar la inscripción.";
      return { success: false, error: motivo };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Crea un equipo para el torneo con el compañero seleccionado
   */
  const crearEquipo = async (id_torneo, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.post(`/torneos/${id_torneo}/equipos`, payload);
      return res.data;
    } catch (err) {
      console.error("Error al crear equipo:", err);
      error.value = err.response?.data?.message || "Error enviando invitación";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Responde a una invitación de equipo (ACEPTADA / RECHAZADA)
   */
  const responderInvitacion = async (id_torneo, id_equipo, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.patch(`/torneos/${id_torneo}/equipos/${id_equipo}/responder`, payload);
      return res.data;
    } catch (err) {
      console.error("Error al responder invitación:", err);
      error.value = err.response?.data?.message || "Error respondiendo a la invitación";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Reasigna compañero de un equipo
   */
  const reasignarCompanero = async (payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.patch(`/equipos/reasignar`, payload);
      return res.data;
    } catch (err) {
      console.error("Error al reasignar compañero:", err);
      error.value = err.response?.data?.message || "Error reasignando compañero";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    disponibles,
    historial,
    loading,
    error,
    paginationDisponibles,
    paginationHistorial,
    fetchDisponibles,
    fetchHistorial,
    inscribir,
    crearEquipo,
    responderInvitacion,
    reasignarCompanero
  };
});
