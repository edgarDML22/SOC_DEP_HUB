import { defineStore } from "pinia";
import api from "@/services/api";

export const useFriendStore = defineStore("friend", {
    state: () => ({
        friends: [],
        loading: false,
        error: null,
    }),
    actions: {
        // Limpia el store — llamar al hacer logout
        reset() {
            this.friends = [];
            this.loading = false;
            this.error = null;
        },
        async fetchFriends(forceRefresh = true) {
            // Sin cache: siempre recarga para garantizar datos frescos por usuario
            if (this.loading) return; // evita llamadas concurrentes
            this.loading = true;
            this.error = null;
            try {
                const res = await api.get("/friends-list");
                this.friends = res.data?.data || res.data || [];
            } catch (err) {
                console.error("Error cargando amistades:", err);
                this.error = err.response?.data?.message || "Error al cargar";
            } finally {
                this.loading = false;
            }
        },
        async addFriend(payload) {
            const res = await api.post("/friend-add", payload);
            await this.fetchFriends();
            return res;
        },
        async acceptFriend(payload) {
            const res = await api.post("/friend-accept", payload);
            await this.fetchFriends();
            return res;
        },
        async rejectFriend(payload) {
            const res = await api.post("/friend-reject", payload);
            await this.fetchFriends();
            return res;
        },
        async removeFriend(payload) {
            const res = await api.delete("/friend-remove", { data: payload });
            await this.fetchFriends();
            return res;
        },
    },
});