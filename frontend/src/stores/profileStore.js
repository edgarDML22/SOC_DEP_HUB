import { ref, computed } from "vue";
import { defineStore } from "pinia";
import api from "@/services/api";
import router from "@/router";

export const useProfileStore = defineStore("profile", () => {
  // 1. STATE
  const profileData = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  let profilePromise = null;

  // Helper para formatear texto: "AL_CORRIENTE" -> "Al Corriente"
  const formatText = (text) => {
    if (!text) return "N/A";
    return text
      .toLowerCase()
      .split('_')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  };

  // 2. GETTERS
  // Tipos y estados formateados
  const typeSocio = computed(() => {
    return formatText(profileData.value?.tipo_socio);
  });

  const statusAccount = computed(() => {
    return formatText(profileData.value?.estatus_cuenta) || 'Desconocido';
  });

  const modalidadPlan = computed(() => {
    return formatText(profileData.value?.modalidad_plan);
  });

  // Datos originales
  const actionNumber = computed(() => {
    return profileData.value?.numero_accion || "N/A";
  });

  const idSocio = computed(() => {
    return profileData.value?.id_socio || null;
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

  // Nuevos campos extraídos para edición
  const fechaNacimiento = computed(() => profileData.value?.fecha_nacimiento || "");
  const genero = computed(() => profileData.value?.genero || "");
  const correoElectronico = computed(() => profileData.value?.correo_electronico || "");

  const isAccountInactive = computed(() => {
    if (!profileData.value) return false;
    const status = profileData.value?.estatus_cuenta?.toUpperCase();
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
  const fetchProfile = async () => {
    if (profileData.value) return profileData.value;


    if (profilePromise) return profilePromise;

    isLoading.value = true;
    error.value = null;

    profilePromise = api.get('/profile')
      .then(response => {
        if (response.data.success) {
          profileData.value = response.data.data;
        }
        return profileData.value;
      })
      .catch(err => {
        console.error("Error de conexión al obtener el perfil:", err);
      })
      .finally(() => {
        isLoading.value = false;
        profilePromise = null;
      });

    return profilePromise;
  };

  // Nuevo Action para guardar los cambios editados
  const updateProfile = async (formData) => {
    isLoading.value = true;
    try {
      const response = await api.post("/profile/update", formData, {
        headers: { Authorization: `Bearer ${localStorage.getItem("auth_token")}` },
      });

      if (response.data.success) {
        // Limpiamos el profileData actual y volvemos a cargar para traer los datos frescos de la BD
        profileData.value = null;
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
    fechaNacimiento,
    genero,
    correoElectronico,
    modalidadPlan,
    fetchProfile,
    updateProfile,
    logout,
    getSupportLink,
    idSocio
  };
});