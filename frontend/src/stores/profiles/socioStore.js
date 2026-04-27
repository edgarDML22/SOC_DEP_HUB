import { computed } from "vue";
import { defineStore } from "pinia";
import { useProfileLogic } from "./profileStore";

export const useProfileStore = defineStore("profile", () => {
  // 1. Extraemos todo el comportamiento base del composable
  const {
    profileData, isLoading, error, fullName, userInitials, formatText,
    fetchProfile, updateProfile, logout, getSupportLink
  } = useProfileLogic();

  // 2. GETTERS ESPECÍFICOS DEL SOCIO TITULAR
  const typeSocio = computed(() => {
    return formatText(profileData.value?.tipo_socio);
  });

  const statusAccount = computed(() => {
    return formatText(profileData.value?.estatus_cuenta) || 'Desconocido';
  });

  const modalidadPlan = computed(() => {
    return formatText(profileData.value?.modalidad_plan);
  });

  const actionNumber = computed(() => {
    return profileData.value?.numero_accion || "N/A";
  });

  const idSocio = computed(() => {
    return profileData.value?.id_socio || null;
  });

  const esAdmin = computed(() => {
    return profileData.value?.rol === 'gerente';
  });

  // Nuevos campos extraídos para edición
  const fechaNacimiento = computed(() => profileData.value?.fecha_nacimiento || "");
  const genero = computed(() => profileData.value?.genero || "");
  const correoElectronico = computed(() => profileData.value?.correo_electronico || "");
  const fechaFinPenalizacion = computed(() => profileData.value?.fecha_fin_penalizacion || null);

  const isPenalized = computed(() => {
    if (!profileData.value) return false;
    const status = profileData.value?.estatus_cuenta?.toUpperCase();
    return status === "PENALIZADO";
  });

  const isAccountInactive = computed(() => {
    if (!profileData.value) return false;
    const status = profileData.value?.estatus_cuenta?.toUpperCase();
    return status === "INACTIVO" || status === "SUSPENDIDO" || status === "PENALIZADO";
  });

  // Da color al badge dinámicamente
  const statusBadgeClass = computed(() => {
    const status = profileData.value?.estatus_cuenta?.toUpperCase();
    if (status === "AL_CORRIENTE" || status === "ACTIVO") return "badge-green";
    if (status === "SUSPENDIDO" || status === "MOROSO" || status === "INACTIVO") return "badge-red";
    return "badge-gray";
  });

  const passwordUpdateText = computed(() => {
    if (!profileData.value?.fecha_actualizacion_password) {
      return "Cargando información...";
    }

    const updateDate = new Date(profileData.value.fecha_actualizacion_password);
    const today = new Date();

    const diffTime = Math.abs(today - updateDate);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "Última actualización hoy";
    if (diffDays === 1) return "Última actualización hace 1 día";

    return `Última actualización hace ${diffDays} días`;
  });

  // 3. RETURN: Retornamos las variables locales combinadas con las del composable
  return {
    profileData,
    isLoading,
    error,
    userInitials,
    fullName,
    typeSocio,
    actionNumber,
    statusAccount,
    isAccountInactive,
    statusBadgeClass,
    passwordUpdateText,
    fechaNacimiento,
    genero,
    correoElectronico,
    fechaFinPenalizacion,
    isPenalized,
    modalidadPlan,
    idSocio,
    esAdmin,
    fetchProfile,
    updateProfile,
    logout,
    getSupportLink
  };
});