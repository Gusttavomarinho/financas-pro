import { defineStore } from 'pinia';
import axios from 'axios';
import { useUiStore } from './ui';

export const useRecurringStore = defineStore('recurring', {
    state: () => ({
        recurrings: [],
        loading: false,
        error: null,
    }),

    getters: {
        activeRecurrings: (state) => state.recurrings.filter(r => r.status === 'ativa'),
        pausedRecurrings: (state) => state.recurrings.filter(r => r.status === 'pausada'),
        endedRecurrings: (state) => state.recurrings.filter(r => r.status === 'encerrada'),

        incomeRecurrings: (state) => state.recurrings.filter(r => r.type === 'receita' && r.status === 'ativa'),
        expenseRecurrings: (state) => state.recurrings.filter(r => r.type === 'despesa' && r.status === 'ativa'),
    },

    actions: {
        async fetchRecurrings(status = null) {
            this.loading = true;
            try {
                const params = status ? { status } : {};
                const response = await axios.get('/api/recurring-transactions', { params });
                this.recurrings = response.data.data;
                return this.recurrings;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao carregar recorrências';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createRecurring(data) {
            const uiStore = useUiStore();
            try {
                const response = await axios.post('/api/recurring-transactions', data);
                this.recurrings.push(response.data.data);
                uiStore.showToast(response.data.message, 'success');
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao criar recorrência';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async updateRecurring(id, data) {
            const uiStore = useUiStore();
            try {
                const response = await axios.put(`/api/recurring-transactions/${id}`, data);
                const index = this.recurrings.findIndex(r => r.id === id);
                if (index !== -1) {
                    this.recurrings[index] = response.data.data;
                }
                uiStore.showToast(response.data.message, 'success');
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao atualizar recorrência';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async pauseRecurring(id) {
            const uiStore = useUiStore();
            try {
                const response = await axios.post(`/api/recurring-transactions/${id}/pause`);
                const index = this.recurrings.findIndex(r => r.id === id);
                if (index !== -1) {
                    this.recurrings[index] = response.data.data;
                }
                uiStore.showToast(response.data.message, 'success');
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao pausar recorrência';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async resumeRecurring(id) {
            const uiStore = useUiStore();
            try {
                const response = await axios.post(`/api/recurring-transactions/${id}/resume`);
                const index = this.recurrings.findIndex(r => r.id === id);
                if (index !== -1) {
                    this.recurrings[index] = response.data.data;
                }
                uiStore.showToast(response.data.message, 'success');
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao retomar recorrência';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async endRecurring(id) {
            const uiStore = useUiStore();
            try {
                const response = await axios.post(`/api/recurring-transactions/${id}/end`);
                const index = this.recurrings.findIndex(r => r.id === id);
                if (index !== -1) {
                    this.recurrings[index] = response.data.data;
                }
                uiStore.showToast(response.data.message, 'success');
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao encerrar recorrência';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async deleteRecurring(id) {
            const uiStore = useUiStore();
            try {
                const response = await axios.delete(`/api/recurring-transactions/${id}`);
                this.recurrings = this.recurrings.filter(r => r.id !== id);
                uiStore.showToast(response.data.message, 'success');
                return response.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao excluir recorrência';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        /**
         * Gera uma transação manual.
         * @param {number} id - ID da recorrência
         * @param {boolean} confirmDuplicate - Se true, confirma geração mesmo sendo duplicata
         * @returns {Object} - { success, requires_confirmation, data }
         */
        async generateTransaction(id, confirmDuplicate = false) {
            const uiStore = useUiStore();
            try {
                const response = await axios.post(`/api/recurring-transactions/${id}/generate`, {
                    confirm_duplicate: confirmDuplicate
                });
                const index = this.recurrings.findIndex(r => r.id === id);
                if (index !== -1) {
                    this.recurrings[index] = response.data.data.recurring;
                }
                uiStore.showToast(response.data.message, 'success');
                return { success: true, data: response.data };
            } catch (error) {
                // Verificar se é um conflito que requer confirmação
                if (error.response?.status === 409 && error.response?.data?.requires_confirmation) {
                    return {
                        success: false,
                        requires_confirmation: true,
                        message: error.response.data.message
                    };
                }
                const message = error.response?.data?.message || 'Erro ao gerar transação';
                uiStore.showToast(message, 'error');
                return { success: false, error: message };
            }
        },

        /**
         * Busca detalhes completos de uma recorrência incluindo estado do botão
         */
        async fetchRecurringDetails(id) {
            try {
                const response = await axios.get(`/api/recurring-transactions/${id}`);
                return {
                    recurring: response.data.data,
                    button_state: response.data.button_state,
                    blocked_tooltip: response.data.blocked_tooltip,
                    generation_info: response.data.generation_info,
                };
            } catch (error) {
                console.error('Erro ao carregar detalhes da recorrência:', error);
                return null;
            }
        },
    },
});
