import { computed } from "vue";
import { defineStore } from "pinia";
import { useProfileLogic } from "./profileStore";
import { useQrStore } from "./qrStore";

export const useProfileStore = defineStore("profile", () => {
  // 1. Extraemos todo el comportamiento base del composable
  const {
    profileData, isLoading, error, fullName, userInitials, formatText,
    fetchProfile: fetchProfileBase, updateProfile, uploadPhoto, logout, getSupportLink
  } = useProfileLogic();

  // Wrapper: después de cargar el perfil, propaga qr_payload y qr_image_url al qrStore
  const fetchProfile = async () => {
    const result = await fetchProfileBase();
    const qrStore = useQrStore();
    if (profileData.value?.qr_payload) {
      qrStore.setFromProfile(profileData.value.qr_payload, profileData.value.qr_image_url)
    } else {
      // Cuenta inactiva o sin QR — marca como cargado sin payload
      qrStore.setFromProfile(null, null)
    }
    return result;
  };

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

  // Campos de perfil para edición
  const fechaNacimiento    = computed(() => profileData.value?.fecha_nacimiento    || "");
  const genero             = computed(() => profileData.value?.genero             || "");
  const correoElectronico  = computed(() => profileData.value?.correo_electronico || "");

  // Fechas de liberación por servicio (reemplazan a fecha_fin_penalizacion unificada)
  const fechaFinPenalizacionReserva  = computed(() => profileData.value?.fecha_fin_penalizacion_reserva  || null);
  const fechaFinPenalizacionLudoteca = computed(() => profileData.value?.fecha_fin_penalizacion_ludoteca || null);

  // Formato legible "dd de mes" para mostrar en la UI del socio
  const formatFechaLiberacion = (isoString) => {
    if (!isoString) return null;
    const str = String(isoString);
    const datePart = str.split(/[ T]/)[0];
    const d = new Date(`${datePart}T00:00:00`);
    if (isNaN(d.getTime())) return null;
    return d.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
  };

  const fechaLiberacionReserva  = computed(() => formatFechaLiberacion(fechaFinPenalizacionReserva.value));
  const fechaLiberacionLudoteca = computed(() => formatFechaLiberacion(fechaFinPenalizacionLudoteca.value));

  // Normaliza cualquier valor de enum que pueda llegar como objeto {value} o string directo
  const toStr = (val) => {
    if (!val) return '';
    if (typeof val === 'string') return val.toUpperCase();
    if (typeof val === 'object' && val.value) return String(val.value).toUpperCase();
    return String(val).toUpperCase();
  };

  const isPenalized = computed(() => {
    if (!profileData.value) return false;
    return toStr(profileData.value.estatus_cuenta) === "PENALIZADO";
  });

  // true cuando hay cualquier penalización activa (para la alerta en SocioHome)
  const hasPenalty = computed(() => {
    if (!profileData.value) return false;
    const p = toStr(profileData.value.estatus_penalizacion);
    return p === 'PENALIZADO_RESERVA' || p === 'PENALIZADO_LUDOTECA' || p === 'PENALIZADO_AMBOS';
  });

  // Texto formateado del estatus_penalizacion (guiones bajos → espacios, capitalizado)
  const statusPenalizacion = computed(() => {
    const raw = profileData.value?.estatus_penalizacion;
    if (!raw) return '';
    return String(raw).replace(/_/g, ' ');
  });

  const isAccountInactive = computed(() => {
    if (!profileData.value) return false;
    const penaltyStatus = toStr(profileData.value.estatus_penalizacion);
    const accountStatus = toStr(profileData.value.estatus_cuenta);

    return accountStatus === "INACTIVO" || penaltyStatus === "SUSPENDIDO" || penaltyStatus === "PENALIZADO_AMBOS";
  });

  const isLudotecaBlocked = computed(() => {
    if (!profileData.value) return false;
    const penaltyStatus = toStr(profileData.value.estatus_penalizacion);
    const accountStatus = toStr(profileData.value.estatus_cuenta);

    return penaltyStatus === "PENALIZADO_LUDOTECA" || penaltyStatus === "PENALIZADO_AMBOS" || penaltyStatus === "SUSPENDIDO" || accountStatus === "INACTIVO";
  });

  const isReservationsBlocked = computed(() => {
    if (!profileData.value) return false;
    const penaltyStatus = toStr(profileData.value.estatus_penalizacion);
    const accountStatus = toStr(profileData.value.estatus_cuenta);

    return penaltyStatus === "PENALIZADO_RESERVA" || penaltyStatus === "PENALIZADO_AMBOS" || penaltyStatus === "SUSPENDIDO" || accountStatus === "INACTIVO";
  });

  const tienePlanFamiliar = computed(() => {
    if (!profileData.value?.modalidad_plan) return false;
    return toStr(profileData.value.modalidad_plan) === 'FAMILIAR';
  });

  // Da color al badge dinámicamente
  const statusBadgeClass = computed(() => {
    const accountStatus = toStr(profileData.value?.estatus_cuenta);
    const penaltyStatus = toStr(profileData.value?.estatus_penalizacion);

    // Priorizamos mostrar el error si hay suspensión o penalización
    if (penaltyStatus === "SUSPENDIDO" || penaltyStatus === "PENALIZADO_AMBOS" || accountStatus === "INACTIVO" || accountStatus === "MOROSO") {
      return "badge-red";
    }

    if (penaltyStatus === "PENALIZADO_LUDOTECA" || penaltyStatus === "PENALIZADO_RESERVA") {
      return "badge-orange"; // Color preventivo para penalizaciones parciales
    }

    if (accountStatus === "AL_CORRIENTE" || accountStatus === "ACTIVO") return "badge-green";

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

  const fotoPerfil = computed(() => profileData.value?.foto_perfil || null);

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
    fechaFinPenalizacionReserva,
    fechaFinPenalizacionLudoteca,
    fechaLiberacionReserva,
    fechaLiberacionLudoteca,
    isPenalized,
    hasPenalty,
    statusPenalizacion,
    modalidadPlan,
    idSocio,
    esAdmin,
    isLudotecaBlocked,
    isReservationsBlocked,
    fetchProfile,
    updateProfile,
    uploadPhoto,
    fotoPerfil,
    tienePlanFamiliar,
    logout,
    getSupportLink,
    formatFechaLiberacion
  };
});