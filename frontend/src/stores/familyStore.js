import { defineStore } from "pinia";
import api from "@/services/api";

export const useFamilyStore = defineStore("family", {
  state: () => ({
    miembrosFamiliares: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchMiembrosFamiliares(forceRefresh = false) {
      if (this.miembrosFamiliares.length > 0 && !forceRefresh) return;
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get("/family-member-list");
        this.miembrosFamiliares = res.data || [];
      } catch (err) {
        console.error("Error cargando:", err);
        this.error = err.response?.data?.message || "Error al cargar";
      } finally {
        this.loading = false;
      }
    },
    async addMiembroFamiliar(payload) {
      const res = await api.post("v1/family-member-create", payload);
      await this.fetchMiembrosFamiliares(true); 
      return res;
    },
    async updateMiembroFamiliar(id, payload) {
      const res = await api.put(`/family-member/${id}`, payload);
      await this.fetchMiembrosFamiliares(true);
      return res;
    },
    async deleteMiembroFamiliar(id) {
      // ⚠️ ADIÓS A LOS TOASTS AQUÍ.
      const res = await api.delete(`/family-member/${id}`);
      this.miembrosFamiliares = this.miembrosFamiliares.filter((mf) => mf.id !== id);
      return res;
    },
  },
});