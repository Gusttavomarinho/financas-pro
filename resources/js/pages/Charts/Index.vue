<template>
    <div>
        <!-- Page header -->
        <div class="mb-6">
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gráficos</h1>
                    <p class="text-gray-500 dark:text-gray-400">Análise financeira avançada</p>
                </div>
                
                <!-- Global Period Selector - same style as Budgets -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1 w-full sm:w-auto overflow-x-auto scrollbar-hide">
                        <button 
                            @click="setPeriod('this_month')"
                            :class="[
                                'flex-shrink-0 sm:flex-shrink px-3 py-2 sm:py-1.5 text-sm font-medium rounded-md transition-colors whitespace-nowrap',
                                isPeriodActive('this_month') 
                                    ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Este Mês
                        </button>
                        <button 
                            @click="setPeriod('last_30')"
                            :class="[
                                'flex-shrink-0 sm:flex-shrink px-3 py-2 sm:py-1.5 text-sm font-medium rounded-md transition-colors whitespace-nowrap',
                                isPeriodActive('last_30') 
                                    ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Últimos 30 dias
                        </button>
                        <button 
                            @click="setPeriod('this_year')"
                            :class="[
                                'flex-shrink-0 sm:flex-shrink px-3 py-2 sm:py-1.5 text-sm font-medium rounded-md transition-colors whitespace-nowrap',
                                isPeriodActive('this_year') 
                                    ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Este Ano
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-gray-50 dark:bg-gray-900 py-4 border-b border-gray-200 dark:border-gray-800 mb-6">
             <div class="flex flex-col gap-4">
                 <!-- Filters Row -->
                 <div class="flex flex-col gap-3">
                    <!-- Account Filter -->
                    <select v-model="filters.account_id" class="input py-2 text-sm w-full">
                        <option value="">Todas as contas</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.name }}
                        </option>
                    </select>
    
                    <!-- Category Filter -->
                    <select v-model="filters.category_id" class="input py-2 text-sm w-full">
                        <option value="">Todas as categorias</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
    
                    <!-- Date Display -->
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 w-full justify-center">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ formatDate(filters.date_from) }} - {{ formatDate(filters.date_to) }}</span>
                    </div>

                     <!-- Advanced Filters Toggle -->
                     <button 
                        @click="showAdvancedFilters = !showAdvancedFilters"
                        class="text-sm font-medium text-primary-600 hover:text-primary-700 flex items-center gap-1 w-full justify-center sm:justify-start"
                    >
                        <span>Filtros avançados</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showAdvancedFilters }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                     </button>
                 </div>

                 <!-- Advanced Filters Panel -->
                 <div v-show="showAdvancedFilters" class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Recurrence -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Tipo de Despesa</label>
                        <select v-model="filters.is_recurring" class="input text-sm w-full">
                            <option value="">Todos</option>
                            <option value="true">Fixas (Recorrentes)</option>
                            <option value="false">Variáveis</option>
                        </select>
                    </div>

                    <!-- Payment Methods -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Método de Pgto</label>
                        <select v-model="filters.payment_method" class="input text-sm w-full">
                            <option value="">Todos</option>
                            <option value="credito">Crédito</option>
                            <option value="debito">Débito</option>
                            <option value="pix">PIX</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>

                     <!-- Installments -->
                     <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Parcelamento</label>
                        <select v-model="filters.is_installment" class="input text-sm w-full">
                            <option value="">Todos</option>
                            <option value="true">Parcelado</option>
                            <option value="false">À vista</option>
                        </select>
                    </div>

                    <!-- Value Range -->
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase">Valor</label>
                        <div class="flex gap-2">
                            <input v-model="filters.min_value" type="number" placeholder="Min" class="input text-sm w-full" />
                            <input v-model="filters.max_value" type="number" placeholder="Max" class="input text-sm w-full" />
                        </div>
                    </div>
                 </div>
             </div>
        </div>

        <!-- Section Tabs - same style as Budgets period toggle -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
            <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1 w-full sm:w-auto overflow-x-auto">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'flex-1 sm:flex-none px-4 sm:px-3 py-2 sm:py-1.5 text-sm font-medium rounded-md transition-colors whitespace-nowrap',
                        activeTab === tab.id
                            ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'
                    ]"
                >
                    {{ tab.name }}
                </button>
            </div>
        </div>

        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
            <!-- KPI Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Savings Rate KPI -->
                <SavingsRate :filters="filters" />
                
                <!-- Future KPIs can go here -->
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Expenses by Category -->
                <ExpensesByCategory :filters="filters" />

                <!-- Fixed vs Variable -->
                <FixedVsVariableExpenses :filters="filters" />

                <!-- Balance Evolution (Full width on large screens if needed, or 2 cols) -->
                <div class="lg:col-span-2">
                    <BalanceEvolution :filters="filters" />
                </div>

                <!-- Income vs Expenses -->
                <div class="lg:col-span-2">
                    <IncomeVsExpenses :filters="filters" />
                </div>
            </div>
        </div>

        <!-- Expenses Tab -->
        <div v-if="activeTab === 'expenses'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                 <!-- Expenses by Category (Existing) -->
                <ExpensesByCategory :filters="filters" />

                <!-- Expenses by Account (New) -->
                <ExpensesByAccount :filters="filters" />

                <!-- Monthly Expenses Evolution (New) -->
                <div class="lg:col-span-2">
                    <MonthlyExpensesEvolution :filters="filters" />
                </div>
            </div>
        </div>

        <!-- Income Tab -->
        <div v-if="activeTab === 'income'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Income by Category -->
                <IncomeByCategory :filters="filters" />

                <!-- Monthly Income Evolution -->
                <div class="lg:col-span-2">
                    <MonthlyIncomeEvolution :filters="filters" />
                </div>
            </div>
        </div>
        <!-- Credit Tab -->
        <div v-if="activeTab === 'credit'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Limit Evolution (Full Width) -->
                <div class="lg:col-span-2">
                    <CreditLimitEvolution />
                </div>

                <!-- Future Commitment -->
                <FutureCommitment />

                <!-- Top Cards -->
                <TopCards />
            </div>
        </div>

        <!-- Planning Tab -->
        <div v-if="activeTab === 'planning'" class="space-y-8">
            <!-- Budgets Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 dark:border-gray-700">Orçamentos do Mês</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Budget vs Actual (Full on mobile, 2 cols on lg) -->
                    <div class="col-span-1 lg:col-span-1">
                        <BudgetVsActual :filters="filters" />
                    </div>

                    <!-- Consumption (Donut) -->
                    <BudgetConsumption :filters="filters" />

                    <!-- Alerts (List) -->
                    <BudgetAlerts :filters="filters" />
                </div>
            </div>

            <!-- Goals Section -->
            <div>
                 <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 dark:border-gray-700 mb-4">Meus Objetivos</h3>
                 <GoalsProgress />
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCategoriesStore } from '@/stores/categories';
import { useAccountsStore } from '@/stores/accounts';
import { useFilters } from '@/composables/useFilters';
import ExpensesByCategory from '@/components/Charts/ExpensesByCategory.vue';
import BalanceEvolution from '@/components/Charts/BalanceEvolution.vue';
import IncomeVsExpenses from '@/components/Charts/IncomeVsExpenses.vue';
import SavingsRate from '@/components/Charts/SavingsRate.vue';
import FixedVsVariableExpenses from '@/components/Charts/FixedVsVariableExpenses.vue';
import ExpensesByAccount from '@/components/Charts/ExpensesByAccount.vue';
import MonthlyExpensesEvolution from '@/components/Charts/MonthlyExpensesEvolution.vue';
import IncomeByCategory from '@/components/Charts/IncomeByCategory.vue';
import MonthlyIncomeEvolution from '@/components/Charts/MonthlyIncomeEvolution.vue';
import CreditLimitEvolution from '@/components/Charts/CreditLimitEvolution.vue';
import FutureCommitment from '@/components/Charts/FutureCommitment.vue';
import TopCards from '@/components/Charts/TopCards.vue';
import BudgetVsActual from '@/components/Charts/BudgetVsActual.vue';
import BudgetConsumption from '@/components/Charts/BudgetConsumption.vue';
import BudgetAlerts from '@/components/Charts/BudgetAlerts.vue';
import GoalsProgress from '@/components/Charts/GoalsProgress.vue';

const categoriesStore = useCategoriesStore();
const accountsStore = useAccountsStore();

const categories = computed(() => categoriesStore.categories);
const accounts = computed(() => accountsStore.accounts);
const showAdvancedFilters = ref(false);
const activeTab = ref('overview');

const tabs = [
    { id: 'overview', name: 'Visão Geral' },
    { id: 'expenses', name: 'Despesas' },
    { id: 'income', name: 'Receitas' },
    { id: 'credit', name: 'Crédito' }, // Future
    { id: 'planning', name: 'Planejamento' }, // Future
];

// Define reactive filters object with new fields
const filtersState = ref({
    account_id: '',
    category_id: '',
    date_from: '',
    date_to: '',
    type: '',
    is_recurring: '',
    is_installment: '',
    payment_method: '',
    min_value: '',
    max_value: '',
});

// useFilters syncs with URL
const { isInitialized } = useFilters(
    filtersState, 
    () => {}, 
    { 
        account_id: '', 
        category_id: '', 
        date_from: '', 
        date_to: '',
        type: '',
        is_recurring: '',
        is_installment: '',
        payment_method: '',
        min_value: '',
        max_value: '',
    }
);

const filters = filtersState; // Expose as 'filters' for template consistency

onMounted(async () => {
    await Promise.all([
        categoriesStore.fetchCategories(),
        accountsStore.fetchAccounts(),
    ]);

    // Set default period if empty
    if (!filters.value.date_from || !filters.value.date_to) {
        setPeriod('this_month');
    }
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    // dateStr is YYYY-MM-DD
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
}

const formatDateISO = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

function setPeriod(period) {
    const today = new Date();
    let start, end;

    if (period === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (period === 'last_30') {
        end = today;
        start = new Date();
        start.setDate(today.getDate() - 30);
    } else if (period === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    }

    filters.value.date_from = formatDateISO(start);
    filters.value.date_to = formatDateISO(end);
}

function isPeriodActive(period) {
    const today = new Date();
    let start, end;

    if (period === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (period === 'last_30') {
        end = today;
        start = new Date();
        start.setDate(today.getDate() - 30);
    } else if (period === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    }

    return filters.value.date_from === formatDateISO(start) &&
           filters.value.date_to === formatDateISO(end);
}
</script>
