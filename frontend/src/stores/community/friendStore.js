import { defineStore } from "pinia";
import api from "@/services/api";

export const useFriendStore = defineStore("friend", {
    state: () => ({
        friends: [],
        loading: false,
        error: null,
        loaded: false,
    }),
    actions: {
        reset() {
            this.friends = [];
            this.loading = false;
            this.error = null;
            this.loaded = false;
        },

        async fetchFriends(forceRefresh = false) {
            if (this.loaded && !forceRefresh) return;
            if (this.loading) return;
            
            this.loading = true;
            this.error = null;
            try {
                const res = await api.get("/friends-list");
                this.friends = res.data?.data || res.data || [];
                this.loaded = true;
            } catch (err) {
                this.error = err.response?.data?.message || "Error al cargar";
            } finally {
                this.loading = false;
            }
        },

        async addFriend(payload) {
            const res = await api.post("/friend-add", payload);
            const nueva = res.data?.data;
            if (nueva) {
                this.friends.push({
                    id_amistad:      nueva.id_amistad,
                    id_amigo:        nueva.receptor_id,
                    nombre_amigo:    payload._nombre_receptor ?? '',
                    estado:          'PENDIENTE',
                    solicitado_por_mi: true,
                    created_at:      nueva.created_at,
                });
            } else {
                await this.fetchFriends(true);
            }
            return res;
        },

        async cancelFriend(payload) {
            const originalFriends = [...this.friends];
            this.friends = this.friends.filter(f => f.id_amistad !== payload.id_amistad);
            try {
                return await api.delete("/friend-cancel", { data: payload });
            } catch (err) {
                this.friends = originalFriends;
                throw err;
            }
        },

        async acceptFriend(payload) {
            const friend = this.friends.find(f => f.id_amistad === payload.id_amistad);
            const oldStatus = friend?.estado;
            if (friend) friend.estado = "ACEPTADA";
            
            try {
                return await api.post("/friend-accept", payload);
            } catch (err) {
                if (friend) friend.estado = oldStatus;
                throw err;
            }
        },

        async rejectFriend(payload) {
            const originalFriends = [...this.friends];
            this.friends = this.friends.filter(f => f.id_amistad !== payload.id_amistad);
            try {
                return await api.post("/friend-reject", payload);
            } catch (err) {
                this.friends = originalFriends;
                throw err;
            }
        },

        async removeFriend(payload) {
            const originalFriends = [...this.friends];
            this.friends = this.friends.filter(f => f.id_amistad !== payload.id_amistad);
            try {
                return await api.delete("/friend-remove", { data: payload });
            } catch (err) {
                this.friends = originalFriends;
                throw err;
            }
        },
    },
});
