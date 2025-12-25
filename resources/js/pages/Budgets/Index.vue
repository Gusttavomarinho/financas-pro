<template>
    <div>
        <!-- Page header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Orçamentos</h1>
                    <p class="text-gray-500 dark:text-gray-400">Controle seus gastos por categoria</p>
                </div>
                <div class="flex items-center gap-3">
                <!-- Period type toggle -->
                    <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                        <button 
                            @click="setPeriodType('todos')"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                                periodType === 'todos' 
                                    ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Todos
                        </button>
                        <button 
                            @click="setPeriodType('mensal')"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                                periodType === 'mensal' 
                                    ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Mensal
                        </button>
                        <button 
                            @click="setPeriodType('anual')"
                            :class="[
                                'px-3 py-1.5 text-sm font-medium rounded-md transition-colors',
                                periodType === 'anual' 
                                    ? 'bg-white dark:bg-gray-700 text-primary-600 shadow-sm' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Anual
                        </button>
                    </div>
                    <!-- Period selector -->
                    <PeriodSelector 
                        v-model:month="selectedMonth" 
                        v-model:year="selectedYear" 
                        v-model:mode="selectorMode"
                        @change="onPeriodChange"
                    />
                    <button @click="openCreateModal" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Novo Orçamento
                    </button>
                </div>
            </div>
        </div>

        <!-- Dismissable info banner -->
        <DismissableBanner storage-key="budgets-info" color="indigo">
            <strong>Como funciona:</strong> Defina limites e acompanhe seus gastos em tempo real.
            O sistema <strong>nunca bloqueia</strong> um lançamento — apenas alerta quando você está se aproximando do limite.
        </DismissableBanner>

        <!-- ============ SEÇÃO 1: ORÇAMENTO GERAL ============ -->
        <section class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Orçamento Geral
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Controle seus gastos totais do período</p>
                </div>
                <!-- Buttons to create missing budget types (shown in Todos mode or specific mode) -->
                <div class="flex gap-2">
                    <button 
                        v-if="(periodType === 'todos' || periodType === 'mensal') && missingMonthlyBudget" 
                        @click="openGeneralBudgetModalWithType('monthly')" 
                        class="btn-secondary btn-sm"
                    >
                        + Criar Mensal
                    </button>
                    <button 
                        v-if="(periodType === 'todos' || periodType === 'anual') && missingYearlyBudget" 
                        @click="openGeneralBudgetModalWithType('yearly')" 
                        class="btn-secondary btn-sm"
                    >
                        + Criar Anual
                    </button>
                </div>
            </div>

            <!-- General Budget Card (filtered by period type) -->
            <div v-for="budget in filteredGeneralBudgets" :key="budget.id" 
                 class="card mb-4 border-2 cursor-pointer hover:shadow-lg transition-shadow" 
                 :class="budget.status === 'paused' ? 'border-yellow-300 dark:border-yellow-700 opacity-75' : 'border-primary-300 dark:border-primary-700 bg-gradient-to-r from-primary-50/50 to-transparent dark:from-primary-900/20'"
                 @click="openTransactionHistoryModal(budget)">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center"
                             :class="budget.status === 'paused' ? 'bg-yellow-100 dark:bg-yellow-900/50' : 'bg-primary-100 dark:bg-primary-900/50'">
                            <svg class="w-7 h-7" :class="budget.status === 'paused' ? 'text-yellow-600 dark:text-yellow-400' : 'text-primary-600 dark:text-primary-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ budget.name }}
                                <span class="ml-2 text-sm font-normal px-2 py-0.5 rounded-full" 
                                      :class="budget.period_type === 'monthly' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'">
                                    {{ budget.period_type === 'monthly' ? 'Mensal' : 'Anual' }}
                                </span>
                                <span v-if="budget.status === 'paused'" class="ml-2 text-sm text-yellow-600">(Pausado)</span>
                            </h3>
                            <p class="text-sm text-gray-500">
                                {{ formatCurrency(getBudgetSpent(budget)) }} de {{ formatCurrency(budget.limit_value) }}
                                <span v-if="getBudgetCurrentPeriod(budget)" class="ml-2 text-gray-400">• {{ getBudgetPeriodLabel(budget) }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2" @click.stop>
                        <!-- Status Badge with Icon -->
                        <span v-if="getBudgetCurrentPeriod(budget)" :class="getStatusBadgeClass(getBudgetCurrentPeriod(budget)?.status || 'within')">
                            {{ getStatusIcon(getBudgetCurrentPeriod(budget)?.status || 'within') }}
                            {{ getStatusLabel(getBudgetCurrentPeriod(budget)?.status || 'within') }}
                        </span>
                        <!-- Pause/Resume button -->
                        <button 
                            v-if="budget.status === 'active'" 
                            @click="pauseGeneralBudgetById(budget.id)" 
                            class="p-2 text-yellow-500 hover:text-yellow-700 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg"
                            title="Pausar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                        <button 
                            v-else-if="budget.status === 'paused'" 
                            @click="resumeGeneralBudgetById(budget.id)" 
                            class="p-2 text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg"
                            title="Retomar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                        <!-- Edit button -->
                        <button @click="openGeneralBudgetModalForEdit(budget)" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Editar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <!-- Delete button -->
                        <button @click="confirmDeleteGeneralBudget(budget)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg" title="Excluir">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="relative h-5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div 
                        class="absolute left-0 top-0 h-full rounded-full transition-all duration-500"
                        :class="getProgressBarClass(getBudgetCurrentPeriod(budget)?.status || 'within')"
                        :style="{ width: Math.min(getBudgetPercentage(budget), 100) + '%' }"
                    ></div>
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white drop-shadow" v-if="getBudgetPercentage(budget) >= 20">
                        {{ Math.round(getBudgetPercentage(budget)) }}%
                    </span>
                </div>
                <p v-if="getBudgetPercentage(budget) < 20" class="text-sm text-gray-500 mt-2 text-right">{{ Math.round(getBudgetPercentage(budget)) }}% utilizado</p>
            </div>

            <!-- Empty state when no general budget exists at all -->
            <div v-if="filteredGeneralBudgets.length === 0 && periodType !== 'todos'" class="card mb-4 border-2 border-dashed border-gray-200 dark:border-gray-700 text-center py-8">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-gray-500 mb-3">Você ainda não tem um orçamento geral {{ periodType === 'mensal' ? 'mensal' : 'anual' }}</p>
                <button @click="openGeneralBudgetModalWithType(periodType === 'mensal' ? 'monthly' : 'yearly')" class="btn-primary btn-sm">
                    + Criar Orçamento {{ periodType === 'mensal' ? 'Mensal' : 'Anual' }}
                </button>
            </div>

            <!-- Empty state for Todos mode when no budgets exist -->
            <div v-if="filteredGeneralBudgets.length === 0 && periodType === 'todos'" class="card mb-4 border-2 border-dashed border-gray-200 dark:border-gray-700 text-center py-8">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-gray-500 mb-3">Você ainda não tem nenhum orçamento geral</p>
                <div class="flex justify-center gap-2">
                    <button @click="openGeneralBudgetModalWithType('monthly')" class="btn-primary btn-sm">
                        + Criar Mensal
                    </button>
                    <button @click="openGeneralBudgetModalWithType('yearly')" class="btn-secondary btn-sm">
                        + Criar Anual
                    </button>
                </div>
            </div>
        </section>


        <!-- ============ SEÇÃO 2: ORÇAMENTOS POR CATEGORIA ============ -->
        <!-- Shown when periodType is 'todos' or 'mensal' (categories are monthly only) -->
        <section v-if="periodType === 'mensal' || periodType === 'todos'" class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Orçamentos por Categoria
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Limites de gasto por tipo de despesa</p>
                </div>
                <button @click="openCreateModal" class="btn-secondary btn-sm">
                    + Novo Orçamento
                </button>
            </div>

            <!-- Totals summary (Category Budgets) -->
            <div v-if="totals && budgets.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="card text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Orçado</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totals.limit) }}</p>
                </div>
                <div class="card text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Consumido</p>
                    <p class="text-xl font-bold text-red-600">{{ formatCurrency(totals.consumed) }}</p>
                </div>
                <div class="card text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Disponível</p>
                    <p :class="['text-xl font-bold', totals.remaining >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ formatCurrency(totals.remaining) }}
                    </p>
                </div>
                <div class="card text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Uso Geral</p>
                    <p :class="['text-xl font-bold', getPercentageColor(totals.percentage)]">{{ totals.percentage }}%</p>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
            </div>

            <!-- Empty state -->
            <div v-else-if="budgets.length === 0" class="card text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Nenhum orçamento por categoria definido
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    Defina limites específicos para cada tipo de despesa.
                </p>
                <button @click="openCreateModal" class="btn btn-primary">
                    Criar Primeiro Orçamento por Categoria
                </button>
            </div>

            <!-- Budgets list -->
            <div v-else class="space-y-4">
                <div 
                    v-for="budget in budgets" 
                    :key="budget.id"
                    class="card hover:shadow-md transition-shadow cursor-pointer"
                    @click="openCategoryBudgetHistoryModal(budget)"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <!-- Category icon -->
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg"
                                :style="{ backgroundColor: budget.category?.color || '#6B7280' }"
                            >
                                {{ budget.category?.icon || '📊' }}
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ budget.category?.name || 'Categoria' }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatCurrency(budget.consumed_value) }} de {{ formatCurrency(budget.limit_value) }}
                                    <span class="text-xs text-gray-400 ml-1">• Orçamento mensal</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <!-- Status badge with icon -->
                            <span :class="getStatusBadgeClass(budget.status)">
                                {{ getStatusIcon(budget.status) }}
                                {{ getStatusLabel(budget.status) }}
                            </span>
                            <!-- Actions -->
                            <div class="flex items-center gap-1">
                                <button 
                                    @click.stop="openEditModal(budget)"
                                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                    title="Editar"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button 
                                    @click.stop="confirmDelete(budget)"
                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                    title="Remover"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="relative">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full transition-all duration-500 ease-out rounded-full"
                                :class="getProgressBarClass(budget.status)"
                                :style="{ width: Math.min(budget.usage_percentage, 100) + '%' }"
                            ></div>
                        </div>
                        <div class="absolute right-2 top-0 h-4 flex items-center">
                            <span class="text-xs font-bold text-white drop-shadow" v-if="budget.usage_percentage >= 30">
                                {{ budget.usage_percentage }}%
                            </span>
                        </div>
                    </div>
                    <p v-if="budget.usage_percentage < 30" class="text-right text-xs text-gray-500 mt-1">
                        {{ budget.usage_percentage }}% utilizado
                    </p>

                    <!-- Remaining value -->
                    <div class="mt-2 flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ budget.remaining_value >= 0 ? 'Restante' : 'Excedido' }}
                        </span>
                        <span :class="budget.remaining_value >= 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">
                            {{ formatCurrency(Math.abs(budget.remaining_value)) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Message when viewing Anual mode - no category budgets available -->
        <div v-if="periodType === 'anual'" class="card text-center py-8 mt-4 border-2 border-dashed border-gray-200 dark:border-gray-700">
            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-500">Orçamentos por categoria são apenas mensais.</p>
            <p class="text-sm text-gray-400 mt-1">Alterne para <strong>Todos</strong> ou <strong>Mensal</strong> para visualizá-los.</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ isEditing ? 'Editar Orçamento' : 'Novo Orçamento' }}
                        <span class="text-sm font-normal text-gray-500 ml-2">
                            ({{ periodType === 'anual' ? 'Anual' : 'Mensal' }})
                        </span>
                    </h2>
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveBudget" class="p-6 space-y-4">
                    <!-- Category (only for new) -->
                    <div v-if="!isEditing">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Categoria *
                        </label>
                        <select v-model="form.category_id" class="input w-full" required>
                            <option value="">Selecione uma categoria...</option>
                            <option 
                                v-for="category in availableCategories" 
                                :key="category.id" 
                                :value="category.id"
                            >
                                {{ category.icon }} {{ category.name }}
                            </option>
                        </select>
                        <p v-if="availableCategories.length === 0" class="text-sm text-yellow-600 mt-1">
                            Todas as categorias já têm orçamento neste período.
                        </p>
                    </div>

                    <!-- Category display (editing) -->
                    <div v-else>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Categoria
                        </label>
                        <div class="flex items-center gap-2 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span 
                                class="w-8 h-8 rounded-full flex items-center justify-center text-white"
                                :style="{ backgroundColor: editingBudget?.category?.color || '#6B7280' }"
                            >
                                {{ editingBudget?.category?.icon || '📊' }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ editingBudget?.category?.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Limit value -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Limite {{ periodType === 'anual' ? 'anual' : 'mensal' }} *
                        </label>
                        <MoneyInput v-model="form.limit_value" />
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeModal" class="btn btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="btn btn-primary flex-1"
                            :disabled="saving || (!isEditing && availableCategories.length === 0)"
                        >
                            {{ saving ? 'Salvando...' : (isEditing ? 'Salvar' : 'Criar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDeleteModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Remover Orçamento</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Deseja remover o orçamento de <strong>{{ deletingBudget?.category?.name }}</strong>?
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="btn btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="deleteBudget" class="btn bg-red-600 text-white hover:bg-red-700 flex-1">
                            Remover
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- General Budget Modal -->
        <div v-if="showGeneralBudgetModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showGeneralBudgetModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ selectedGeneralBudget ? 'Editar' : 'Criar' }} Orçamento Geral
                    </h3>
                    <button @click="showGeneralBudgetModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveGeneralBudget" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nome (opcional)
                        </label>
                        <input 
                            v-model="generalBudgetForm.name" 
                            type="text" 
                            class="input" 
                            placeholder="Orçamento Geral"
                        />
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Valor Limite *
                        </label>
                        <MoneyInput v-model="generalBudgetForm.limit_value" />
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Periodicidade *
                        </label>
                        <div class="flex gap-3">
                            <label class="flex-1">
                                <input type="radio" v-model="generalBudgetForm.period_type" value="monthly" class="sr-only peer" />
                                <div class="p-3 border rounded-lg text-center cursor-pointer peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <span class="font-medium">Mensal</span>
                                    <p class="text-xs text-gray-500 mt-1">Limite reinicia todo mês</p>
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" v-model="generalBudgetForm.period_type" value="yearly" class="sr-only peer" />
                                <div class="p-3 border rounded-lg text-center cursor-pointer peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <span class="font-medium">Anual</span>
                                    <p class="text-xs text-gray-500 mt-1">Limite para o ano todo</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Info box about limitation -->
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-300 flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                <strong>Orçamento Geral</strong> soma <em>todos</em> os seus gastos, independente de categoria.
                                Você pode ter apenas 1 mensal e 1 anual ativos.
                            </span>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showGeneralBudgetModal = false" class="btn btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary flex-1" :disabled="savingGeneralBudget">
                            {{ savingGeneralBudget ? 'Salvando...' : (selectedGeneralBudget ? 'Salvar' : 'Criar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete General Budget Confirmation Modal -->
        <div v-if="showDeleteGeneralBudgetConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDeleteGeneralBudgetConfirm = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Excluir Orçamento Geral</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Deseja remover o orçamento <strong>{{ budgetToDelete?.name }}</strong>?<br>
                        <span class="text-sm">O histórico de períodos anteriores será perdido.</span>
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteGeneralBudgetConfirm = false" class="btn btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="deleteGeneralBudget" class="btn bg-red-600 text-white hover:bg-red-700 flex-1">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Modal -->
        <div v-if="showTransactionHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showTransactionHistoryModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between p-6 border-b dark:border-gray-700">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            Histórico - {{ historyBudget?.name }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ historyBudget?.period_type === 'monthly' ? 'Mensal' : 'Anual' }}</p>
                    </div>
                    <button @click="showTransactionHistoryModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Period Selector -->
                <div class="px-6 py-3 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Período:</label>
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            <button 
                                v-for="period in historyPeriods" 
                                :key="period.id"
                                @click="selectHistoryPeriod(period)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-colors',
                                    selectedHistoryPeriod?.id === period.id 
                                        ? 'bg-primary-500 text-white' 
                                        : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border'
                                ]"
                            >
                                {{ getHistoryPeriodLabel(period) }}
                                <span v-if="period.status === 'exceeded'" class="ml-1 text-red-200">⚠</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="selectedHistoryPeriod" class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500">
                            Limite: {{ formatCurrency(selectedHistoryPeriod.limit_value_snapshot) }}
                        </span>
                        <span :class="selectedHistoryPeriod.status === 'exceeded' ? 'text-red-500 font-semibold' : 'text-gray-500'">
                            Utilizado: {{ formatCurrency(selectedHistoryPeriod.spent) }} ({{ Math.round(selectedHistoryPeriod.percentage) }}%)
                        </span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div v-if="loadingHistory" class="text-center py-8">
                        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-gray-500 mt-2">Carregando...</p>
                    </div>
                    <div v-else-if="historyTransactions.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Nenhum lançamento neste período
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="tx in historyTransactions" :key="tx.id" 
                             class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg"
                                     :style="{ backgroundColor: tx.category?.color || '#888' }">
                                    {{ tx.category?.icon || '💰' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ tx.description }}</p>
                                    <p class="text-sm text-gray-500">{{ tx.category?.name }} • {{ new Date(tx.date).toLocaleDateString('pt-BR') }}</p>
                                </div>
                            </div>
                            <span class="font-semibold text-red-600">{{ formatCurrency(tx.value) }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Total do período:</span>
                        <span class="text-xl font-bold text-red-600">{{ formatCurrency(selectedHistoryPeriod?.spent || 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Budget History Modal -->
        <div v-if="showCategoryHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCategoryHistoryModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between p-6 border-b dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg"
                             :style="{ backgroundColor: categoryHistoryBudget?.category?.color || '#888' }">
                            {{ categoryHistoryBudget?.category?.icon || '📊' }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                Histórico - {{ categoryHistoryBudget?.category?.name }}
                            </h3>
                            <p class="text-sm text-gray-500">Orçamentos por período</p>
                        </div>
                    </div>
                    <button @click="showCategoryHistoryModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Period Selector -->
                <div class="px-6 py-3 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Período:</label>
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            <button 
                                v-for="period in categoryHistoryList" 
                                :key="period.id"
                                @click="selectCategoryPeriod(period)"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-colors',
                                    selectedCategoryPeriod?.id === period.id 
                                        ? 'bg-primary-500 text-white' 
                                        : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border'
                                ]"
                            >
                                {{ getCategoryPeriodLabel(period) }}
                                <span v-if="period.status === 'exceeded'" class="ml-1 text-red-200">⚠</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="selectedCategoryPeriod" class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500">
                            Limite: {{ formatCurrency(selectedCategoryPeriod.limit_value) }}
                        </span>
                        <span :class="selectedCategoryPeriod.status === 'exceeded' ? 'text-red-500 font-semibold' : 'text-gray-500'">
                            Utilizado: {{ formatCurrency(selectedCategoryPeriod.consumed_value) }} ({{ Math.round(selectedCategoryPeriod.usage_percentage || 0) }}%)
                        </span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div v-if="loadingCategoryHistory" class="text-center py-8">
                        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-gray-500 mt-2">Carregando...</p>
                    </div>
                    <div v-else-if="categoryTransactions.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Nenhum lançamento neste período
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="tx in categoryTransactions" :key="tx.id" 
                             class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg"
                                     :style="{ backgroundColor: tx.category?.color || '#888' }">
                                    {{ tx.category?.icon || '💰' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ tx.description }}</p>
                                    <p class="text-sm text-gray-500">{{ new Date(tx.date).toLocaleDateString('pt-BR') }}</p>
                                </div>
                            </div>
                            <span class="font-semibold text-red-600">{{ formatCurrency(tx.value) }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="flex justify-between items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Total do período:</span>
                        <span class="text-xl font-bold text-red-600">{{ formatCurrency(selectedCategoryPeriod?.consumed_value || 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useBudgetsStore } from '@/stores/budgets';
import { useCategoriesStore } from '@/stores/categories';
import axios from 'axios';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import PeriodSelector from '@/components/Common/PeriodSelector.vue';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';

const budgetsStore = useBudgetsStore();
const categoriesStore = useCategoriesStore();

// Period selection
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const selectorMode = ref('month');
const periodType = ref('todos'); // 'todos', 'mensal' or 'anual'

const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const editingBudget = ref(null);
const deletingBudget = ref(null);
const saving = ref(false);

const form = ref({
    category_id: '',
    limit_value: 0,
});

const loading = computed(() => budgetsStore.loading);
const budgets = computed(() => budgetsStore.budgets);
const totals = computed(() => budgetsStore.totals);
const generalBudgets = ref([]);
const selectedGeneralBudget = ref(null); // For editing/viewing

// Computed: Filter general budgets by current period type selection
const filteredGeneralBudgets = computed(() => {
    if (periodType.value === 'todos') {
        // Show all general budgets
        return generalBudgets.value;
    }
    const type = periodType.value === 'mensal' ? 'monthly' : 'yearly';
    return generalBudgets.value.filter(b => b.period_type === type);
});

// Computed: Check which budget types are missing (for "Todos" view)
const missingMonthlyBudget = computed(() => {
    return !generalBudgets.value.some(b => b.period_type === 'monthly');
});
const missingYearlyBudget = computed(() => {
    return !generalBudgets.value.some(b => b.period_type === 'yearly');
});

// Computed: Check if we already have a general budget for the current period type
const hasGeneralBudgetForCurrentPeriod = computed(() => {
    return filteredGeneralBudgets.value.length > 0;
});

// ========== STATUS HELPER FUNCTIONS ==========
// Unified status functions with icons for better UX

function getStatusIcon(status) {
    const icons = {
        within: '✓',
        ok: '✓',
        warning: '⚠️',
        exceeded: '🔴',
    };
    return icons[status] || '✓';
}

function getStatusLabel(status) {
    const labels = {
        within: 'Dentro do limite',
        ok: 'Dentro do limite',
        warning: 'Atenção',
        exceeded: 'Estourado',
    };
    return labels[status] || 'OK';
}

function getStatusBadgeClass(status) {
    const classes = {
        within: 'badge badge-green',
        ok: 'badge badge-green',
        warning: 'badge badge-yellow',
        exceeded: 'badge badge-red',
    };
    return classes[status] || 'badge badge-green';
}

function getProgressBarClass(status) {
    const classes = {
        within: 'bg-green-500',
        ok: 'bg-green-500',
        warning: 'bg-yellow-500',
        exceeded: 'bg-red-500',
    };
    return classes[status] || 'bg-primary-500';
}

async function loadGeneralBudget() {
    try {
        const response = await axios.get('/api/general-budgets-current');
        // Store both monthly and yearly if they exist
        const data = response.data.data || {};
        generalBudgets.value = [];
        if (data.monthly) generalBudgets.value.push(data.monthly);
        if (data.yearly) generalBudgets.value.push(data.yearly);
    } catch (e) {
        console.error('Error loading general budget:', e);
    }
}

// Helper functions for multiple budgets
function getBudgetCurrentPeriod(budget) {
    if (!budget) return null;
    const periods = budget.periods || [];
    const now = new Date();
    const year = now.getFullYear();
    const month = budget.period_type === 'monthly' ? now.getMonth() + 1 : null;
    return periods.find(p => p.reference_year === year && p.reference_month === month) || periods[periods.length - 1] || null;
}

function getBudgetSpent(budget) {
    const period = getBudgetCurrentPeriod(budget);
    return parseFloat(period?.spent || 0);
}

function getBudgetPercentage(budget) {
    const period = getBudgetCurrentPeriod(budget);
    return parseFloat(period?.percentage || 0);
}

function getBudgetPeriodLabel(budget) {
    const period = getBudgetCurrentPeriod(budget);
    if (!period) return '';
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    if (period.reference_month) {
        return months[period.reference_month - 1] + '/' + period.reference_year;
    }
    return String(period.reference_year);
}

// Computed for allowing creation of second type
const canCreateAnotherGeneralBudget = computed(() => {
    if (generalBudgets.value.length === 0) return false;
    if (generalBudgets.value.length >= 2) return false;
    const existingType = generalBudgets.value[0]?.period_type;
    return existingType === 'monthly' || existingType === 'yearly';
});

const missingGeneralBudgetType = computed(() => {
    if (generalBudgets.value.length === 1) {
        return generalBudgets.value[0].period_type === 'monthly' ? 'yearly' : 'monthly';
    }
    return null;
});

// Pause/Resume functions by ID
async function pauseGeneralBudgetById(id) {
    try {
        await axios.post(`/api/general-budgets/${id}/pause`);
        await loadGeneralBudget();
    } catch (e) {
        console.error('Error pausing budget:', e);
    }
}

async function resumeGeneralBudgetById(id) {
    try {
        await axios.post(`/api/general-budgets/${id}/resume`);
        await loadGeneralBudget();
    } catch (e) {
        console.error('Error resuming budget:', e);
    }
}

// Delete confirmation
const showDeleteGeneralBudgetConfirm = ref(false);
const budgetToDelete = ref(null);

function confirmDeleteGeneralBudget(budget) {
    budgetToDelete.value = budget;
    showDeleteGeneralBudgetConfirm.value = true;
}

async function deleteGeneralBudget() {
    if (!budgetToDelete.value) return;
    try {
        await axios.delete(`/api/general-budgets/${budgetToDelete.value.id}`);
        showDeleteGeneralBudgetConfirm.value = false;
        budgetToDelete.value = null;
        await loadGeneralBudget();
    } catch (e) {
        console.error('Error deleting budget:', e);
    }
}

// Transaction History Modal 
const showTransactionHistoryModal = ref(false);
const historyBudget = ref(null);
const historyTransactions = ref([]);
const loadingHistory = ref(false);
const historyPeriods = ref([]);
const selectedHistoryPeriod = ref(null);

async function openTransactionHistoryModal(budget) {
    historyBudget.value = budget;
    showTransactionHistoryModal.value = true;
    loadingHistory.value = true;
    historyTransactions.value = [];
    
    // Get all periods from budget, ordered by most recent first
    const periods = budget.periods || [];
    historyPeriods.value = [...periods].sort((a, b) => {
        if (a.reference_year !== b.reference_year) return b.reference_year - a.reference_year;
        return (b.reference_month || 0) - (a.reference_month || 0);
    });
    
    // Select current period by default
    const currentPeriod = getBudgetCurrentPeriod(budget);
    if (currentPeriod) {
        selectedHistoryPeriod.value = currentPeriod;
        await loadPeriodTransactions(currentPeriod.id);
    } else if (historyPeriods.value.length > 0) {
        selectedHistoryPeriod.value = historyPeriods.value[0];
        await loadPeriodTransactions(historyPeriods.value[0].id);
    } else {
        selectedHistoryPeriod.value = null;
        historyTransactions.value = [];
        loadingHistory.value = false;
    }
}

async function selectHistoryPeriod(period) {
    if (selectedHistoryPeriod.value?.id === period.id) return;
    selectedHistoryPeriod.value = period;
    await loadPeriodTransactions(period.id);
}

async function loadPeriodTransactions(periodId) {
    loadingHistory.value = true;
    try {
        const response = await axios.get(`/api/general-budget-periods/${periodId}/transactions`);
        historyTransactions.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading transactions:', e);
        historyTransactions.value = [];
    }
    loadingHistory.value = false;
}

function getHistoryPeriodLabel(period) {
    if (!period) return '';
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    if (period.reference_month) {
        return months[period.reference_month - 1] + '/' + period.reference_year;
    }
    return String(period.reference_year);
}

// Category Budget History Modal
const showCategoryHistoryModal = ref(false);
const categoryHistoryBudget = ref(null);
const categoryHistoryList = ref([]);
const selectedCategoryPeriod = ref(null);
const categoryTransactions = ref([]);
const loadingCategoryHistory = ref(false);

async function openCategoryBudgetHistoryModal(budget) {
    categoryHistoryBudget.value = budget;
    showCategoryHistoryModal.value = true;
    loadingCategoryHistory.value = true;
    categoryTransactions.value = [];
    
    try {
        // Load all historical periods for this category
        const response = await axios.get(`/api/budgets/${budget.id}/history`);
        categoryHistoryList.value = response.data.data || [];
        
        // Select current budget by default
        const current = categoryHistoryList.value.find(b => b.id === budget.id);
        if (current) {
            selectedCategoryPeriod.value = current;
            await loadCategoryPeriodTransactions(budget.id);
        } else if (categoryHistoryList.value.length > 0) {
            selectedCategoryPeriod.value = categoryHistoryList.value[0];
            await loadCategoryPeriodTransactions(categoryHistoryList.value[0].id);
        } else {
            selectedCategoryPeriod.value = null;
            loadingCategoryHistory.value = false;
        }
    } catch (e) {
        console.error('Error loading category history:', e);
        categoryHistoryList.value = [];
        loadingCategoryHistory.value = false;
    }
}

async function selectCategoryPeriod(period) {
    if (selectedCategoryPeriod.value?.id === period.id) return;
    selectedCategoryPeriod.value = period;
    await loadCategoryPeriodTransactions(period.id);
}

async function loadCategoryPeriodTransactions(budgetId) {
    loadingCategoryHistory.value = true;
    try {
        const response = await axios.get(`/api/budgets/${budgetId}/transactions`);
        categoryTransactions.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading category transactions:', e);
        categoryTransactions.value = [];
    }
    loadingCategoryHistory.value = false;
}

function getCategoryPeriodLabel(period) {
    if (!period) return '';
    const ref = period.reference_month;
    if (!ref) return '';
    // Format: 2024-12 -> Dez/2024 or 2024 -> 2024
    if (ref.includes('-')) {
        const [year, month] = ref.split('-');
        const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return months[parseInt(month) - 1] + '/' + year;
    }
    return ref;
}

// General Budget Modal state
const showGeneralBudgetModal = ref(false);
const savingGeneralBudget = ref(false);
const generalBudgetForm = ref({
    name: '',
    limit_value: 0,
    period_type: 'monthly',
    include_future_categories: true,
});

function openGeneralBudgetModal() {
    // Reset form for new budget, pre-set type if only one type is missing
    selectedGeneralBudget.value = null;
    generalBudgetForm.value = {
        name: '',
        limit_value: 0,
        period_type: missingGeneralBudgetType.value || 'monthly',
        include_future_categories: true,
    };
    showGeneralBudgetModal.value = true;
}

// Open modal with a specific period type pre-selected
function openGeneralBudgetModalWithType(type) {
    selectedGeneralBudget.value = null;
    generalBudgetForm.value = {
        name: '',
        limit_value: 0,
        period_type: type, // 'monthly' or 'yearly'
        include_future_categories: true,
    };
    showGeneralBudgetModal.value = true;
}

function openGeneralBudgetModalForEdit(budget) {
    selectedGeneralBudget.value = budget;
    generalBudgetForm.value = {
        name: budget.name || '',
        limit_value: parseFloat(budget.limit_value) || 0,
        period_type: budget.period_type || 'monthly',
        include_future_categories: budget.include_future_categories ?? true,
    };
    showGeneralBudgetModal.value = true;
}

async function saveGeneralBudget() {
    savingGeneralBudget.value = true;
    try {
        const payload = {
            name: generalBudgetForm.value.name || 'Orçamento Geral',
            limit_value: generalBudgetForm.value.limit_value,
            period_type: generalBudgetForm.value.period_type,
            include_future_categories: generalBudgetForm.value.include_future_categories,
        };

        if (selectedGeneralBudget.value) {
            // Update existing
            await axios.put(`/api/general-budgets/${selectedGeneralBudget.value.id}`, payload);
        } else {
            // Create new
            await axios.post('/api/general-budgets', payload);
        }

        showGeneralBudgetModal.value = false;
        selectedGeneralBudget.value = null;
        await loadGeneralBudget();
    } catch (e) {
        console.error('Error saving general budget:', e);
        alert(e.response?.data?.message || 'Erro ao salvar orçamento geral');
    } finally {
        savingGeneralBudget.value = false;
    }
}

function getMonthName(month) {
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return months[month - 1];
}

// Current period string (for category budgets - 'todos' is treated as 'mensal')
const currentBudgetPeriodString = computed(() => {
    // Only use year-only format for 'anual' mode
    if (periodType.value === 'anual') {
        return String(selectedYear.value);
    }
    // 'todos' and 'mensal' use month format for category budgets
    return `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`;
});

// Categories that don't have a budget yet for this period
const availableCategories = computed(() => {
    const existingCategoryIds = budgets.value.map(b => b.category_id);
    return categoriesStore.categories.filter(c => !existingCategoryIds.includes(c.id));
});

function setPeriodType(type) {
    periodType.value = type;
    // Update selector mode to match (todos uses month since we show category budgets)
    if (type === 'anual') {
        selectorMode.value = 'year';
    } else {
        selectorMode.value = 'month';
    }
    loadData();
}

async function loadData() {
    // For API calls, 'todos' is treated as 'mensal' since category budgets are monthly
    const apiPeriodType = periodType.value === 'todos' ? 'mensal' : periodType.value;
    budgetsStore.setPeriod(currentBudgetPeriodString.value, apiPeriodType);
    await Promise.all([
        budgetsStore.fetchBudgets(currentBudgetPeriodString.value, apiPeriodType),
        budgetsStore.fetchSummary(currentBudgetPeriodString.value, apiPeriodType),
        categoriesStore.fetchCategories(),
    ]);
}

function onPeriodChange(event) {
    // If user switched mode to year, also switch periodType
    if (event.mode === 'year' && periodType.value !== 'anual') {
        periodType.value = 'anual';
    } else if (event.mode === 'month' && periodType.value !== 'mensal') {
        periodType.value = 'mensal';
    }
    loadData();
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

// Note: getStatusLabel, getStatusBadgeClass, getProgressBarClass are defined earlier (consolidated)

function getPercentageColor(percentage) {
    if (percentage >= 100) return 'text-red-600';
    if (percentage >= 80) return 'text-yellow-600';
    return 'text-green-600';
}

function openCreateModal() {
    form.value = { category_id: '', limit_value: 0 };
    isEditing.value = false;
    editingBudget.value = null;
    showModal.value = true;
}

function openEditModal(budget) {
    form.value = { limit_value: budget.limit_value };
    isEditing.value = true;
    editingBudget.value = budget;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.value = { category_id: '', limit_value: 0 };
}

async function saveBudget() {
    saving.value = true;
    try {
        if (isEditing.value) {
            await budgetsStore.updateBudget(editingBudget.value.id, { limit_value: form.value.limit_value });
        } else {
            await budgetsStore.createBudget(form.value, periodType.value);
        }
        await loadData();
        closeModal();
    } catch (error) {
        // Error handled by store
    } finally {
        saving.value = false;
    }
}

function confirmDelete(budget) {
    deletingBudget.value = budget;
    showDeleteModal.value = true;
}

async function deleteBudget() {
    if (!deletingBudget.value) return;
    await budgetsStore.deleteBudget(deletingBudget.value.id);
    await loadData();
    showDeleteModal.value = false;
    deletingBudget.value = null;
}

// Sync selector mode with period type
watch(periodType, (newType) => {
    selectorMode.value = newType === 'anual' ? 'year' : 'month';
});

onMounted(() => {
    loadData();
    loadGeneralBudget();
});
</script>
