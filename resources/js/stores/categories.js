import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useUiStore } from './ui';

export const useCategoriesStore = defineStore('categories', () => {
    const categories = ref([]);
    const loading = ref(false);

    async function fetchCategories() {
        loading.value = true;
        try {
            const response = await axios.get('/api/categories');
            categories.value = response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar categorias', 'error');
        } finally {
            loading.value = false;
        }
    }

    async function createCategory(data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post('/api/categories', data);
            categories.value.push(response.data.data);
            uiStore.showToast('Categoria criada com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao criar categoria';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function updateCategory(id, data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.put(`/api/categories/${id}`, data);
            const index = categories.value.findIndex(c => c.id === id);
            if (index !== -1) {
                categories.value[index] = response.data.data;
            }
            uiStore.showToast('Categoria atualizada com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao atualizar categoria';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function deleteCategory(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            await axios.delete(`/api/categories/${id}`);
            categories.value = categories.value.filter(c => c.id !== id);
            uiStore.showToast('Categoria removida com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao remover categoria';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    // Helper to get categories by type
    function getCategoriesByType(type) {
        return categories.value.filter(c => c.type === type && !c.parent_id);
    }

    // Helper to get subcategories
    function getSubcategories(parentId) {
        return categories.value.filter(c => c.parent_id === parentId);
    }

    return {
        categories,
        loading,
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory,
        getCategoriesByType,
        getSubcategories,
    };
});
