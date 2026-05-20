import { defineStore } from "pinia";
import api from "@/services/api";

export const usePreRegisterStore = defineStore("preRegister", {
  state: () => ({
    paso: 1,
    tipo: null, // 'INDIVIDUAL' o 'EQUIPO'
    nombre_equipo: "",
    datosCapitan: {
      nombre: "",
      apellido: "",
      email: "",
      telefono: "",
      ranking_declarado: null,
      fecha_nacimiento: "",
      genero: "",
    },
    datosCompanero: {
      nombre: "",
      apellido: "",
      email: "",
      telefono: "",
      ranking_declarado: null,
      fecha_nacimiento: "",
      genero: "",
    },
    archivos: {
      ine: null,
      curp: null,
      carta: null,
      companero_ine: null,
      companero_curp: null,
      companero_carta: null,
    },
    loading: false,
    error: null,
    exito: false,
    mongoId: null,
  }),

  actions: {
    setPaso(n) {
      this.paso = n;
    },

    setTipo(tipo) {
      this.tipo = tipo;
    },

    setDatos(rol, datos) {
      if (rol === "capitan") {
        this.datosCapitan = { ...this.datosCapitan, ...datos };
      } else if (rol === "companero") {
        this.datosCompanero = { ...this.datosCompanero, ...datos };
      }
    },

    setArchivo(key, file) {
      this.archivos[key] = file;
    },

    async submitRegistro(id_torneo) {
      this.loading = true;
      this.error = null;
      this.exito = false;

      try {
        const formData = new FormData();
        formData.append("tipo", this.tipo);

        if (this.tipo === "INDIVIDUAL") {
          // Individual Fields
          const nombreCompleto = `${this.datosCapitan.nombre} ${this.datosCapitan.apellido}`.trim();
          formData.append("nombre_completo", nombreCompleto);
          formData.append("correo", this.datosCapitan.email);
          formData.append("fecha_nacimiento", this.datosCapitan.fecha_nacimiento);
          formData.append("genero", this.datosCapitan.genero);
          formData.append("ranking_declarado", this.datosCapitan.ranking_declarado);

          // Files
          if (this.archivos.ine) formData.append("ine_pdf", this.archivos.ine);
          if (this.archivos.curp) formData.append("curp_pdf", this.archivos.curp);
          if (this.archivos.carta) formData.append("carta_responsiva_pdf", this.archivos.carta);
        } else if (this.tipo === "EQUIPO") {
          // Team Fields
          formData.append("nombre_equipo", this.nombre_equipo);

          // Capitán (Integrante 0)
          const capitanNombre = `${this.datosCapitan.nombre} ${this.datosCapitan.apellido}`.trim();
          formData.append("integrantes[0][nombre_completo]", capitanNombre);
          formData.append("integrantes[0][correo]", this.datosCapitan.email);
          formData.append("integrantes[0][fecha_nacimiento]", this.datosCapitan.fecha_nacimiento);
          formData.append("integrantes[0][genero]", this.datosCapitan.genero);
          formData.append("integrantes[0][ranking_declarado]", this.datosCapitan.ranking_declarado);

          if (this.archivos.ine) formData.append("integrantes[0][ine_pdf]", this.archivos.ine);
          if (this.archivos.curp) formData.append("integrantes[0][curp_pdf]", this.archivos.curp);
          if (this.archivos.carta) formData.append("integrantes[0][carta_responsiva_pdf]", this.archivos.carta);

          // Compañero (Integrante 1)
          const companeroNombre = `${this.datosCompanero.nombre} ${this.datosCompanero.apellido}`.trim();
          formData.append("integrantes[1][nombre_completo]", companeroNombre);
          formData.append("integrantes[1][correo]", this.datosCompanero.email);
          formData.append("integrantes[1][fecha_nacimiento]", this.datosCompanero.fecha_nacimiento);
          formData.append("integrantes[1][genero]", this.datosCompanero.genero);
          formData.append("integrantes[1][ranking_declarado]", this.datosCompanero.ranking_declarado);

          if (this.archivos.companero_ine) formData.append("integrantes[1][ine_pdf]", this.archivos.companero_ine);
          if (this.archivos.companero_curp) formData.append("integrantes[1][curp_pdf]", this.archivos.companero_curp);
          if (this.archivos.companero_carta) formData.append("integrantes[1][carta_responsiva_pdf]", this.archivos.companero_carta);
        }

        const response = await api.post(`/torneos/${id_torneo}/pre-registros`, formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        this.exito = true;
        this.mongoId = response.data.preRegistro?.id || null;
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || "Error al procesar el pre-registro";
        throw err;
      } finally {
        this.loading = false;
      }
    },

    resetStore() {
      this.$reset();
    }
  },
});
