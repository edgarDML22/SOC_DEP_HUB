import { ref, computed } from "vue";
import { defineStore } from "pinia";
import { useProfileLogic } from "./profileStore";
import api from "@/services/api";
import Swal from "sweetalert2";

export const useAdminStore = defineStore("adminProfile", () => {

    const { 
        profileData, 
        isLoading: profileLoading, 
        error, 
        fetchProfile, 
        updateProfile, 
        logout,
        userInitials,
        getSupportLink
    } = useProfileLogic();

    const managers = ref([]);
    const isLoading = ref({
        fetch: false,
        create: false,
        update: false,
        toggle: false
    });

    const role = computed(() => profileData.value?.rol || "");
    const idAdmin = computed(() => profileData.value?.id_empleado || null);
    const fullName = computed(() => profileData.value?.nombre_completo || "");
    const email = computed(() => profileData.value?.correo_electronico || "");
    const position = computed(() => profileData.value?.cargo || "");
    const status = computed(() => profileData.value?.estatus || "");

    // Actions para la gestión de gerentes
    const fetchManagers = async () => {
        isLoading.value.fetch = true;
        try {
            const { data } = await api.get('/admin/users');
            if (data.success) {
                managers.value = data.data;
            }
        } catch (error) {
            console.error('Error fetching managers:', error);
            Swal.fire('Error', 'No se pudieron cargar los gerentes', 'error');
        } finally {
            isLoading.value.fetch = false;
        }
    };

    const crearManager = async (payload) => {
        isLoading.value.create = true;
        try {
            const { data } = await api.post('/admin/users', payload);
            if (data.success) {
                await fetchManagers();
                Swal.fire('Éxito', 'Gerente creado correctamente', 'success');
                return true;
            }
        } catch (error) {
            console.error('Error creating manager:', error);
            const msg = error.response?.data?.message || 'Error al crear el gerente';
            Swal.fire('Error', msg, 'error');
            return false;
        } finally {
            isLoading.value.create = false;
        }
    };

    const actualizarManager = async (id, payload) => {
        isLoading.value.update = true;
        try {
            const { data } = await api.put(`/admin/users/${id}`, payload);
            if (data.success) {
                await fetchManagers();
                Swal.fire('Éxito', 'Gerente actualizado correctamente', 'success');
                return true;
            }
        } catch (error) {
            console.error('Error updating manager:', error);
            const msg = error.response?.data?.message || 'Error al actualizar el gerente';
            Swal.fire('Error', msg, 'error');
            return false;
        } finally {
            isLoading.value.update = false;
        }
    };

    const toggleActivo = async (id, status) => {
        isLoading.value.toggle = true;
        try {
            const { data } = await api.patch(`/admin/users/${id}/toggle-activo`, { activo: status });
            if (data.success) {
                const index = managers.value.findIndex(m => m.id === id);
                if (index !== -1) {
                    managers.value[index].activo = status;
                }
                return true;
            }
        } catch (error) {
            console.error('Error toggling manager status:', error);
            Swal.fire('Error', 'No se pudo cambiar el estatus', 'error');
            return false;
        } finally {
            isLoading.value.toggle = false;
        }
    };

    return {
        idAdmin,
        fullName,
        email,
        position,
        status,
        profileData,
        profileLoading,
        error,
        fetchProfile,
        updateProfile,
        role,
        logout,
        userInitials,
        getSupportLink,
        // Management exports
        managers,
        isLoading,
        fetchManagers,
        crearManager,
        actualizarManager,
        toggleActivo
    };

});
