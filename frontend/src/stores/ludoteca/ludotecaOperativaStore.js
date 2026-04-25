import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";


import { useInstructorStore } from "@/stores/profiles/instructorStore";

export const useLudotecaOperativaStore = defineStore("ludotecaOperativa", () => {
    const instructorStore = useInstructorStore();

    const estanciasDelDia = ref([]);
    const turnoActual = ref({
        hora_inicio: null,
        hora_fin: null
    });

    const loading = ref(false);
    const error = ref(null);

    // Función auxiliar para convertir "HH:MM:SS" o "HH:MM" a minutos
    const timeToMinutes = (timeString) => {
        if (!timeString) return null;
        const [hours, minutes] = timeString.split(':').map(Number);
        return hours * 60 + minutes;
    };

    // Gating de Tiempo: ¿El instructor está dentro de su horario?
    const isTurnoActivo = computed(() => {
        if (!turnoActual.value.hora_inicio || !turnoActual.value.hora_fin) return false;

        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        const startMinutes = timeToMinutes(turnoActual.value.hora_inicio);
        const endMinutes = timeToMinutes(turnoActual.value.hora_fin);

        return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
    });

    // Filtros de Tablero
    const estanciasActivas = computed(() => {
        return estanciasDelDia.value.filter(estancia => estancia.estatus === 'Activo');
    });

    const estanciasInactivas = computed(() => {
        return estanciasDelDia.value.filter(estancia => estancia.estatus === 'Inactivo');
    });

    const estanciasEntregadas = computed(() => {
        return estanciasDelDia.value.filter(estancia => estancia.estatus === 'Entregado');
    });


    // ACTIONS

    // Cargar la información inicial
    const fetchEstancias = async () => {
        if (!instructorStore.idInstructor) {
            error.value = "No se pudo identificar al instructor. Inicia sesión nuevamente.";
            return;
        }

        loading.value = true;
        error.value = null;

        try {
            // NOTA: Cuando el backend esté listo, ajustar esta ruta GET si cambia
            const res = await api.get(`/ludoteca/operativa?id_instructor=${instructorStore.idInstructor}`);

            if (res.data.success) {
                estanciasDelDia.value = res.data.data.estancias || [];
                turnoActual.value = res.data.data.turno || { hora_inicio: null, hora_fin: null };
            }
        } catch (err) {
            console.error("Error al cargar datos de la ludoteca:", err);
            error.value = err.response?.data?.message || "No se pudo cargar la información del turno.";
        } finally {
            loading.value = false;
        }
    };

    // Rollback Optimista: Cambiar estatus de un niño sin esperar al servidor
    const cambiarEstatusEstancia = async (idEstancia, nuevoEstatus) => {
        // Asumi que la llave primaria puede llamarse "id" o "id_estancia"
        const estanciaIndex = estanciasDelDia.value.findIndex(e => e.id === idEstancia || e.id_estancia === idEstancia);
        if (estanciaIndex === -1) return;

        // GUARDAR ESTADO ANTERIOR
        const estatusAnterior = estanciasDelDia.value[estanciaIndex].estatus;

        // ACTUALIZACIÓN OPTIMISTA EN UI
        estanciasDelDia.value[estanciaIndex].estatus = nuevoEstatus;
        error.value = null;

        try {

            const res = await api.patch(`/ludoteca/estancia/${idEstancia}/status`, {
                estatus: nuevoEstatus
            });

            if (!res.data.success) {
                throw new Error("El backend rechazó el cambio.");
            }


            if (nuevoEstatus === 'Entregado' && res.data.data?.hora_salida) {
                estanciasDelDia.value[estanciaIndex].hora_salida = res.data.data.hora_salida;
            }

        } catch (err) {

            console.error("Falló la actualización optimista:", err);

            // Regresamos la tarjeta a la pestaña original
            estanciasDelDia.value[estanciaIndex].estatus = estatusAnterior;

            error.value = "Error de red: El cambio no se guardó. La tarjeta regresó a su posición original.";
        }
    };

    return {
        // State
        estanciasDelDia,
        turnoActual,
        loading,
        error,
        // Getters
        isTurnoActivo,
        estanciasActivas,
        estanciasInactivas,
        estanciasEntregadas,
        // Actions
        fetchEstancias,
        cambiarEstatusEstancia
    };
});