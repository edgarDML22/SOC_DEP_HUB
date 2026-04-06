import { ref, computed } from "vue";
import { defineStore } from "pinia";
import api from "@/services/api";
import router from "@/router";

export const useProfileStore = defineStore("profile", () => {
  // 1. STATE
  const profileData = ref(null);
  const isLoading = ref(false);
  const error = ref(null);

  // 2. GETTERS
  // Calcula las iniciales
  const typeSocio = computed(() => {
    return profileData.value?.tipo_socio || "N/A";
  });

  const actionNumber = computed(() => {
    return profileData.value?.numero_accion || "N/A";
  });

  const fullName = computed(() => {
    if (!profileData.value?.nombre_completo) return "";
    return profileData.value.nombre_completo;
  });

  const userInitials = computed(() => {
    if (fullName.value === "") return "";
    const names = fullName.value.split(" ");
    if (names.length >= 2) {
      return `${names[0][0]}${names[1][0]}`.toUpperCase();
    }
    return names[0][0].toUpperCase();
  });

  // Cuenta inactiva
  const statusAccount = computed(() => {
    return profileData.value?.estatus_cuenta || 'Desconocido';
  });

  const isAccountInactive = computed(() => {
    if (!profileData.value) return false;
    const status = statusAccount.value?.toUpperCase();
    return status === "INACTIVO" || status === "SUSPENDIDO";
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

  // 3. ACTIONS
  const fetchProfile = async (params) => {
    if (profileData.value) return; //para solo cargar la primera vez

    isLoading.value = true;
    error.value = null;

    try {
      const response = await api.get('/profile');

      if (response.data.success) {
        profileData.value = response.data.data;
      } else {
        console.error("Error desde el servidor:", response.data.message);
      }
    } catch (error) {
      console.error("Error de conexión al obtener el perfil:", error);
    } finally {
      isLoading.value = false;
    }
  };

  const logout = async () => {
    try {
      // Hacemos la petición al backend para que invalide el token
      await api.post("/auth/logout");
    } catch (error) {
      console.error("Error al cerrar sesión en el servidor:", error);
    } finally {
      localStorage.clear();
      router.push("/login");
      profileData.value = null;
    }
  };

  const getSupportLink = async () => {
    try {
      const response = await api.get("/system/support-link");
      const url = response.data?.data?.support_url;

      if (url) {
        window.open(url, "_blank");
      }
    } catch (error) {
      console.error("Hubo un error al obtener el link:", error);
      alert("No se pudo cargar el formulario de soporte.");
    }
  };

  // 4. RETURN
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
    fetchProfile,
    logout,
    getSupportLink,
  };
});
