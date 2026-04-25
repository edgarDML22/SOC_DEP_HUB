import { computed } from "vue";
import { defineStore } from "pinia";
import { useProfileLogic } from "./profileStore";

export const useInstructorStore = defineStore("instructorProfile", () => {
    // 1. Extraemos todo el comportamiento base de nuestro nuevo composable
    const { profileData, isLoading, error, fullName, userInitials, fetchProfile, updateProfile, logout, getSupportLink } = useProfileLogic();

    // 2. GETTERS ESPECÍFICOS DEL INSTRUCTOR
    const idInstructor = computed(() => profileData.value?.id_instructor || null);

    const status = computed(() => profileData.value?.estatus_cuenta || profileData.value?.estatus || "");
    const email = computed(() => profileData.value?.correo_electronico || "");
    const phone = computed(() => profileData.value?.telefono || "");
    const role = computed(() => profileData.value?.rol || "Instructor");
    const hireDate = computed(() => profileData.value?.fecha_afiliacion || "");
    const birthDate = computed(() => profileData.value?.fecha_nacimiento || "");
    const disciplinas = computed(() => profileData.value?.disciplinas || []);
    const isCuidador = computed(() => {
        return disciplinas.value.some(d => {
            const nombre = typeof d === 'string' ? d : d.nombre_disciplina;
            return nombre && nombre.toUpperCase() === 'LUDOTECA';
        });
    });

    // 3. RETURN: Retornamos las variables locales combinadas con las del composable
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
        fetchProfile,
        updateProfile,
        logout,
        getSupportLink
    };
});