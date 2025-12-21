<template>
    <div>
        <!-- Page header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="text-gray-500 dark:text-gray-400">Visão geral das suas finanças</p>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <RouterLink to="/accounts" class="block">
                <StatCard
                    title="Saldo Total"
                    :value="formatCurrency(stats.totalBalance)"
                    icon="wallet"
                    :trend="stats.balanceTrend"
                    color="primary"
                    clickable
                />
            </RouterLink>
            <RouterLink to="/transactions?type=receita" class="block">
                <StatCard
                    title="Receitas do Mês"
                    :value="formatCurrency(stats.monthIncome)"
                    icon="arrow-up"
                    :trend="stats.incomeTrend"
                    color="green"
                    clickable
                />
            </RouterLink>
            <RouterLink to="/transactions?type=despesa" class="block">
                <StatCard
                    title="Despesas do Mês"
                    :value="formatCurrency(stats.monthExpenses)"
                    icon="arrow-down"
                    :trend="stats.expensesTrend"
                    color="red"
                    clickable
                />
            </RouterLink>
            <RouterLink to="/cards" class="block">
                <StatCard
                    title="Faturas em Aberto"
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
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transações Recentes</h3>
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
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Próximas Faturas</h3>
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
import { ref, onMounted, nextTick } from 'vue';
import { RouterLink } from 'vue-router';
import { Chart, registerables } from 'chart.js';
import axios from 'axios';
import StatCard from '@/components/Common/StatCard.vue';
import { useAccountsStore } from '@/stores/accounts';
import { useCardsStore } from '@/stores/cards';
import { useTransactionsStore } from '@/stores/transactions';

Chart.register(...registerables);

const categoryChartRef = ref(null);
const timelineChartRef = ref(null);

const accountsStore = useAccountsStore();
const cardsStore = useCardsStore();
const transactionsStore = useTransactionsStore();

const stats = ref({
    totalBalance: 0,
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
    try {
        // Load accounts for total balance
        await accountsStore.fetchAccounts();
        stats.value.totalBalance = accountsStore.totalBalance;

        // Load transactions
        await transactionsStore.fetchTransactions();
        recentTransactions.value = transactionsStore.transactions.slice(0, 5);

        // Calculate month income/expenses
        const now = new Date();
        const monthStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];

        const monthTransactions = transactionsStore.transactions.filter(t => {
            return t.date >= monthStart && t.date <= monthEnd;
        });

        stats.value.monthIncome = monthTransactions
            .filter(t => t.type === 'receita')
            .reduce((sum, t) => sum + parseFloat(t.value), 0);

        stats.value.monthExpenses = monthTransactions
            .filter(t => t.type === 'despesa')
            .reduce((sum, t) => sum + parseFloat(t.value), 0);

        // Load cards and invoices
        await cardsStore.fetchCards();

        // Load open invoices from all cards
        const invoicesPromises = cardsStore.cards.map(async card => {
            try {
                await cardsStore.fetchCurrentInvoice(card.id);
                return cardsStore.currentInvoice ? { ...cardsStore.currentInvoice, card } : null;
            } catch (e) {
                return null;
            }
        });

        const invoices = (await Promise.all(invoicesPromises)).filter(i => i && i.status !== 'paga');
        upcomingInvoices.value = invoices;
        stats.value.openInvoices = invoices.reduce((sum, i) => sum + (parseFloat(i.total_value) - parseFloat(i.paid_value)), 0);

        // Category breakdown for chart
        const categoryBreakdown = {};
        monthTransactions
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
    }
}

onMounted(async () => {
    await loadDashboardData();
    await nextTick();
    
    // Category pie chart
    if (categoryChartRef.value) {
        const labels = categoryData.value.labels.length ? categoryData.value.labels : ['Sem dados'];
        const data = categoryData.value.data.length ? categoryData.value.data : [1];
        
        new Chart(categoryChartRef.value, {
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
    
    // Timeline chart - mock data for now
    if (timelineChartRef.value) {
        const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const currentMonth = new Date().getMonth();
        const recentMonths = [];
        for (let i = 5; i >= 0; i--) {
            const idx = (currentMonth - i + 12) % 12;
            recentMonths.push(months[idx]);
        }

        new Chart(timelineChartRef.value, {
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
});
</script>
