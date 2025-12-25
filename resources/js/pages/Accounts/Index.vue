<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contas Bancárias</h1>
                <p class="text-gray-500 dark:text-gray-400">Gerencie suas contas e saldos</p>
            </div>
            <RouterLink to="/accounts/create" class="btn-primary">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nova Conta
            </RouterLink>
        </div>

        <!-- Dismissable info banner -->
        <DismissableBanner storage-key="accounts-info" color="blue">
            Aqui você visualiza e gerencia suas contas bancárias. O saldo de cada conta é atualizado automaticamente conforme seus lançamentos.
        </DismissableBanner>

        <!-- Total balance card -->
        <div class="card mb-6 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">Saldo total</p>
                    <p class="text-3xl font-bold">{{ formatCurrency(accountsStore.totalBalance) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="accountsStore.loading" class="text-center py-12">
            <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Accounts grid -->
        <div v-else-if="accountsStore.accounts.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="account in accountsStore.accounts"
                :key="account.id"
                class="card-hover relative"
                :class="{ 'opacity-75 bg-gray-50 dark:bg-gray-800/50': account.status === 'archived' }"
                @click="openAccountDetail(account)"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl font-bold"
                        :style="{ backgroundColor: account.color || '#6366f1' }"
                    >
                        {{ account.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ account.name }}</h3>
                            <span v-if="account.status === 'archived'" class="badge badge-gray text-xs">Arquivada</span>
                            <span v-if="account.exclude_from_totals" class="badge badge-amber text-xs" title="Esta conta não é considerada nos totais">Excluída dos cálculos</span>
                            <button
                                v-if="account.status === 'archived'"
                                @click.stop.prevent="handleUnarchive(account)"
                                class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 px-2 py-0.5 rounded transition-colors ml-2 relative z-10 cursor-pointer pointer-events-auto"
                                title="Reativar conta"
                            >
                                Reativar
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">{{ getTypeLabel(account.type) }}</p>
                        <p class="text-lg font-bold mt-2" :class="getBalanceColor(account.current_balance || account.initial_balance)">
                            {{ formatCurrency(account.current_balance || account.initial_balance) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="card text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhuma conta cadastrada</h3>
            <p class="text-gray-500 mb-4">Adicione sua primeira conta bancária</p>
            <RouterLink to="/accounts/create" class="btn-primary">
                Adicionar Conta
            </RouterLink>
        </div>

        <!-- Account Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedAccount" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto animate-slide-up">
                    <!-- Account header -->
                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="w-16 h-16 rounded-xl flex items-center justify-center text-white text-2xl font-bold"
                            :style="{ backgroundColor: selectedAccount.color || '#6366f1' }"
                        >
                            {{ selectedAccount.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedAccount.name }}</h2>
                                <span v-if="selectedAccount.status === 'archived'" class="badge badge-gray">Arquivada</span>
                            </div>
                            <p class="text-gray-500">{{ getTypeLabel(selectedAccount.type) }}</p>
                        </div>
                        <button @click="showDetailModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Balance -->
                    <div class="card bg-gray-50 dark:bg-gray-700/50 mb-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Saldo atual</p>
                        <p class="text-3xl font-bold" :class="getBalanceColor(selectedAccount.current_balance || selectedAccount.initial_balance)">
                            {{ formatCurrency(selectedAccount.current_balance || selectedAccount.initial_balance) }}
                        </p>
                    </div>

                    <!-- Recent Transactions (Last 30 days) -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center justify-between">
                            <span>Últimos Lançamentos (30 dias)</span>
                            <RouterLink 
                                :to="`/transactions?account_id=${selectedAccount.id}`"
                                class="text-xs text-primary-600 hover:text-primary-700"
                                @click="showDetailModal = false"
                            >
                                Ver todos →
                            </RouterLink>
                        </h3>
                        <div v-if="loadingRecentTransactions" class="text-center py-4">
                            <svg class="animate-spin h-5 w-5 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div v-else-if="recentTransactions.length" class="space-y-2">
                            <div
                                v-for="tx in recentTransactions.slice(0, 5)"
                                :key="tx.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="getTransactionIconClass(tx)">
                                        <svg v-if="tx.type === 'receita'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tx.description }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(tx.date) }}</p>
                                    </div>
                                </div>
                                <span :class="getValueClass(tx)">
                                    {{ tx.type === 'receita' ? '+' : '-' }}{{ formatCurrency(tx.value) }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500 text-sm">
                            Esta conta ainda não possui lançamentos
                        </div>
                    </div>

                    <!-- Account info -->
                    <div class="space-y-3 mb-6">
                        <div v-if="selectedAccount.bank" class="flex justify-between">
                            <span class="text-gray-500">Banco</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ selectedAccount.bank }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Saldo inicial</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedAccount.initial_balance) }}</span>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Histórico de Ações</h3>
                        <Timeline entityType="Account" :entityId="selectedAccount.id" />
                    </div>

                    <!-- Actions -->
                    <div v-if="selectedAccount.status !== 'archived'" class="space-y-3">
                        <RouterLink 
                            :to="`/transactions?account_id=${selectedAccount.id}`"
                            class="btn-primary w-full justify-center"
                            @click="showDetailModal = false"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Ver Extrato Completo
                        </RouterLink>
                        <RouterLink 
                            :to="`/transactions/create?type=receita&account_id=${selectedAccount.id}`"
                            class="btn-success w-full justify-center"
                            @click="showDetailModal = false"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Depositar
                        </RouterLink>
                        <RouterLink 
                            :to="`/accounts/${selectedAccount.id}/edit`" 
                            class="btn-secondary w-full justify-center"
                            @click="showDetailModal = false"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar Conta
                        </RouterLink>
                        <button @click="confirmDelete" class="btn-danger w-full justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Excluir/Arquivar Conta
                        </button>
                    </div>
                    <div v-else class="text-center py-4">
                        <p class="text-sm text-gray-500">Esta conta está arquivada e não pode ser editada.</p>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm mx-4 w-full animate-slide-up">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        {{ deleteInfo.can_delete ? 'Excluir Conta' : 'Arquivar Conta' }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ deleteInfo.message }}
                    </p>
                    <div v-if="deleteInfo.transactions_count > 0" class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 mb-4">
                        <p class="text-sm text-amber-800 dark:text-amber-300">
                            ⚠️ Esta conta possui {{ deleteInfo.transactions_count }} lançamento(s). 
                            O histórico será preservado para relatórios.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="handleDelete" class="btn-danger flex-1">
                            {{ deleteInfo.can_delete ? 'Excluir' : 'Arquivar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Unarchive Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showUnarchiveModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm mx-4 w-full animate-slide-up">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Reativar Conta
                        </h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Deseja reativar a conta <strong>"{{ accountToUnarchive?.name }}"</strong>?
                    </p>
                    <p class="text-sm text-gray-500 mb-4">
                        A conta voltará a aparecer nas opções de lançamento e seu saldo será considerado novamente.
                    </p>
                    <div class="flex gap-3">
                        <button @click="showUnarchiveModal = false" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="confirmUnarchive" class="btn-success flex-1">
                            Reativar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useAccountsStore } from '@/stores/accounts';
import axios from 'axios';
import Timeline from '@/components/Common/Timeline.vue';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';

const accountsStore = useAccountsStore();
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const showUnarchiveModal = ref(false);
const accountToUnarchive = ref(null);
const selectedAccount = ref(null);
const recentTransactions = ref([]);
const loadingRecentTransactions = ref(false);
const deleteInfo = ref({
    can_delete: false,
    transactions_count: 0,
    message: '',
});

const typeLabels = {
    corrente: 'Conta Corrente',
    poupanca: 'Poupança',
    carteira_digital: 'Carteira Digital',
    investimento: 'Investimento',
    caixa: 'Caixa (Dinheiro físico)',
    credito: 'Crédito',
};

function getTypeLabel(type) {
    return typeLabels[type] || type;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    if (!date) return '';
    // Tratar data YYYY-MM-DD como local, sem conversão de fuso
    const [year, month, day] = date.split('T')[0].split('-');
    return `${day}/${month}/${year}`;
}

function getBalanceColor(balance) {
    if (balance > 0) return 'text-green-600';
    if (balance < 0) return 'text-red-600';
    return 'text-gray-900 dark:text-white';
}

function getTransactionIconClass(tx) {
    if (tx.type === 'receita') {
        return 'w-8 h-8 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 flex items-center justify-center';
    }
    return 'w-8 h-8 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center';
}

function getValueClass(tx) {
    if (tx.type === 'receita') {
        return 'text-sm font-semibold text-green-600 dark:text-green-400';
    }
    return 'text-sm font-semibold text-red-600 dark:text-red-400';
}

async function fetchRecentTransactions(accountId) {
    loadingRecentTransactions.value = true;
    try {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        const response = await axios.get('/api/transactions', {
            params: {
                account_id: accountId,
                date_from: thirtyDaysAgo.toISOString().split('T')[0],
                per_page: 10,
            },
        });
        recentTransactions.value = response.data.data || [];
    } catch (error) {
        recentTransactions.value = [];
    } finally {
        loadingRecentTransactions.value = false;
    }
}

async function openAccountDetail(account) {
    selectedAccount.value = account;
    showDetailModal.value = true;
    await fetchRecentTransactions(account.id);
}

async function confirmDelete() {
    showDetailModal.value = false;
    
    // Check if account can be deleted
    try {
        const response = await axios.get(`/api/accounts/${selectedAccount.value.id}/check-delete`);
        deleteInfo.value = response.data;
    } catch (error) {
        deleteInfo.value = {
            can_delete: false,
            transactions_count: 0,
            message: 'Não foi possível verificar o status da conta.',
        };
    }
    
    showDeleteModal.value = true;
}

async function handleDelete() {
    if (selectedAccount.value) {
        await accountsStore.deleteAccount(selectedAccount.value.id);
        showDeleteModal.value = false;
        selectedAccount.value = null;
    }
}

async function handleUnarchive(account) {
    accountToUnarchive.value = account;
    showUnarchiveModal.value = true;
}

async function confirmUnarchive() {
    if (accountToUnarchive.value) {
        await accountsStore.unarchiveAccount(accountToUnarchive.value.id);
        showUnarchiveModal.value = false;
        accountToUnarchive.value = null;
    }
}

onMounted(() => {
    accountsStore.fetchAccounts();
});
</script>
