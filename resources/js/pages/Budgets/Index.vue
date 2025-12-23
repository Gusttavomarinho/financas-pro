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
            <strong>Como funciona:</strong> Defina limites por categoria e acompanhe seus gastos em tempo real.
            O sistema <strong>nunca bloqueia</strong> um lançamento — apenas alerta quando você está se aproximando do limite.
            <span v-if="periodType === 'anual'" class="block mt-1">
                <strong>Modo anual:</strong> O consumo é calculado sobre todas as despesas do ano selecionado.
            </span>
        </DismissableBanner>

        <!-- General Budget Card -->
        <div v-if="generalBudget" class="card mb-6 border-2 border-primary-200 dark:border-primary-800">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ generalBudget.name }}
                            <span v-if="generalBudget.status === 'paused'" class="ml-2 text-sm text-yellow-600">(Pausado)</span>
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ formatCurrency(currentPeriodSpent) }} de {{ formatCurrency(generalBudget.limit_value) }}
                            <span class="ml-2">• {{ generalBudget.period_type === 'monthly' ? 'Mensal' : 'Anual' }}</span>
                            <span v-if="currentPeriod" class="ml-2 text-gray-400">• {{ currentPeriodLabel }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="currentPeriod" :class="getGeneralBudgetStatusClass(currentPeriod.status)">
                        {{ getGeneralBudgetStatusLabel(currentPeriod.status) }}
                    </span>
                    <!-- Pause/Resume button -->
                    <button 
                        v-if="generalBudget.status === 'active'" 
                        @click="pauseGeneralBudget" 
                        class="p-2 text-yellow-500 hover:text-yellow-700 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg"
                        title="Pausar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <button 
                        v-else-if="generalBudget.status === 'paused'" 
                        @click="resumeGeneralBudget" 
                        class="p-2 text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg"
                        title="Retomar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <!-- Edit button -->
                    <button @click="openGeneralBudgetModal" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" title="Editar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Progress bar -->
            <div class="relative h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div 
                    class="absolute left-0 top-0 h-full rounded-full transition-all duration-500"
                    :class="getGeneralProgressBarClass(currentPeriod?.status || 'ok')"
                    :style="{ width: Math.min(currentPeriodPercentage, 100) + '%' }"
                ></div>
            </div>
            <p class="text-sm text-gray-500 mt-2 text-right">{{ Math.round(currentPeriodPercentage) }}% utilizado</p>
        </div>

        <!-- No General Budget - Option to create -->
        <div v-else class="card mb-6 border-2 border-dashed border-gray-200 dark:border-gray-700 text-center py-6">
            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-gray-500 mb-3">Defina um orçamento geral para controlar seus gastos totais</p>
            <button @click="openGeneralBudgetModal" class="btn-primary btn-sm">
                + Criar Orçamento Geral
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
        <div v-else-if="budgets.length === 0" class="card text-center py-12">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Nenhum orçamento {{ periodType === 'anual' ? 'anual' : 'mensal' }} definido
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                Comece criando um orçamento {{ periodType === 'anual' ? 'anual' : 'mensal' }} para uma categoria.
            </p>
            <button @click="openCreateModal" class="btn btn-primary">
                Criar Primeiro Orçamento
            </button>
        </div>

        <!-- Budgets list -->
        <div v-else class="space-y-4">
            <div 
                v-for="budget in budgets" 
                :key="budget.id"
                class="card hover:shadow-md transition-shadow"
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
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Status badge -->
                        <span :class="getStatusBadgeClass(budget.status)">
                            {{ getStatusLabel(budget.status) }}
                        </span>
                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            <button 
                                @click="openEditModal(budget)"
                                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                title="Editar"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button 
                                @click="confirmDelete(budget)"
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

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
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
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
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
        <div v-if="showGeneralBudgetModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ generalBudget ? 'Editar' : 'Criar' }} Orçamento Geral
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

                    <!-- Include all categories -->
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <input type="checkbox" v-model="generalBudgetForm.include_future_categories" id="include_future" class="w-4 h-4" />
                        <label for="include_future" class="text-sm text-gray-700 dark:text-gray-300">
                            Incluir automaticamente novas categorias
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showGeneralBudgetModal = false" class="btn btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary flex-1" :disabled="savingGeneralBudget">
                            {{ savingGeneralBudget ? 'Salvando...' : (generalBudget ? 'Salvar' : 'Criar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useBudgetsStore } from '@/stores/budgets';
import { useCategoriesStore } from '@/stores/categories';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import PeriodSelector from '@/components/Common/PeriodSelector.vue';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';

const budgetsStore = useBudgetsStore();
const categoriesStore = useCategoriesStore();

// Period selection
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const selectorMode = ref('month');
const periodType = ref('mensal'); // 'mensal' or 'anual'

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
const generalBudget = ref(null);

// General Budget helper functions
function getGeneralBudgetStatusClass(status) {
    const classes = {
        within: 'badge badge-green',
        warning: 'badge badge-yellow',
        exceeded: 'badge badge-red',
    };
    return classes[status] || 'badge';
}

function getGeneralBudgetStatusLabel(status) {
    const labels = {
        within: 'Dentro do limite',
        warning: 'Atenção',
        exceeded: 'Estourado',
    };
    return labels[status] || status;
}

function getGeneralProgressBarClass(status) {
    const classes = {
        within: 'bg-green-500',
        warning: 'bg-yellow-500',
        exceeded: 'bg-red-500',
    };
    return classes[status] || 'bg-primary-500';
}

async function loadGeneralBudget() {
    try {
        const response = await fetch('/api/general-budgets-current', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            const data = await response.json();
            // Show monthly budget if available, otherwise annual
            generalBudget.value = data.data?.monthly || data.data?.annual || null;
        }
    } catch (e) {
        console.error('Error loading general budget:', e);
    }
}

// Computed properties for current period
const currentPeriod = computed(() => {
    if (!generalBudget.value) return null;
    // Find current period from periods array
    const periods = generalBudget.value.periods || [];
    const now = new Date();
    const year = now.getFullYear();
    const month = generalBudget.value.period_type === 'monthly' ? now.getMonth() + 1 : null;
    return periods.find(p => p.reference_year === year && p.reference_month === month) || null;
});

const currentPeriodSpent = computed(() => {
    return parseFloat(currentPeriod.value?.spent || 0);
});

const currentPeriodPercentage = computed(() => {
    return parseFloat(currentPeriod.value?.percentage || 0);
});

const currentPeriodLabel = computed(() => {
    if (!currentPeriod.value) return '';
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    if (currentPeriod.value.reference_month) {
        return months[currentPeriod.value.reference_month - 1] + '/' + currentPeriod.value.reference_year;
    }
    return String(currentPeriod.value.reference_year);
});

// Pause/Resume functions
async function pauseGeneralBudget() {
    if (!generalBudget.value) return;
    try {
        const response = await fetch(`/api/general-budgets/${generalBudget.value.id}/pause`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            await loadGeneralBudget();
        }
    } catch (e) {
        console.error('Error pausing budget:', e);
    }
}

async function resumeGeneralBudget() {
    if (!generalBudget.value) return;
    try {
        const response = await fetch(`/api/general-budgets/${generalBudget.value.id}/resume`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            await loadGeneralBudget();
        }
    } catch (e) {
        console.error('Error resuming budget:', e);
    }
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
    // If editing, populate form with existing values
    if (generalBudget.value) {
        generalBudgetForm.value = {
            name: generalBudget.value.name || '',
            limit_value: parseFloat(generalBudget.value.limit_value) || 0,
            period_type: generalBudget.value.period_type || 'monthly',
            include_future_categories: generalBudget.value.include_future_categories ?? true,
        };
    } else {
        // Reset form for new budget
        generalBudgetForm.value = {
            name: '',
            amount: 0,
            type: 'mensal',
            month: new Date().getMonth() + 1,
            year: new Date().getFullYear(),
            include_future_categories: true,
        };
    }
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

        let response;
        if (generalBudget.value) {
            // Update existing
            response = await fetch(`/api/general-budgets/${generalBudget.value.id}`, {
                method: 'PUT',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
        } else {
            // Create new
            response = await fetch('/api/general-budgets', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
        }

        if (response.ok) {
            showGeneralBudgetModal.value = false;
            await loadGeneralBudget();
        } else {
            const error = await response.json();
            alert(error.message || 'Erro ao salvar orçamento geral');
        }
    } catch (e) {
        console.error('Error saving general budget:', e);
        alert('Erro ao salvar orçamento geral');
    } finally {
        savingGeneralBudget.value = false;
    }
}

function getMonthName(month) {
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return months[month - 1];
}

// Current period string (for category budgets)
const currentBudgetPeriodString = computed(() => {
    if (periodType.value === 'anual') {
        return String(selectedYear.value);
    }
    return `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}`;
});

// Categories that don't have a budget yet for this period
const availableCategories = computed(() => {
    const existingCategoryIds = budgets.value.map(b => b.category_id);
    return categoriesStore.categories.filter(c => !existingCategoryIds.includes(c.id));
});

function setPeriodType(type) {
    periodType.value = type;
    // Update selector mode to match
    selectorMode.value = type === 'anual' ? 'year' : 'month';
    loadData();
}

async function loadData() {
    budgetsStore.setPeriod(currentPeriod.value, periodType.value);
    await Promise.all([
        budgetsStore.fetchBudgets(currentPeriod.value, periodType.value),
        budgetsStore.fetchSummary(currentPeriod.value, periodType.value),
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

function getStatusLabel(status) {
    const labels = {
        ok: 'Dentro do limite',
        warning: 'Atenção',
        exceeded: 'Excedido',
    };
    return labels[status] || status;
}

function getStatusBadgeClass(status) {
    const classes = {
        ok: 'badge badge-green',
        warning: 'badge badge-yellow',
        exceeded: 'badge badge-red',
    };
    return classes[status] || 'badge';
}

function getProgressBarClass(status) {
    const classes = {
        ok: 'bg-green-500',
        warning: 'bg-yellow-500',
        exceeded: 'bg-red-500',
    };
    return classes[status] || 'bg-gray-500';
}

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
