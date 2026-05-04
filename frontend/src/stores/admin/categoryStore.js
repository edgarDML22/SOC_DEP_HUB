import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useCategoryStore = defineStore("categoriesAdmin", () => {
    const categories = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    const fetchCategories = async (force = false) => {
        if (!force && categories.value.length > 0) return;
        isLoading.value = true;
        error.value = null;
        try {
            const res = await api.get("/disciplinas-categories/all");
            if (res.data.success) {
                categories.value = res.data.data;
            }
        } catch (err) {
            console.error("Error fetching categories:", err);
            error.value = "Error al cargar categorías.";
        } finally {
            isLoading.value = false;
        }
    };

    const createCategory = async (data) => {
        isLoading.value = true;
        try {
            const res = await api.post("/disciplinas-categories/create", data);
            if (res.data.success) {
                await fetchCategories(true);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al crear categoría." };
        } finally {
            isLoading.value = false;
        }
    };

    const updateCategory = async (id, data) => {
        isLoading.value = true;
        try {
            const res = await api.put(`/disciplinas-categories/update/${id}`, data);
            if (res.data.success) {
                await fetchCategories(true);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al actualizar categoría." };
        } finally {
            isLoading.value = false;
        }
    };

    const deleteCategory = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.delete(`/disciplinas-categories/delete/${id}`);
            if (res.data.success) {
                await fetchCategories(true);
                return { success: true };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al eliminar categoría." };
        } finally {
            isLoading.value = false;
        }
    };

    return {
        categories,
        isLoading,
        error,
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory
    };
});
