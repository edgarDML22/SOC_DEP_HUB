import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";
import router from "@/router";
import { useFriendStore } from "@/stores/community/friendStore";
import { useFamilyStore } from "@/stores/community/familyStore";
import { useGuestStore } from "@/stores/community/guestStore";
import { useNotificacionesStore } from "@/stores/profiles/notificacionesStore";
import { useReservationStore } from "@/stores/reservationStore";
import { useQrStore } from "@/stores/profiles/qrStore";
import { useBootstrapStore } from "@/stores/profiles/bootstrapStore";
import { useformat } from '@/utils/formatters';

const { formatText } = useformat();

export function useProfileLogic(endpointUrl = '/profile') {
    const profileData = ref(null);
    const isLoading = ref(false);
    const error = ref(null);
    let profilePromise = null;

    const fullName = computed(() => {
        if (!profileData.value?.nombre_completo) return "";
        return profileData.value.nombre_completo;
    });

    const userInitials = computed(() => {
        if (fullName.value === "") return "?";
        const names = fullName.value.split(" ");
        if (names.length >= 2) {
            return `${names[0][0]}${names[1][0]}`.toUpperCase();
        }
        return names[0][0].toUpperCase();
    });

    // 2. ACTIONS Comunes
    const fetchProfile = async () => {
        if (profileData.value) return profileData.value;
        if (profilePromise) return profilePromise;

        isLoading.value = true;
        error.value = null;

        profilePromise = api.get(endpointUrl)
            .then(response => {
                if (response.data.success) {
                    profileData.value = response.data.data;
                }
                return profileData.value;
            })
            .catch(err => {
                console.error("Error de conexión al obtener el perfil:", err);
                error.value = err;
            })
            .finally(() => {
                isLoading.value = false;
                profilePromise = null;
            });

        return profilePromise;
    };

    const updateProfile = async (formData) => {
        isLoading.value = true;
        try {
            const response = await api.post("/profile/update", formData, {
                headers: { Authorization: `Bearer ${localStorage.getItem("auth_token")}` },
            });

            if (response.data.success) {
                profileData.value = null; // Refrescar en memoria
                await fetchProfile();
                return true;
            }
        } catch (error) {
            console.error("Error al actualizar perfil:", error);
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const uploadPhoto = async (file) => {
        isLoading.value = true;
        try {
            const formData = new FormData();
            formData.append("foto_perfil", file);

            const response = await api.post("/profile/upload-photo", formData, {
                headers: { 
                    Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
                    "Content-Type": "multipart/form-data"
                },
            });

            if (response.data.success) {
                profileData.value = null; // Forzar recarga del perfil
                await fetchProfile();
                return { success: true, foto_perfil: response.data.foto_perfil };
            }
        } catch (error) {
            console.error("Error al subir foto de perfil:", error);
            return { success: false, error: error.response?.data?.message || "Error al subir." };
        } finally {
            isLoading.value = false;
        }
    };

    const logout = () => {
        // Fire-and-forget: no esperamos al servidor para limpiar la sesión local
        api.post("/auth/logout").catch(() => { });

        profileData.value = null;
        profilePromise = null;

        useFriendStore().reset();
        useFamilyStore().$reset();
        useGuestStore().$reset();
        useNotificacionesStore().reset();
        useReservationStore().resetearReserva();
        useQrStore().reset();
        useBootstrapStore().reset();

        localStorage.clear();
        router.push("/login");
    };

    const getSupportLink = async () => {
        try {
            const response = await api.get("/system/support-link");
            const url = response.data?.data?.support_url;
            if (url) window.open(url, "_blank");
        } catch (error) {
            console.error("Hubo un error al obtener el link:", error);
            alert("No se pudo cargar el formulario de soporte.");
        }
    };


    return {
        profileData,
        isLoading,
        error,
        fullName,
        userInitials,
        formatText,
        fetchProfile,
        updateProfile,
        uploadPhoto,
        logout,
        getSupportLink
    };
}