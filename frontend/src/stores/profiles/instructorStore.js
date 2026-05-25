import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { useProfileLogic } from "./profileStore";
import api from "@/services/api";

export const useInstructorStore = defineStore("instructorProfile", () => {
    const { profileData, isLoading, error, fullName, userInitials, fetchProfile, updateProfile, logout, getSupportLink } = useProfileLogic('/instructor/profile');

    const idInstructor = computed(() => profileData.value?.id_instructor || null);

    const status = computed(() => profileData.value?.estatus_cuenta || profileData.value?.estatus || "");
    const email = computed(() => profileData.value?.correo_electronico || "");
    const phone = computed(() => profileData.value?.telefono || "");
    const role = computed(() => profileData.value?.rol || "Instructor");
    const hireDate = computed(() => profileData.value?.fecha_afiliacion || "");
    const birthDate = computed(() => profileData.value?.fecha_nacimiento || "");
    const disciplinas = computed(() => profileData.value?.disciplinas || []);

    const isCuidador = computed(() => {
        return profileData.value?.tieneLudoteca == true;
    });

    const turnoLudotecaHoy = computed(() => profileData.value?.turno_ludoteca_hoy ?? null);

    const tieneTurnoLudotecaHoy = computed(() => turnoLudotecaHoy.value !== null);

    const homeSessions = computed(() => {
        return homeSessionsCache.value;
    });

    // Caché de sesiones del home — persiste mientras el store viva (misma sesión)
    const homeSessionsCache = ref(null);
    const homeSessionsLoading = ref(false);

    const fetchHomeSessions = async () => {
        if (homeSessionsCache.value !== null) return homeSessionsCache.value;

        homeSessionsLoading.value = true;
        try {
            const response = await api.get('/instructor/sessions');
            if (response.data?.success) {
                homeSessionsCache.value = response.data.data;
            } else {
                homeSessionsCache.value = [];
            }
        } catch {
            homeSessionsCache.value = [];
        } finally {
            homeSessionsLoading.value = false;
        }
        return homeSessionsCache.value;
    };

    return {
        profileData,
        isLoading,
        error,
        fullName,
        userInitials,
        email,
        phone,
        role,
        hireDate,
        status,
        birthDate,
        idInstructor,
        disciplinas,
        isCuidador,
        turnoLudotecaHoy,
        tieneTurnoLudotecaHoy,
        homeSessions,
        fetchProfile,
        updateProfile,
        logout,
        getSupportLink,
        homeSessionsCache,
        homeSessionsLoading,
        fetchHomeSessions,
    };
});