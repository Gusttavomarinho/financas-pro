import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useUiStore } from './ui';

export const useCardsStore = defineStore('cards', () => {
    const cards = ref([]);
    const currentCard = ref(null);
    const currentInvoice = ref(null);
    const invoices = ref([]);
    const loading = ref(false);

    const totalCreditUsed = computed(() => {
        return cards.value
            .filter(c => c.status === 'ativo' && c.type !== 'debito')
            .reduce((sum, c) => sum + parseFloat(c.used_limit || 0), 0);
    });

    const totalCreditLimit = computed(() => {
        return cards.value
            .filter(c => c.status === 'ativo' && c.type !== 'debito')
            .reduce((sum, c) => sum + parseFloat(c.credit_limit || 0), 0);
    });

    async function fetchCards() {
        loading.value = true;
        try {
            const response = await axios.get('/api/cards');
            cards.value = response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar cartões', 'error');
        } finally {
            loading.value = false;
        }
    }

    async function fetchCard(id) {
        loading.value = true;
        try {
            const response = await axios.get(`/api/cards/${id}`);
            currentCard.value = response.data.data;
            return response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar cartão', 'error');
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function createCard(data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post('/api/cards', data);
            cards.value.push(response.data.data);
            uiStore.showToast('Cartão criado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao criar cartão';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function updateCard(id, data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.put(`/api/cards/${id}`, data);
            const index = cards.value.findIndex(c => c.id === id);
            if (index !== -1) {
                cards.value[index] = response.data.data;
            }
            uiStore.showToast('Cartão atualizado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao atualizar cartão';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function deleteCard(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            await axios.delete(`/api/cards/${id}`);
            cards.value = cards.value.filter(c => c.id !== id);
            uiStore.showToast('Cartão removido com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao remover cartão';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function fetchCurrentInvoice(cardId) {
        loading.value = true;
        try {
            const response = await axios.get(`/api/cards/${cardId}/invoice`);
            currentInvoice.value = response.data.data;
            return response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar fatura', 'error');
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInvoices(cardId) {
        loading.value = true;
        try {
            const response = await axios.get(`/api/cards/${cardId}/invoices`, {
                params: {
                    upcoming: true,
                    sort_dir: 'asc'
                }
            });
            invoices.value = response.data.data;
            return response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar histórico de faturas', 'error');
            return [];
        } finally {
            loading.value = false;
        }
    }

    async function payInvoice(cardId, data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/cards/${cardId}/pay`, data);
            uiStore.showToast('Pagamento registrado com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao registrar pagamento';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    return {
        cards,
        currentCard,
        currentInvoice,
        invoices,
        loading,
        totalCreditUsed,
        totalCreditLimit,
        fetchCards,
        fetchCard,
        createCard,
        updateCard,
        deleteCard,
        fetchCurrentInvoice,
        fetchInvoices,
        payInvoice,
    };
});
