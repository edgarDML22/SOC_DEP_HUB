import { defineStore } from "pinia";
import api from "@/services/api";

export const useGuestStore = defineStore("guest", {
  state: () => ({
    invitados: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchInvitados(forceRefresh = false) {
      if (this.invitados.length > 0 && !forceRefresh) return;
      this.loading = true;
      try {
        const res = await api.get("/guest-list");
        this.invitados = res.data.data || [];
      } catch (err) {
        console.error(err);
        this.error = "Error cargando invitados";
      } finally {
        this.loading = false;
      }
    },
    async addInvitado(payload) {
      const res = await api.post("/guest-create", payload);
      await this.fetchInvitados(true);
      return res;
    },
    async updateInvitado(id, payload) {
      const res = await api.put(`/guests/${id}`, payload);
      await this.fetchInvitados(true);
      return res;
    },
    async deleteInvitado(id) {
      // ⚠️ ADIÓS A LOS TOASTS AQUÍ. Solo retornamos la respuesta o dejamos que lance el error.
      const res = await api.delete(`/guests/${id}`);
      this.invitados = this.invitados.filter((inv) => inv.id !== id);
      return res;
    },
  },
});