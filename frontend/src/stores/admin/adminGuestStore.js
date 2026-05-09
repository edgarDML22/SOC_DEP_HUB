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
            const res = await api.get(`/socios/${socioId}/guests`);
            if (res.data.success) {
                guests.value = res.data.data;
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
            const res = await api.post(`/socios/${socioId}/guests`, payload);
            if (res.data.success) {
                await fetchGuests(socioId);
                return { success: true, data: res.data.data };
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
            const res = await api.put(`/socios/${socioId}/guests/${guestId}`, payload);
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
            const res = await api.patch(`/socios/${socioId}/guests/${guestId}/status`, { 
                estatus_acceso: newStatus 
            });
            
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
            const res = await api.delete(`/socios/${socioId}/guests/${guestId}`);
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