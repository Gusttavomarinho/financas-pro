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
            <RouterLink to="/transactions?type=receita" class="block">
                <StatCard
                    title="Receitas do Período"
                    :value="formatCurrency(stats.monthIncome)"
                    icon="arrow-up"
                    :trend="stats.incomeTrend"
                    color="green"
                    clickable
                />
            </RouterLink>
            <RouterLink to="/transactions?type=despesa" class="block">
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
                    <RouterLink to="/transactions" class="text-sm text-primary-600 hover:text-primary-700">
                        Ver todas
                    </RouterLink>
                </div>
                <div v-if="recentTransactions.length" class="space-y-3">
                    <RouterLink
                        v-for="transaction in recentTransactions"
                        :key="transaction.id"
                        :to="`/transactions/${transaction.id}/edit`"
                        class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
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
                    </RouterLink>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    Nenhum lançamento ainda
                </div>
            </div>

            <!-- Upcoming bills / invoices -->
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Faturas do Período</h3>
                    <RouterLink to="/cards" class="text-sm text-primary-600 hover:text-primary-700">
                        Ver cartões
                    </RouterLink>
                </div>
                <div v-if="upcomingInvoices.length" class="space-y-3">
                    <RouterLink
                        v-for="invoice in upcomingInvoices"
                        :key="invoice.id"
                        :to="`/cards/${invoice.card_id}/invoice`"
                        class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ invoice.card?.name || 'Fatura' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Vencimento: {{ formatDate(invoice.due_date) }}
                                <span :class="['ml-1 font-bold', getDueWarningClass(getDaysUntilDue(invoice.due_date))]">
                                    ({{ getDueWarningText(getDaysUntilDue(invoice.due_date)) }})
                                </span>
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                            {{ formatCurrency(invoice.total_value - invoice.paid_value) }}
                        </span>
                    </RouterLink>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    Nenhuma fatura pendente
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';
import StatCard from '@/components/Common/StatCard.vue';
import PeriodSelector from '@/components/Common/PeriodSelector.vue';
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
const categoryData = ref({ labels: [], data: [] });
const loading = ref(false);

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
    const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
    
    return diffDays;
}

function getDueWarningClass(days) {
    if (days === null) return '';
    if (days < 0) return 'text-red-500'; // Vencida
    if (days <= 3) return 'text-red-500 animate-pulse'; // Crítico
    if (days <= 7) return 'text-yellow-500'; // Alerta
    return 'text-green-500'; // Normal
}

function getDueWarningText(days) {
    if (days === null) return '';
    if (days < 0) return `Venceu há ${Math.abs(days)} dias`;
    if (days === 0) return 'Vence hoje!';
    if (days === 1) return 'Vence amanhã';
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

        // Load cards and invoices
        await cardsStore.fetchCards();

        // Load invoices for the selected period
        const invoicesPromises = cardsStore.cards.map(async card => {
            try {
                await cardsStore.fetchInvoices(card.id);
                
                if (viewMode.value === 'year') {
                    // Year mode: get all invoices for the year
                    const yearInvoices = cardsStore.invoices.filter(i => 
                        i.reference_month && i.reference_month.startsWith(`${selectedYear.value}-`)
                    );
                    return yearInvoices.map(inv => ({ ...inv, card }));
                } else {
                    // Month mode: find invoice matching the selected period
                    const periodReference = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`;
                    const periodInvoice = cardsStore.invoices.find(i => i.reference_month === periodReference);
                    return periodInvoice ? [{ ...periodInvoice, card }] : [];
                }
            } catch (e) {
                return [];
            }
        });

        const invoicesArrays = await Promise.all(invoicesPromises);
        const allInvoices = invoicesArrays.flat().filter(i => i && i.status !== 'paga');
        upcomingInvoices.value = allInvoices.slice(0, 5); // Limit for UI
        stats.value.openInvoices = allInvoices.reduce((sum, i) => sum + (parseFloat(i.total_value || 0) - parseFloat(i.paid_value || 0)), 0);

        // Calculate predicted balance (current balance - future expenses - open invoices)
        const currentDate = new Date();
        const isCurrentMonth = selectedMonth.value === (currentDate.getMonth() + 1) && selectedYear.value === currentDate.getFullYear();
        const isFutureMonth = new Date(selectedYear.value, selectedMonth.value - 1, 1) > currentDate;
        
        if (isCurrentMonth || isFutureMonth) {
            // For current/future months, predicted = balance - pending expenses - open invoices
            stats.value.predictedBalance = stats.value.totalBalance - stats.value.openInvoices;
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
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    } finally {
        loading.value = false;
    }
}

// Handle period change
async function onPeriodChange({ month, year }) {
    await loadDashboardData();
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
