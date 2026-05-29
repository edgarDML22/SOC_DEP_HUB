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
        const res = await api.get("family-member-list");
        this.miembrosFamiliares = res.data.data || [];
      } catch (err) {
        console.error("Error cargando:", err);
        this.error = err.response?.data?.message || "Error al cargar";
      } finally {
        this.loading = false;
      }
    },

    async addMiembroFamiliar(payload) {
      const res = await api.post("family-member-create", payload);
      const nuevo = res.data.data;
      if (nuevo) {
        const qrUrl = res.data.qr_url || "";
        const match = qrUrl.match(/data=([^&]+)/);
        nuevo.codigo_qr = match ? decodeURIComponent(match[1]) : "QR_NO_ENCONTRADO";
        this.miembrosFamiliares.push(nuevo);
      }
      return res;
    },

    async updateMiembroFamiliar(id, payload) {
      const res = await api.put(`family-member/${id}`, payload);
      const actualizado = res.data.data;
      if (actualizado) {
        const idx = this.miembrosFamiliares.findIndex((m) => m.id_miembro === id);
        if (idx !== -1) {
          this.miembrosFamiliares[idx] = {
            ...this.miembrosFamiliares[idx],
            ...actualizado,
          };
        }
      }
      return res;
    },

    async deleteMiembroFamiliar(id) {
      const res = await api.delete(`family-member/${id}`);
      this.miembrosFamiliares = this.miembrosFamiliares.filter(
        (m) => m.id_miembro !== id
      );
      return res;
    },
  },
});
