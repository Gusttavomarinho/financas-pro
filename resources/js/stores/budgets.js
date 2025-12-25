import { defineStore } from 'pinia';
import axios from 'axios';
import { useUiStore } from './ui';

export const useBudgetsStore = defineStore('budgets', {
    state: () => ({
        budgets: [],
        summary: null,
        totals: null,
        loading: false,
        currentPeriod: new Date().toISOString().substring(0, 7), // YYYY-MM or YYYY
        periodType: 'mensal', // 'mensal' or 'anual'
    }),

    getters: {
        budgetsByStatus: (state) => {
            return {
                ok: state.budgets.filter(b => b.status === 'ok'),
                warning: state.budgets.filter(b => b.status === 'warning'),
                exceeded: state.budgets.filter(b => b.status === 'exceeded'),
            };
        },
    },

    actions: {
        async fetchBudgets(period = null, periodType = null) {
            this.loading = true;
            try {
                const targetPeriod = period || this.currentPeriod;
                const targetType = periodType || this.periodType;
                const response = await axios.get('/api/budgets', {
                    params: {
                        period: targetPeriod,
                        period_type: targetType,
                    },
                });
                this.budgets = response.data.data;
                this.currentPeriod = targetPeriod;
                this.periodType = targetType;
            } catch (error) {
                const uiStore = useUiStore();
                uiStore.showToast('Erro ao carregar orçamentos', 'error');
            } finally {
                this.loading = false;
            }
        },

        async fetchSummary(period = null, periodType = null) {
            try {
                const targetPeriod = period || this.currentPeriod;
                const targetType = periodType || this.periodType;
                const response = await axios.get('/api/budgets/summary', {
                    params: {
                        period: targetPeriod,
                        period_type: targetType,
                    },
                });
                this.summary = response.data.data;
                this.totals = response.data.totals;
                this.currentPeriod = targetPeriod;
                this.periodType = targetType;
            } catch (error) {
                console.error('Error fetching budget summary:', error);
            }
        },

        async createBudget(data, periodType = null) {
            const uiStore = useUiStore();
            // Convert 'todos' to 'mensal' since backend only accepts 'mensal' or 'anual'
            let type = periodType || this.periodType;
            if (type === 'todos') type = 'mensal';
            try {
                const response = await axios.post('/api/budgets', {
                    ...data,
                    reference_month: this.currentPeriod,
                    period_type: type,
                });
                this.budgets.push(response.data.data);
                uiStore.showToast(response.data.message, 'success');
                return response.data.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao criar orçamento';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async updateBudget(id, data) {
            const uiStore = useUiStore();
            try {
                const response = await axios.put(`/api/budgets/${id}`, data);
                const index = this.budgets.findIndex(b => b.id === id);
                if (index !== -1) {
                    this.budgets[index] = response.data.data;
                }
                uiStore.showToast(response.data.message, 'success');
                return response.data.data;
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao atualizar orçamento';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        async deleteBudget(id) {
            const uiStore = useUiStore();
            try {
                await axios.delete(`/api/budgets/${id}`);
                this.budgets = this.budgets.filter(b => b.id !== id);
                uiStore.showToast('Orçamento removido!', 'success');
            } catch (error) {
                const message = error.response?.data?.message || 'Erro ao remover orçamento';
                uiStore.showToast(message, 'error');
                throw error;
            }
        },

        setPeriod(period, type = 'mensal') {
            this.currentPeriod = period;
            this.periodType = type;
        },
    },
});
