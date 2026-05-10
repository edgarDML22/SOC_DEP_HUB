import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";

export const useAdminGuestStore = defineStore("adminGuest", () => {
    // State
    const guests = ref([]);
    const currentMemberId = ref(null);
    const isLoading = ref(false);
    const loading = ref({
        fetch: false,
        create: false,
        update: false,
        delete: false,
        toggle: false
    });
    const error = ref(null);

    // Computed
    const activePasses = computed(() => {
        return guests.value.filter(guest => guest.estatus_acceso === 'ACTIVO').length;
    });

    // Actions
    const fetchGuests = async (socioId) => {
        currentMemberId.value = socioId;
        isLoading.value = true;
        loading.value.fetch = true;
        error.value = null;
        try {
            // Es CRÍTICO incluir el prefijo /v1/ y pasar el socio_id para el admin
            const res = await api.get(`/guest-list?socio_id=${socioId}`);
            if (res.data.success) {
                // Mapeamos los campos del controlador a los que espera el frontend
                guests.value = res.data.data.map(g => ({
                    ...g,
                    id_invitado: g.id,
                    nombre_invitado: g.nombre
                }));
            }
        } catch (err) {
            console.error("Error fetching guests:", err);
            error.value = "Error al cargar la lista de invitados.";
        } finally {
            isLoading.value = false;
            loading.value.fetch = false;
        }
    };

    const createGuest = async (socioId, payload) => {
        isLoading.value = true;
        loading.value.create = true;
        error.value = null;
        try {
            // Incluimos socio_id en el payload para que el backend sepa a quién asignar el invitado
            const res = await api.post(`/guest-create?socio_id=${socioId}`, payload);

            if (res.data.success || res.status === 201) {
                await fetchGuests(socioId);
                const responseData = res.data.success ? res.data.data : res.data;
                return {
                    success: true,
                    data: {
                        ...responseData,
                        qr_code_image: `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${responseData.codigo_qr}`
                    }
                };
            }
        } catch (err) {
            console.error("Error creating guest:", err);
            const msg = err.response?.data?.message || "Error al registrar el invitado.";
            error.value = msg;
            return { success: false, error: msg };
        } finally {
            isLoading.value = false;
            loading.value.create = false;
        }
    };

    const updateGuest = async (socioId, guestId, payload) => {
        isLoading.value = true;
        loading.value.update = true;
        error.value = null;
        try {
            // Usamos la ruta /v1/guests/{id}
            const res = await api.put(`/guests/${guestId}`, payload);
            if (res.data.success) {
                await fetchGuests(socioId);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            console.error("Error updating guest:", err);
            const msg = err.response?.data?.message || "Error al actualizar los datos del invitado.";
            error.value = msg;
            return { success: false, error: msg };
        } finally {
            isLoading.value = false;
            loading.value.update = false;
        }
    };

    const togglePass = async (socioId, guestId, newStatus) => {
        isLoading.value = true;
        loading.value.toggle = true;
        error.value = null;
        try {
            // Usamos la ruta /v1/guests/{id}/toggle-pass
            const res = await api.put(`/guests/${guestId}/toggle-pass`);

            if (res.data.success) {
                await fetchGuests(socioId);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            console.error("Error toggling pass status:", err);
            const msg = err.response?.data?.message || "Error al cambiar el estatus del pase.";
            error.value = msg;
            return { success: false, error: msg };
        } finally {
            isLoading.value = false;
            loading.value.toggle = false;
        }
    };

    const deleteGuest = async (socioId, guestId) => {
        isLoading.value = true;
        loading.value.delete = true;
        error.value = null;
        try {
            // Usamos la ruta /v1/guests/{id}
            const res = await api.delete(`/guests/${guestId}`);
            if (res.data.success) {
                await fetchGuests(socioId);
                return { success: true };
            }
        } catch (err) {
            console.error("Error deleting guest:", err);
            const msg = err.response?.data?.message || "Error al eliminar el invitado.";
            error.value = msg;
            return { success: false, error: msg };
        } finally {
            isLoading.value = false;
            loading.value.delete = false;
        }
    };

    return {
        // State
        guests,
        currentMemberId,
        isLoading,
        loading,
        error,
        // Computed
        activePasses,
        // Actions
        fetchGuests,
        createGuest,
        updateGuest,
        togglePass,
        deleteGuest
    };
});