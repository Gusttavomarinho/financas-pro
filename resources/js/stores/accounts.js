import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useUiStore } from './ui';

export const useAccountsStore = defineStore('accounts', () => {
    const accounts = ref([]);
    const currentAccount = ref(null);
    const loading = ref(false);

    const totalBalance = computed(() => {
        return accounts.value
            .filter(a => a.status === 'active')
            .reduce((sum, a) => sum + parseFloat(a.current_balance || 0), 0);
    });

    async function fetchAccounts(showArchived = true) {
        loading.value = true;
        try {
            const response = await axios.get('/api/accounts', {
                params: { show_archived: showArchived }
            });
            accounts.value = response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar contas', 'error');
        } finally {
            loading.value = false;
        }
    }

    async function fetchAccount(id) {
        loading.value = true;
        try {
            const response = await axios.get(`/api/accounts/${id}`);
            currentAccount.value = response.data.data;
            return response.data.data;
        } catch (error) {
            const uiStore = useUiStore();
            uiStore.showToast('Erro ao carregar conta', 'error');
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function createAccount(data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post('/api/accounts', data);
            accounts.value.push(response.data.data);
            uiStore.showToast('Conta criada com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao criar conta';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function updateAccount(id, data) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.put(`/api/accounts/${id}`, data);
            const index = accounts.value.findIndex(a => a.id === id);
            if (index !== -1) {
                accounts.value[index] = response.data.data;
            }
            uiStore.showToast('Conta atualizada com sucesso!', 'success');
            return { success: true, data: response.data.data };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao atualizar conta';
            uiStore.showToast(message, 'error');
            return { success: false, errors: error.response?.data?.errors };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function deleteAccount(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.delete(`/api/accounts/${id}`);
            // Recarrega a lista para pegar o status atualizado (arquivado ou excluído)
            await fetchAccounts();
            uiStore.showToast(response.data?.message || 'Conta removida com sucesso!', 'success');
            return { success: true, archived: response.data?.archived };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao remover conta';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    async function unarchiveAccount(id) {
        const uiStore = useUiStore();
        uiStore.setLoading(true);

        try {
            const response = await axios.post(`/api/accounts/${id}/unarchive`);
            // Atualiza a conta na lista local
            const index = accounts.value.findIndex(a => a.id === id);
            if (index !== -1) {
                accounts.value[index] = response.data.data;
            }
            uiStore.showToast(response.data?.message || 'Conta reativada com sucesso!', 'success');
            return { success: true };
        } catch (error) {
            const message = error.response?.data?.message || 'Erro ao reativar conta';
            uiStore.showToast(message, 'error');
            return { success: false };
        } finally {
            uiStore.setLoading(false);
        }
    }

    return {
        accounts,
        currentAccount,
        loading,
        totalBalance,
        fetchAccounts,
        fetchAccount,
        createAccount,
        updateAccount,
        deleteAccount,
        unarchiveAccount,
    };
});
