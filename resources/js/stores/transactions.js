import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useUiStore } from './ui';

export const useTransactionsStore = defineStore('transactions', () => {
    const transactions = ref([]);
    const currentTransaction = ref(null);
    const loading = ref(false);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 0,
    });
    const filters = ref({
        type: '',
        category_id: '',
        account_id: '',
        card_id: '',
        date_from: '',
        date_to: '',
        search: '',
    });

    async function fetchTransactions(page = 1) {
        loading.value = true;
        try {
            const params = {
                page,
                per_page: pagination.value.per_page,
            };

            // Add non-empty filters
            Object.entries(filters.value).forEach(([key, value]) => {
                if (value !== null && value !== '' && value !== undefined) {
                    params[key] = value;
                }
            });

            const response = await axios.get('/api/transactions', { params });
            transactions.value = response.data.data;
            pagination.value = response.data.meta;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar transações', 'error');
        } finally {
            loading.value = false;
        }
    }

    async function fetchTransaction(id) {
        loading.value = true;
        try {
            const response = await axios.get(`/api/transactions/${id}`);
            currentTransaction.value = response.data.data;
            return response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar transação', 'error');
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function createTransaction(data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post('/api/transactions', data);
            uiStore.showToast(response.data.message || 'Lançamento criado com sucesso!', 'success');
            await fetchTransactions();
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao criar lançamento';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function updateTransaction(id, data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.put(`/api/transactions/${id}`, data);
            const index = transactions.value.findIndex(t => t.id === id);
            if (index !== -1) {
                transactions.value[index] = response.data.data;
            }
            uiStore.showToast('Lançamento atualizado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao atualizar lançamento';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function deleteTransaction(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.delete(`/api/transactions/${id}`);
            transactions.value = transactions.value.filter(t => t.id !== id);
            uiStore.showToast(response.data.message || 'Lançamento removido com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao remover lançamento';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function duplicateTransaction(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/transactions/${id}/duplicate`);
            await fetchTransactions();
            uiStore.showToast('Lançamento duplicado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao duplicar lançamento';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function refundTransaction(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/transactions/${id}/refund`);
            await fetchTransactions();
            uiStore.showToast(response.data.message || 'Lançamento estornado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao estornar lançamento';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function partialRefund(id, keepInstallments) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/transactions/${id}/partial-refund`, {
                keep_installments: keepInstallments,
            });
            await fetchTransactions();
            uiStore.showToast(response.data.message || 'Estorno parcial realizado!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao realizar estorno parcial';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function anticipateTransaction(id, installmentIds, discount) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/transactions/${id}/anticipate`, {
                installment_ids: installmentIds,
                discount: discount
            });

            await fetchTransactions();
            uiStore.showToast(response.data.message || 'Parcelas antecipadas com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao antecipar parcelas';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function refundByValue(id, value) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/transactions/${id}/refund-by-value`, { value });
            await fetchTransactions();
            uiStore.showToast(response.data.message || 'Estorno realizado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao realizar estorno';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    function setFilters(newFilters) {
        filters.value = { ...filters.value, ...newFilters };
    }

    function clearFilters() {
        filters.value = {
            type: '',
            category_id: '',
            account_id: '',
            card_id: '',
            date_from: '',
            date_to: '',
            search: '',
        };
    }

    return {
        transactions,
        currentTransaction,
        loading,
        pagination,
        filters,
        fetchTransactions,
        fetchTransaction,
        createTransaction,
        updateTransaction,
        deleteTransaction,
        duplicateTransaction,
        refundTransaction,
        partialRefund,
        anticipateTransaction,
        refundByValue,
        setFilters,
        clearFilters,
    };
});
