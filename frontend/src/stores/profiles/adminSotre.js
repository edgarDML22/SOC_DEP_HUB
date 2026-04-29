import { computed } from "vue";
import { defineStore } from "pinia";
import { useProfileLogic } from "./profileStore";

export const useAdminStore = defineStore("adminProfile", () => {

    const { profileData, isLoading, error, fetchProfile, updateProfile, logout } = useProfileLogic();
    const role = computed(() => profileData.value?.rol || "");

    const idAdmin = computed(() => profileData.value?.id_empleado || null);
    const fullName = computed(() => profileData.value?.nombre_completo || "");
    const email = computed(() => profileData.value?.correo_electronico || "");
    const position = computed(() => profileData.value?.cargo || "");
    const status = computed(() => profileData.value?.estatus || "");

    return {
        idAdmin,
        fullName,
        email,
        position,
        status,
        profileData,
        isLoading,
        error,
        fetchProfile,
        updateProfile,
        role,
        logout,
    }

});
