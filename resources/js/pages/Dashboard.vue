<template>
    <div>
        <!-- Page header with Period Selector -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="text-gray-500 dark:text-gray-400">Visão financeira do período</p>
                </div>
                <PeriodSelector 
                    v-model:month="selectedMonth" 
                    v-model:year="selectedYear" 
                    v-model:mode="viewMode"
                    @change="onPeriodChange"
                />
            </div>
        </div>

        <!-- Transparency info banner -->
        <DismissableBanner storage-key="dashboard-calc-info" color="blue" class="mb-4">
            <p>💡 Os valores consideram os lançamentos pela data da transação, não pela data de vencimento da fatura.</p>
            <p class="mt-1">💡 O "Saldo Previsto" inclui despesas de faturas futuras já lançadas.</p>
        </DismissableBanner>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <RouterLink to="/accounts" class="block">
                <StatCard
                    title="Saldo Atual"
                    :value="formatCurrency(stats.totalBalance)"
                    icon="wallet"
                    :trend="stats.balanceTrend"
                    color="primary"
                    clickable
                />
            </RouterLink>
            <div class="block">
                <StatCard
                    title="Saldo Previsto"
                    :value="formatCurrency(stats.predictedBalance)"
                    :subtitle="isCurrentOrFutureMonth ? 'Após faturas do período' : 'Período anterior'"
                    icon="chart"
                    color="purple"
                />
            </div>
            <RouterLink :to="incomeFilterUrl" class="block">
                <StatCard
                    title="Receitas do Período"
                    :value="formatCurrency(stats.monthIncome)"
                    icon="arrow-up"
                    :trend="stats.incomeTrend"
                    color="green"
                    clickable
                />
            </RouterLink>
            <RouterLink :to="expenseFilterUrl" class="block">
                <StatCard
                    title="Despesas do Período"
                    :value="formatCurrency(stats.monthExpenses)"
                    icon="arrow-down"
                    :trend="stats.expensesTrend"
                    color="red"
                    clickable
                />
            </RouterLink>
            <RouterLink to="/cards" class="block">
                <StatCard
                    title="Faturas do Período"
                    :value="formatCurrency(stats.openInvoices)"
                    icon="credit-card"
                    color="yellow"
                    clickable
                />
            </RouterLink>
        </div>

        <!-- Charts row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Expenses by category -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Despesas por Categoria</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas ref="categoryChartRef"></canvas>
                </div>
            </div>

            <!-- Balance timeline -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Evolução do Saldo</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas ref="timelineChartRef"></canvas>
                </div>
            </div>
        </div>

        <!-- Bottom row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent transactions -->
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transações do Período</h3>
                    <RouterLink :to="allTransactionsUrl" class="text-sm text-primary-600 hover:text-primary-700">
                        Ver todas
                    </RouterLink>
                </div>
                <div v-if="recentTransactions.length" class="space-y-3">
                    <div
                        v-for="transaction in recentTransactions"
                        :key="transaction.id"
                        @click="openTransactionDetail(transaction)"
                        class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                    >
                        <div :class="getTransactionIconClass(transaction)">
                            <svg v-if="transaction.type === 'transferencia'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <svg v-else-if="transaction.type === 'receita'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ transaction.description }}
                            </p>
                            <p class="text-xs text-gray-500">{{ formatDate(transaction.date) }}</p>
                        </div>
                        <span :class="getTransactionValueClass(transaction)">
                            {{ getTransactionPrefix(transaction) }}{{ formatCurrency(transaction.value) }}
                        </span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    Nenhum lançamento ainda
                </div>
            </div>

            <!-- Faturas do Período - Updated Layout -->
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Faturas do Período</h3>
                    <RouterLink to="/cards" class="text-sm text-primary-600 hover:text-primary-700">
                        Ver cartões
                    </RouterLink>
                </div>

                <!-- Current Invoice (compras do mês) -->
                <div v-if="currentInvoice" class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Fatura Atual</p>
                    <RouterLink
                        :to="`/cards/${currentInvoice.card_id}/invoice`"
                        class="flex items-center gap-3 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors border border-yellow-200 dark:border-yellow-800"
                    >
                        <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ currentInvoice.card?.name || 'Fatura' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ getStatusLabel(currentInvoice.status) }} • Vence {{ formatDate(currentInvoice.due_date) }}
                            </p>
                        </div>
                        <span class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                            {{ formatCurrency(currentInvoice.total_value - currentInvoice.paid_value) }}
                        </span>
                    </RouterLink>
                </div>

                <!-- Previous Invoice (paga/fechada) -->
                <div v-if="previousInvoice">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Fatura Anterior</p>
                    <RouterLink
                        :to="`/cards/${previousInvoice.card_id}/invoice`"
                        class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        <div class="w-10 h-10 rounded-full flex items-center justify-center"
                            :class="previousInvoice.status === 'paga' 
                                ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                            ">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="previousInvoice.status === 'paga'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ previousInvoice.card?.name || 'Fatura' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ getStatusLabel(previousInvoice.status) }}
                            </p>
                        </div>
                        <span class="text-sm font-semibold"
                            :class="previousInvoice.status === 'paga' 
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-gray-600 dark:text-gray-400'
                            ">
                            {{ formatCurrency(previousInvoice.total_value) }}
                        </span>
                    </RouterLink>
                </div>

                <div v-if="!currentInvoice && !previousInvoice" class="text-center py-8 text-gray-500">
                    Nenhuma fatura no período
                </div>
            </div>
        </div>

        <!-- Budget Overview Card -->
        <div class="grid grid-cols-1 gap-6 mt-6">
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Orçamentos do Período</h3>
                    <RouterLink to="/budgets" class="text-sm text-primary-600 hover:text-primary-700">
                        Ver todos
                    </RouterLink>
                </div>

                <!-- General Budget Section (Monthly) -->
                <div v-if="generalBudget" class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Orçamento Mensal</p>
                    <RouterLink v-if="selectedMonthlyPeriod" to="/budgets" class="block p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white text-lg">
                                    📊
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ generalBudget.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatCurrency(generalBudgetSpent) }} de {{ formatCurrency(selectedMonthlyPeriod.limit_value_snapshot || generalBudget.limit_value) }}
                                    </p>
                                </div>
                            </div>
                            <span :class="getGeneralBudgetStatusClass(generalBudgetStatus)">
                                {{ generalBudgetStatus === 'exceeded' ? 'Estourado' : generalBudgetStatus === 'warning' ? 'Atenção' : 'OK' }}
                            </span>
                        </div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full transition-all duration-300"
                                :class="generalBudgetStatus === 'exceeded' ? 'bg-red-500' : generalBudgetStatus === 'warning' ? 'bg-yellow-500' : 'bg-green-500'"
                                :style="{ width: Math.min(generalBudgetPercentage, 100) + '%' }"
                            ></div>
                        </div>
                    </RouterLink>
                    <div v-else class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 text-center text-gray-500 text-sm">
                        Sem dados para este mês
                    </div>
                </div>

                <!-- Category Budgets Section -->
                <div v-if="categoryBudgets.length > 0">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Por Categoria</p>
                    <div class="space-y-2">
                        <RouterLink 
                            v-for="budget in categoryBudgets.slice(0, 3)"
                            :key="budget.id"
                            to="/budgets"
                            class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm"
                                 :style="{ backgroundColor: budget.category?.color || '#6B7280' }">
                                {{ budget.category?.icon || '📊' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ budget.category?.name }}
                                    </p>
                                    <span class="text-xs text-gray-500">
                                        {{ Math.round(budget.usage_percentage || 0) }}%
                                    </span>
                                </div>
                                <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-1">
                                    <div 
                                        class="h-full transition-all duration-300"
                                        :class="budget.status === 'exceeded' ? 'bg-red-500' : budget.status === 'atenção' ? 'bg-yellow-500' : 'bg-green-500'"
                                        :style="{ width: Math.min(budget.usage_percentage || 0, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </RouterLink>
                    </div>
                    <p v-if="categoryBudgets.length > 3" class="text-xs text-gray-400 mt-2 text-center">
                        +{{ categoryBudgets.length - 3 }} outros orçamentos
                    </p>
                </div>

                <!-- Annual Budget Section -->
                <div v-if="annualBudget" class="mt-4 pt-4 border-t dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Orçamento Anual ({{ selectedYear }})</p>
                    <RouterLink v-if="selectedAnnualPeriod" to="/budgets" class="block p-4 rounded-lg bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 hover:from-purple-100 hover:to-indigo-100 dark:hover:from-purple-900/30 dark:hover:to-indigo-900/30 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-white text-lg">
                                    📅
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ annualBudget.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatCurrency(annualBudgetSpent) }} de {{ formatCurrency(selectedAnnualPeriod.limit_value_snapshot || annualBudget.limit_value) }}
                                    </p>
                                </div>
                            </div>
                            <span :class="getGeneralBudgetStatusClass(annualBudgetStatus)">
                                {{ annualBudgetStatus === 'exceeded' ? 'Estourado' : annualBudgetStatus === 'warning' ? 'Atenção' : 'OK' }}
                            </span>
                        </div>
                        <div class="h-2 bg-white/50 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full transition-all duration-300"
                                :class="annualBudgetStatus === 'exceeded' ? 'bg-red-500' : annualBudgetStatus === 'warning' ? 'bg-yellow-500' : 'bg-green-500'"
                                :style="{ width: Math.min(annualBudgetPercentage, 100) + '%' }"
                            ></div>
                        </div>
                    </RouterLink>
                    <div v-else class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 text-center text-gray-500 text-sm">
                        Sem dados para este ano
                    </div>
                </div>

                <div v-if="!generalBudget && !annualBudget && categoryBudgets.length === 0" class="text-center py-8 text-gray-500">
                    <RouterLink to="/budgets" class="text-primary-600 hover:underline">
                        Criar primeiro orçamento →
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Detail Modal -->
    <Teleport to="body">
        <div v-if="showDetailModal && selectedTransaction" 
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" 
             @click.self="showDetailModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto animate-slide-up">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detalhes do Lançamento</h3>
                    <button @click="showDetailModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="text-center py-4">
                        <div :class="getModalIconClass(selectedTransaction)" class="w-16 h-16 mx-auto mb-3">
                            <svg v-if="selectedTransaction.type === 'transferencia'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <svg v-else-if="selectedTransaction.type === 'receita'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            </svg>
                            <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold" :class="getTransactionValueClass(selectedTransaction)">
                            {{ getTransactionPrefix(selectedTransaction) }}{{ formatCurrency(selectedTransaction.value) }}
                        </p>
                        <p class="text-gray-500">{{ selectedTransaction.description }}</p>
                    </div>

                    <div class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tipo</span>
                            <span class="font-medium text-gray-900 dark:text-white capitalize">{{ selectedTransaction.type }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Data</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(selectedTransaction.date) }}</span>
                        </div>
                        <div v-if="selectedTransaction.category" class="flex justify-between">
                            <span class="text-gray-500">Categoria</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ selectedTransaction.category.name }}</span>
                        </div>
                        <div v-if="selectedTransaction.account" class="flex justify-between">
                            <span class="text-gray-500">Conta</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ selectedTransaction.account.name }}</span>
                        </div>
                        <div v-if="selectedTransaction.card" class="flex justify-between">
                            <span class="text-gray-500">Cartão</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ selectedTransaction.card.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                    <RouterLink 
                        :to="`/transactions/${selectedTransaction.id}/edit`" 
                        class="btn-primary w-full justify-center"
                        @click="showDetailModal = false"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar Lançamento
                    </RouterLink>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';
import StatCard from '@/components/Common/StatCard.vue';
import PeriodSelector from '@/components/Common/PeriodSelector.vue';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';
import { useAccountsStore } from '@/stores/accounts';
import { useCardsStore } from '@/stores/cards';
import { useTransactionsStore } from '@/stores/transactions';

Chart.register(...registerables);

// Period state (default to current month/year)
const now = new Date();
const selectedMonth = ref(now.getMonth() + 1);
const selectedYear = ref(now.getFullYear());
const viewMode = ref('month'); // 'month' or 'year'

const categoryChartRef = ref(null);
const timelineChartRef = ref(null);
let categoryChart = null;
let timelineChart = null;

const accountsStore = useAccountsStore();
const cardsStore = useCardsStore();
const transactionsStore = useTransactionsStore();

const stats = ref({
    totalBalance: 0,
    predictedBalance: 0, // New: saldo previsto
    monthIncome: 0,
    monthExpenses: 0,
    openInvoices: 0,
    balanceTrend: 0,
    incomeTrend: 0,
    expensesTrend: 0,
});

const recentTransactions = ref([]);
const upcomingInvoices = ref([]);
const currentInvoice = ref(null);
const previousInvoice = ref(null);
const categoryData = ref({ labels: [], data: [] });
const loading = ref(false);

// Budget data
const generalBudget = ref(null);
const annualBudget = ref(null);
const categoryBudgets = ref([]);

// Selected period for the current view (based on selectedMonth/selectedYear)
const selectedMonthlyPeriod = ref(null);
const selectedAnnualPeriod = ref(null);

// Transaction detail modal
const showDetailModal = ref(false);
const selectedTransaction = ref(null);

// Computed URLs for contextual navigation
const incomeFilterUrl = computed(() => {
    return `/transactions?type=receita&month=${selectedMonth.value}&year=${selectedYear.value}`;
});

const expenseFilterUrl = computed(() => {
    return `/transactions?type=despesa&month=${selectedMonth.value}&year=${selectedYear.value}`;
});

const allTransactionsUrl = computed(() => {
    return `/transactions?month=${selectedMonth.value}&year=${selectedYear.value}`;
});

// Open transaction detail modal
function openTransactionDetail(transaction) {
    selectedTransaction.value = transaction;
    showDetailModal.value = true;
}

// Modal icon class helper
function getModalIconClass(transaction) {
    if (transaction.type === 'transferencia') {
        return 'rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center';
    } else if (transaction.type === 'receita') {
        return 'rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 flex items-center justify-center';
    } else {
        return 'rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center';
    }
}

// General budget computed values (monthly) - uses selected period
const generalBudgetSpent = computed(() => {
    if (!selectedMonthlyPeriod.value) return 0;
    return parseFloat(selectedMonthlyPeriod.value.spent) || 0;
});

const generalBudgetPercentage = computed(() => {
    if (!selectedMonthlyPeriod.value) return 0;
    const limit = parseFloat(selectedMonthlyPeriod.value.limit_value_snapshot) || parseFloat(generalBudget.value?.limit_value) || 1;
    return (generalBudgetSpent.value / limit) * 100;
});

const generalBudgetStatus = computed(() => {
    const pct = generalBudgetPercentage.value;
    if (pct >= 100) return 'exceeded';
    if (pct >= 80) return 'warning';
    return 'ok';
});

// Annual budget computed values - uses selected period
const annualBudgetSpent = computed(() => {
    if (!selectedAnnualPeriod.value) return 0;
    return parseFloat(selectedAnnualPeriod.value.spent) || 0;
});

const annualBudgetPercentage = computed(() => {
    if (!selectedAnnualPeriod.value) return 0;
    const limit = parseFloat(selectedAnnualPeriod.value.limit_value_snapshot) || parseFloat(annualBudget.value?.limit_value) || 1;
    return (annualBudgetSpent.value / limit) * 100;
});

const annualBudgetStatus = computed(() => {
    const pct = annualBudgetPercentage.value;
    if (pct >= 100) return 'exceeded';
    if (pct >= 80) return 'warning';
    return 'ok';
});

function getGeneralBudgetStatusClass(status) {
    const classes = {
        ok: 'text-xs px-2 py-1 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        warning: 'text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
        exceeded: 'text-xs px-2 py-1 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || classes.ok;
}

// Helper function to find the correct period from periods array
function findPeriodForSelection(periods, year, month, isYearly = false) {
    if (!periods || !Array.isArray(periods)) return null;
    
    if (isYearly) {
        // For yearly budgets, find by reference_year only (reference_month is null)
        return periods.find(p => p.reference_year === year && !p.reference_month) || null;
    } else {
        // For monthly budgets, find by reference_year and reference_month
        return periods.find(p => p.reference_year === year && p.reference_month === month) || null;
    }
}

async function loadBudgetData() {
    try {
        // Load general budgets (both monthly and annual)
        const generalResponse = await axios.get('/api/general-budgets-current');
        const budgetsData = generalResponse.data.data || {};
        console.log('General budgets loaded:', budgetsData);
        
        // API returns {monthly: {...}, yearly: {...}} or arrays
        // Handle both formats
        if (Array.isArray(budgetsData)) {
            // Old format: array of budgets
            generalBudget.value = budgetsData.find(b => b.period_type === 'monthly') || null;
            annualBudget.value = budgetsData.find(b => b.period_type === 'annual' || b.period_type === 'yearly') || null;
        } else {
            // New format: {monthly: {...}, yearly: {...}}
            generalBudget.value = budgetsData.monthly || null;
            annualBudget.value = budgetsData.yearly || budgetsData.annual || null;
        }
        
        // Find the correct period for the selected month/year
        if (generalBudget.value && generalBudget.value.periods) {
            selectedMonthlyPeriod.value = findPeriodForSelection(
                generalBudget.value.periods, 
                selectedYear.value, 
                selectedMonth.value, 
                false
            );
            console.log('Selected monthly period:', selectedMonthlyPeriod.value);
        } else {
            selectedMonthlyPeriod.value = null;
        }
        
        // For annual budget, find period for the selected year
        if (annualBudget.value && annualBudget.value.periods) {
            selectedAnnualPeriod.value = findPeriodForSelection(
                annualBudget.value.periods, 
                selectedYear.value, 
                null, 
                true
            );
            console.log('Selected annual period:', selectedAnnualPeriod.value);
        } else {
            selectedAnnualPeriod.value = null;
        }

        // Load category budgets for current period
        const period = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`;
        console.log('Loading category budgets for period:', period);
        const categoryResponse = await axios.get('/api/budgets/summary', { params: { period } });
        categoryBudgets.value = categoryResponse.data.data || [];
        console.log('Category budgets loaded:', categoryBudgets.value);
    } catch (e) {
        console.error('Error loading budget data:', e);
    }
}

function getStatusLabel(status) {
    const labels = {
        aberta: 'Em aberto',
        fechada: 'Fechada',
        parcialmente_paga: 'Parcialmente paga',
        paga: 'Paga',
        vencida: 'Vencida',
    };
    return labels[status] || status;
}


// Computed to check if viewing current or future period
const isCurrentOrFutureMonth = computed(() => {
    const current = new Date();
    
    if (viewMode.value === 'year') {
        // Year mode: check if selected year is current or future
        return selectedYear.value >= current.getFullYear();
    }
    
    // Month mode: check if selected month/year is current or future
    const selectedDate = new Date(selectedYear.value, selectedMonth.value - 1, 1);
    const currentMonthStart = new Date(current.getFullYear(), current.getMonth(), 1);
    return selectedDate >= currentMonthStart;
});

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
    }).format(new Date(date));
}




function getDaysUntilDue(dateStr) {
    if (!dateStr) return null;
    const now = new Date();
    
    // Extrair componentes de data local
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();
    
    // Converter 'Hoje' para UTC ZERADO (00:00:00)
    const utcToday = Date.UTC(currentYear, currentMonth, currentDay);
    
    // Parsing da data de vencimento (YYYY-MM-DD)
    const cleanDate = dateStr.toString().split('T')[0];
    const [dueYear, dueMonth, dueDay] = cleanDate.split('-').map(Number);
    
    // Converter 'Vencimento' para UTC ZERADO (nota: dueMonth no split já vem 1-12, Date.UTC espera 0-11)
    const utcDue = Date.UTC(dueYear, dueMonth - 1, dueDay);
    
    const diffMs = utcDue - utcToday;
    // Exclui dia de início, inclui dia final
    return Math.round(diffMs / (1000 * 60 * 60 * 24)) - 1;
}

function getDueWarningClass(days) {
    if (days === null) return '';
    if (days < -1) return 'text-red-500'; // Vencida
    if (days === -1) return 'text-red-500 animate-pulse'; // Vence hoje!
    if (days <= 2) return 'text-red-500 animate-pulse'; // Crítico (até 3 dias)
    if (days <= 6) return 'text-yellow-500'; // Alerta (até 7 dias)
    return 'text-green-500'; // Normal
}

function getDueWarningText(days) {
    if (days === null) return '';
    if (days < -1) return `Venceu há ${Math.abs(days + 1)} dias`;
    if (days === -1) return 'Vence hoje!';
    if (days === 0) return 'Vence amanhã';
    return `${days} dias`;
}

function getTransactionIconClass(transaction) {
    if (transaction.type === 'transferencia') {
        return 'w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center';
    } else if (transaction.type === 'receita') {
        return 'w-10 h-10 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 flex items-center justify-center';
    }
    return 'w-10 h-10 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center';
}

function getTransactionValueClass(transaction) {
    if (transaction.type === 'transferencia') return 'text-sm font-semibold text-blue-600 dark:text-blue-400';
    if (transaction.type === 'receita') return 'text-sm font-semibold text-green-600 dark:text-green-400';
    return 'text-sm font-semibold text-red-600 dark:text-red-400';
}

function getTransactionPrefix(transaction) {
    if (transaction.type === 'transferencia') return '';
    return transaction.type === 'receita' ? '+' : '-';
}

async function loadDashboardData() {
    loading.value = true;
    try {
        // Calculate period dates based on viewMode
        let periodStart, periodEnd;
        
        if (viewMode.value === 'year') {
            // Year mode: filter entire year
            periodStart = `${selectedYear.value}-01-01`;
            periodEnd = `${selectedYear.value}-12-31`;
        } else {
            // Month mode: filter selected month
            periodStart = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`;
            const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate();
            periodEnd = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
        }

        // Load accounts for total balance
        await accountsStore.fetchAccounts();
        stats.value.totalBalance = accountsStore.totalBalance;

        // Clear any existing filters before loading transactions for dashboard
        // (filters may persist from previous navigation to Transactions page)
        transactionsStore.clearFilters();
        
        // Load transactions
        await transactionsStore.fetchTransactions();
        
        // Filter transactions for selected period
        const periodTransactions = transactionsStore.transactions.filter(t => {
            return t.date >= periodStart && t.date <= periodEnd;
        });

        // Recent transactions = transactions from the selected period
        recentTransactions.value = periodTransactions.slice(0, 5);

        // Calculate period income/expenses
        stats.value.monthIncome = periodTransactions
            .filter(t => t.type === 'receita')
            .reduce((sum, t) => sum + parseFloat(t.value), 0);

        stats.value.monthExpenses = periodTransactions
            .filter(t => t.type === 'despesa')
            .reduce((sum, t) => sum + parseFloat(t.value), 0);

        // Calculate trends (comparison with previous month)
        if (viewMode.value === 'month') {
            // Get previous month dates
            const prevMonth = selectedMonth.value === 1 ? 12 : selectedMonth.value - 1;
            const prevYear = selectedMonth.value === 1 ? selectedYear.value - 1 : selectedYear.value;
            const prevPeriodStart = `${prevYear}-${String(prevMonth).padStart(2, '0')}-01`;
            const prevLastDay = new Date(prevYear, prevMonth, 0).getDate();
            const prevPeriodEnd = `${prevYear}-${String(prevMonth).padStart(2, '0')}-${String(prevLastDay).padStart(2, '0')}`;

            // Filter transactions for previous month
            const prevTransactions = transactionsStore.transactions.filter(t => {
                return t.date >= prevPeriodStart && t.date <= prevPeriodEnd;
            });

            const prevIncome = prevTransactions
                .filter(t => t.type === 'receita')
                .reduce((sum, t) => sum + parseFloat(t.value), 0);

            const prevExpenses = prevTransactions
                .filter(t => t.type === 'despesa')
                .reduce((sum, t) => sum + parseFloat(t.value), 0);

            // Calculate percentage change
            if (prevIncome > 0) {
                stats.value.incomeTrend = Math.round(((stats.value.monthIncome - prevIncome) / prevIncome) * 100);
            } else if (stats.value.monthIncome > 0) {
                stats.value.incomeTrend = 100; // New income where there was none before
            } else {
                stats.value.incomeTrend = 0;
            }

            if (prevExpenses > 0) {
                stats.value.expensesTrend = Math.round(((stats.value.monthExpenses - prevExpenses) / prevExpenses) * 100);
            } else if (stats.value.monthExpenses > 0) {
                stats.value.expensesTrend = 100; // New expenses where there were none before
            } else {
                stats.value.expensesTrend = 0;
            }

            // Calculate balance trend (based on net flow: income - expenses)
            const currentNetFlow = stats.value.monthIncome - stats.value.monthExpenses;
            const prevNetFlow = prevIncome - prevExpenses;
            
            if (Math.abs(prevNetFlow) > 0) {
                stats.value.balanceTrend = Math.round(((currentNetFlow - prevNetFlow) / Math.abs(prevNetFlow)) * 100);
            } else if (currentNetFlow !== 0) {
                stats.value.balanceTrend = currentNetFlow > 0 ? 100 : -100;
            } else {
                stats.value.balanceTrend = 0;
            }
        } else {
            // Year mode: reset trends (different comparison logic would be needed)
            stats.value.incomeTrend = 0;
            stats.value.expensesTrend = 0;
            stats.value.balanceTrend = 0;
        }

        // Load cards and invoices
        await cardsStore.fetchCards();

        // Load invoices and find current + previous for the selected period
        // Current invoice = fatura cujos lançamentos são do mês visualizado (opening_date está no mês)
        // Previous invoice = fatura do mês anterior (paga ou fechada)
        const invoicesPromises = cardsStore.cards.map(async card => {
            try {
                await cardsStore.fetchInvoices(card.id);
                return cardsStore.invoices.map(inv => ({ ...inv, card }));
            } catch (e) {
                return [];
            }
        });

        const invoicesArrays = await Promise.all(invoicesPromises);
        const allInvoices = invoicesArrays.flat();

        if (viewMode.value === 'month') {
            // Month mode: find invoice where opening_date is in the selected month
            const periodStart = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-01`;
            const lastDay = new Date(selectedYear.value, selectedMonth.value, 0).getDate();
            const periodEnd = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${lastDay}`;

            // =====================================================
            // LÓGICA DE COMPETÊNCIA (mês dos lançamentos):
            // A fatura atual é aquela cujo período de compras (period_start a period_end)
            // inclui o mês selecionado, independentemente de quando vence.
            // =====================================================
            
            currentInvoice.value = allInvoices.find(inv => {
                // Prioridade 1: Usar period_start/period_end se disponíveis
                if (inv.period_start && inv.period_end) {
                    // Verifica se o mês selecionado tem interseção com o período da fatura
                    const invStart = inv.period_start.split('T')[0]; // Remove time part
                    const invEnd = inv.period_end.split('T')[0];
                    // O mês selecionado está dentro do período de competência da fatura?
                    return invStart <= periodEnd && invEnd >= periodStart;
                }
                
                // Fallback: reference_month representa o mês de competência
                return inv.reference_month === `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`;
            }) || null;
            
            // FATURA ANTERIOR: fatura com competência no mês anterior
            const prevMonth = selectedMonth.value === 1 ? 12 : selectedMonth.value - 1;
            const prevYear = selectedMonth.value === 1 ? selectedYear.value - 1 : selectedYear.value;
            const prevPeriodStart = `${prevYear}-${String(prevMonth).padStart(2, '0')}-01`;
            const prevLastDay = new Date(prevYear, prevMonth, 0).getDate();
            const prevPeriodEnd = `${prevYear}-${String(prevMonth).padStart(2, '0')}-${prevLastDay}`;
            
            previousInvoice.value = allInvoices.find(inv => {
                // Prioridade 1: Usar period_start/period_end
                if (inv.period_start && inv.period_end) {
                    const invStart = inv.period_start.split('T')[0];
                    const invEnd = inv.period_end.split('T')[0];
                    return invStart <= prevPeriodEnd && invEnd >= prevPeriodStart;
                }
                // Fallback: reference_month
                return inv.reference_month === `${prevYear}-${String(prevMonth).padStart(2, '0')}`;
            }) || null;

            // Calculate open invoices total (from current invoice if exists)
            const openAmount = currentInvoice.value 
                ? parseFloat(currentInvoice.value.total_value || 0) - parseFloat(currentInvoice.value.paid_value || 0)
                : 0;
            stats.value.openInvoices = openAmount;
        } else {
            // Year mode: sum all unpaid invoices for the year
            const yearInvoices = allInvoices.filter(i => {
                // Filter by period_start year if available
                if (i.period_start) {
                    return i.period_start.startsWith(`${selectedYear.value}-`) && i.status !== 'paga';
                }
                return i.reference_month && i.reference_month.startsWith(`${selectedYear.value}-`) && i.status !== 'paga';
            });
            upcomingInvoices.value = yearInvoices.slice(0, 5);
            stats.value.openInvoices = yearInvoices.reduce((sum, i) => sum + (parseFloat(i.total_value || 0) - parseFloat(i.paid_value || 0)), 0);
            
            // In year mode, show most recent current invoice
            currentInvoice.value = yearInvoices[0] || null;
            previousInvoice.value = null;
        }

        // Calculate predicted balance (current balance + projected recurrences - open invoices)
        const currentDate = new Date();
        const isCurrentMonth = selectedMonth.value === (currentDate.getMonth() + 1) && selectedYear.value === currentDate.getFullYear();
        const isFutureMonth = new Date(selectedYear.value, selectedMonth.value - 1, 1) > currentDate;
        
        if (isCurrentMonth || isFutureMonth) {
            // Fetch recurrence projections for the period
            try {
                const projectionResponse = await axios.get('/api/recurring-transactions-projection', {
                    params: {
                        start_date: periodStart,
                        end_date: periodEnd,
                    },
                });
                
                const projection = projectionResponse.data.data;
                // Predicted = balance + projected income - projected expenses - open invoices
                stats.value.predictedBalance = stats.value.totalBalance 
                    + projection.receitas 
                    - projection.despesas 
                    - stats.value.openInvoices;
            } catch (e) {
                // Fallback to simple calculation without recurrences
                stats.value.predictedBalance = stats.value.totalBalance - stats.value.openInvoices;
            }
        } else {
            // For past months, just show the current balance
            stats.value.predictedBalance = stats.value.totalBalance;
        }

        // Category breakdown for chart
        const categoryBreakdown = {};
        periodTransactions
            .filter(t => t.type === 'despesa' && t.category)
            .forEach(t => {
                const catName = t.category.name;
                categoryBreakdown[catName] = (categoryBreakdown[catName] || 0) + parseFloat(t.value);
            });

        categoryData.value = {
            labels: Object.keys(categoryBreakdown),
            data: Object.values(categoryBreakdown),
        };

        // Load budget data
        await loadBudgetData();
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    } finally {
        loading.value = false;
    }
}

// Handle period change
async function onPeriodChange({ month, year }) {
    await loadDashboardData();
    await loadBudgetData();
    await updateCharts();
}

onMounted(async () => {
    await loadDashboardData();
    await nextTick();
    await updateCharts();
});

async function updateCharts() {
    await nextTick();
    
    // Category pie chart
    if (categoryChartRef.value) {
        const labels = categoryData.value.labels.length ? categoryData.value.labels : ['Sem dados'];
        const data = categoryData.value.data.length ? categoryData.value.data : [1];
        
        // Destroy existing chart if any
        if (categoryChart) {
            categoryChart.destroy();
        }
        
        categoryChart = new Chart(categoryChartRef.value, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: [
                        '#22c55e',
                        '#3b82f6',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ef4444',
                        '#06b6d4',
                        '#ec4899',
                        '#6b7280',
                    ],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151',
                        },
                    },
                },
            },
        });
    }
    
    // Timeline chart
    if (timelineChartRef.value) {
        const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        // Build labels for the selected month context
        const recentMonths = [];
        for (let i = 5; i >= 0; i--) {
            const monthIdx = ((selectedMonth.value - 1) - i + 12) % 12;
            recentMonths.push(months[monthIdx]);
        }

        // Destroy existing chart if any
        if (timelineChart) {
            timelineChart.destroy();
        }

        timelineChart = new Chart(timelineChartRef.value, {
            type: 'line',
            data: {
                labels: recentMonths,
                datasets: [{
                    label: 'Saldo',
                    data: [
                        stats.value.totalBalance * 0.7,
                        stats.value.totalBalance * 0.75,
                        stats.value.totalBalance * 0.8,
                        stats.value.totalBalance * 0.85,
                        stats.value.totalBalance * 0.9,
                        stats.value.totalBalance,
                    ],
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        },
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151',
                        },
                    },
                    x: {
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        },
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#374151',
                        },
                    },
                },
            },
        });
    }
}
</script>
