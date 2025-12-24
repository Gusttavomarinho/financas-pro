<template>
    <div>
        <!-- Page header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Recorrências</h1>
                <p class="text-gray-500 dark:text-gray-400">Gerencie receitas e despesas recorrentes</p>
            </div>
            <div class="flex gap-2">
                <RouterLink to="/recurrences/suggestions" class="btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    Sugestões
                </RouterLink>
                <button @click="openCreateModal" class="btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nova Recorrência
                </button>
            </div>
        </div>

        <!-- Dismissable info banner -->
        <DismissableBanner storage-key="recurrences-info" color="blue">
            Recorrências geram lançamentos automaticamente na data programada.
            Cada lançamento criado pode ser editado ou cancelado independentemente.
        </DismissableBanner>

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
            <button 
                v-for="tab in tabs" 
                :key="tab.value"
                @click="activeTab = tab.value"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === tab.value 
                        ? 'border-primary-500 text-primary-600' 
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                ]"
            >
                {{ tab.label }}
                <span class="ml-1 text-xs opacity-60">({{ getTabCount(tab.value) }})</span>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredRecurrings.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <p class="text-gray-500">Nenhuma recorrência {{ activeTab !== 'all' ? getStatusLabel(activeTab).toLowerCase() : 'cadastrada' }}.</p>
            <button @click="openCreateModal" class="btn-primary mt-4">
                Criar primeira recorrência
            </button>
        </div>

        <!-- List -->
        <div v-else class="space-y-3">
            <div 
                v-for="recurring in filteredRecurrings" 
                :key="recurring.id"
                class="card hover:shadow-lg transition-shadow cursor-pointer"
                @click="openDetailModal(recurring)"
            >
                <div class="flex items-center gap-4">
                    <!-- Icon -->
                    <div :class="[
                        'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0',
                        recurring.type === 'receita' 
                            ? 'bg-green-100 dark:bg-green-900/30' 
                            : 'bg-red-100 dark:bg-red-900/30'
                    ]">
                        <svg v-if="recurring.card_id" class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <svg v-else :class="[
                            'w-6 h-6',
                            recurring.type === 'receita' ? 'text-green-600' : 'text-red-600'
                        ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="recurring.type === 'receita'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                        </svg>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ recurring.description }}
                            </p>
                            <span :class="getStatusBadgeClass(recurring.status)">
                                {{ getStatusLabel(recurring.status) }}
                            </span>
                            <span v-if="recurring.card_id" class="badge badge-purple">
                                {{ recurring.card?.name || 'Cartão' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ recurring.category?.name || 'Sem categoria' }} · 
                            {{ getFrequencyLabel(recurring) }}
                        </p>
                        <p v-if="recurring.status === 'ativa'" class="text-xs text-primary-600 dark:text-primary-400 mt-0.5 font-medium">
                            Próxima cobrança em {{ formatDate(recurring.next_occurrence) }}
                        </p>
                        <p v-else-if="recurring.status === 'pausada'" class="text-xs text-yellow-600 dark:text-yellow-400 mt-0.5">
                            Pausada desde {{ formatDate(recurring.updated_at) }}
                        </p>
                        <p v-else class="text-xs text-gray-400 mt-0.5">
                            Encerrada
                            <template v-if="recurring.end_date">em {{ formatDate(recurring.end_date) }}</template>
                        </p>
                    </div>

                    <!-- Value -->
                    <div :class="[
                        'text-right flex-shrink-0',
                        recurring.type === 'receita' ? 'text-green-600' : 'text-red-600'
                    ]">
                        <p class="text-xl font-bold">
                            {{ recurring.type === 'receita' ? '+' : '-' }}{{ formatCurrency(recurring.value) }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 flex-shrink-0" @click.stop>
                        <button 
                            v-if="recurring.status === 'ativa'"
                            @click="openGenerateConfirm(recurring)"
                            class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg"
                            title="Gerar transação agora"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </button>
                        <button 
                            v-if="recurring.status === 'ativa'"
                            @click="openPauseConfirm(recurring)"
                            class="p-2 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg"
                            title="Pausar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                        <button 
                            v-if="recurring.status === 'pausada'"
                            @click="resumeRecurring(recurring)"
                            class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg"
                            title="Retomar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                        <button 
                            @click="openEditModal(recurring)"
                            class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            title="Editar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button 
                            v-if="recurring.status !== 'encerrada'"
                            @click="openEndConfirm(recurring)"
                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                            title="Encerrar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-xl max-h-[90vh] overflow-hidden shadow-2xl animate-slide-up">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ isEditing ? 'Editar Recorrência' : 'Nova Recorrência' }}
                        </h2>
                        <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 overflow-y-auto max-h-[calc(90vh-140px)]">
                        <form @submit.prevent="submitForm" class="space-y-5">
                            <!-- Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        type="button"
                                        @click="form.type = 'despesa'"
                                        :class="[
                                            'flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all',
                                            form.type === 'despesa'
                                                ? 'border-red-500 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 text-gray-600'
                                        ]"
                                    >
                                        <svg class="w-6 h-6" :class="form.type === 'despesa' ? 'text-red-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                        </svg>
                                        <span class="font-medium">Despesa</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="form.type = 'receita'"
                                        :class="[
                                            'flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all',
                                            form.type === 'receita'
                                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 text-gray-600'
                                        ]"
                                    >
                                        <svg class="w-6 h-6" :class="form.type === 'receita' ? 'text-green-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                        </svg>
                                        <span class="font-medium">Receita</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição *</label>
                                <input 
                                    v-model="form.description" 
                                    type="text" 
                                    class="input w-full" 
                                    placeholder="Ex: Aluguel, Salário, Netflix..." 
                                    required
                                >
                            </div>

                            <!-- Value with MoneyInput -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor *</label>
                                <MoneyInput v-model="form.value" />
                            </div>

                            <!-- Payment Source -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Origem do pagamento *</label>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <button 
                                        type="button"
                                        @click="form.payment_source = 'account'"
                                        :class="[
                                            'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all text-sm',
                                            form.payment_source === 'account'
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 text-gray-600'
                                        ]"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        <span class="font-medium">Conta</span>
                                    </button>
                                    <button 
                                        type="button"
                                        @click="form.payment_source = 'card'; form.type = 'despesa'"
                                        :class="[
                                            'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all text-sm',
                                            form.payment_source === 'card'
                                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700'
                                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 text-gray-600'
                                        ]"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        <span class="font-medium">Cartão de Crédito</span>
                                    </button>
                                </div>

                                <!-- Account select -->
                                <select 
                                    v-if="form.payment_source === 'account'" 
                                    v-model="form.account_id" 
                                    class="input w-full"
                                    required
                                >
                                    <option :value="null">Selecione uma conta...</option>
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }}
                                    </option>
                                </select>

                                <!-- Card select -->
                                <select 
                                    v-if="form.payment_source === 'card'" 
                                    v-model="form.card_id" 
                                    class="input w-full"
                                    required
                                >
                                    <option :value="null">Selecione um cartão...</option>
                                    <option v-for="card in cards" :key="card.id" :value="card.id">
                                        {{ card.name }} ({{ card.brand }})
                                    </option>
                                </select>
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                                <select v-model="form.category_id" class="input w-full">
                                    <option :value="null">Selecione uma categoria...</option>
                                    <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>

                            <!-- Frequency -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequência *</label>
                                    <select v-model="form.frequency" class="input w-full" required>
                                        <option value="semanal">Semanal</option>
                                        <option value="mensal">Mensal</option>
                                        <option value="anual">Anual</option>
                                        <option value="personalizada">Personalizada</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">A cada *</label>
                                    <div class="flex items-center gap-2">
                                        <input v-model="form.frequency_value" type="number" min="1" max="365" class="input w-20" required>
                                        <span class="text-sm text-gray-500 whitespace-nowrap">{{ frequencyUnit }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data de início *</label>
                                    <input v-model="form.start_date" type="date" class="input w-full" required :disabled="isEditing">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data de término</label>
                                    <input v-model="form.end_date" type="date" class="input w-full">
                                    <p class="text-xs text-gray-400 mt-1">Deixe vazio para indefinido</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                                <textarea v-model="form.notes" class="input w-full" rows="2" placeholder="Notas opcionais..."></textarea>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <button type="button" @click="closeModal" class="btn-secondary flex-1">Cancelar</button>
                        <button @click="submitForm" class="btn-primary flex-1" :disabled="submitting">
                            {{ submitting ? 'Salvando...' : (isEditing ? 'Atualizar' : 'Criar Recorrência') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeConfirmModal">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl animate-slide-up">
                    <div class="flex items-start gap-4 mb-6">
                        <div :class="[
                            'w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0',
                            confirmAction === 'generate' ? 'bg-blue-100 dark:bg-blue-900/30' :
                            confirmAction === 'pause' ? 'bg-yellow-100 dark:bg-yellow-900/30' :
                            'bg-red-100 dark:bg-red-900/30'
                        ]">
                            <svg :class="[
                                'w-6 h-6',
                                confirmAction === 'generate' ? 'text-blue-600' :
                                confirmAction === 'pause' ? 'text-yellow-600' :
                                'text-red-600'
                            ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="confirmAction === 'generate'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                <path v-else-if="confirmAction === 'pause'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ confirmTitle }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ confirmMessage }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeConfirmModal" class="btn-secondary flex-1">Cancelar</button>
                        <button 
                            @click="executeConfirmAction" 
                            :class="[
                                'flex-1 px-4 py-2 rounded-lg font-medium transition-colors',
                                confirmAction === 'generate' ? 'bg-blue-600 hover:bg-blue-700 text-white' :
                                confirmAction === 'pause' ? 'bg-yellow-500 hover:bg-yellow-600 text-white' :
                                'bg-red-600 hover:bg-red-700 text-white'
                            ]"
                            :disabled="confirmLoading"
                        >
                            {{ confirmLoading ? 'Aguarde...' : confirmButtonText }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedRecurring" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeDetailModal">
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden shadow-2xl animate-slide-up">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div :class="[
                                'w-10 h-10 rounded-xl flex items-center justify-center',
                                selectedRecurring.type === 'receita' 
                                    ? 'bg-green-100 dark:bg-green-900/30' 
                                    : 'bg-red-100 dark:bg-red-900/30'
                            ]">
                                <svg :class="[
                                    'w-5 h-5',
                                    selectedRecurring.type === 'receita' ? 'text-green-600' : 'text-red-600'
                                ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="selectedRecurring.type === 'receita'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ selectedRecurring.description }}</h2>
                                <span :class="getStatusBadgeClass(selectedRecurring.status)">{{ getStatusLabel(selectedRecurring.status) }}</span>
                            </div>
                        </div>
                        <button @click="closeDetailModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-5 overflow-y-auto max-h-[calc(90vh-200px)]">
                        <!-- Loading -->
                        <div v-if="detailLoading" class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Value & Next Occurrence Highlight -->
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-700/30 rounded-xl p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Valor</p>
                                        <p :class="[
                                            'text-2xl font-bold',
                                            selectedRecurring.type === 'receita' ? 'text-green-600' : 'text-red-600'
                                        ]">
                                            {{ selectedRecurring.type === 'receita' ? '+' : '-' }}{{ formatCurrency(selectedRecurring.value) }}
                                        </p>
                                    </div>
                                    <div v-if="selectedRecurring.status === 'ativa'" class="text-right">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Próxima cobrança</p>
                                        <p class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                                            {{ formatDate(selectedRecurring.next_occurrence) }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Microcopy -->
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 bg-white/50 dark:bg-gray-800/50 rounded-lg px-3 py-2">
                                    <span class="font-medium">{{ getFrequencyLabel(selectedRecurring) }}</span>
                                    <span v-if="selectedRecurring.status === 'ativa'"> — {{ getNextOccurrenceText(selectedRecurring) }}</span>
                                </p>
                            </div>

                            <!-- Details Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Tipo</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRecurring.type === 'receita' ? 'Receita' : 'Despesa' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Categoria</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRecurring.category?.name || 'Sem categoria' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Origem do Pagamento</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ getPaymentSourceText(selectedRecurring) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Frequência</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ getFrequencyLabel(selectedRecurring) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Data de Início</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(selectedRecurring.start_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Data de Término</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRecurring.end_date ? formatDate(selectedRecurring.end_date) : 'Indefinida' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Última Cobrança</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRecurring.last_generated_at ? formatDate(selectedRecurring.last_generated_at) : 'Nunca gerada' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Criada em</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(selectedRecurring.created_at) }}</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div v-if="selectedRecurring.notes">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Observações</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">{{ selectedRecurring.notes }}</p>
                            </div>

                            <!-- Charge History -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Histórico de Cobranças
                                </h3>
                                <div v-if="selectedRecurring.transactions && selectedRecurring.transactions.length > 0" class="space-y-2 max-h-48 overflow-y-auto">
                                    <RouterLink 
                                        v-for="tx in selectedRecurring.transactions" 
                                        :key="tx.id"
                                        :to="{ path: '/transactions', query: { showDetail: tx.id } }"
                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ formatDate(tx.date) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span :class="[
                                                'text-sm font-medium',
                                                tx.type === 'receita' ? 'text-green-600' : 'text-red-600'
                                            ]">{{ formatCurrency(tx.value) }}</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </RouterLink>
                                </div>
                                <div v-else class="text-center py-6 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="text-sm text-gray-500">Nenhuma cobrança gerada ainda</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Generation Info Section -->
                    <div v-if="generationInfo" class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 text-sm border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-gray-600 dark:text-gray-400">
                            <div v-if="generationInfo.last_automatic">
                                <span class="text-xs text-gray-500">Última automática:</span>
                                <span class="ml-1 font-medium">{{ generationInfo.last_automatic }}</span>
                            </div>
                            <div v-if="generationInfo.last_manual">
                                <span class="text-xs text-gray-500">Última manual:</span>
                                <span class="ml-1 font-medium text-yellow-600">{{ generationInfo.last_manual }}</span>
                            </div>
                            <div v-if="generationInfo.next_occurrence">
                                <span class="text-xs text-gray-500">Próxima:</span>
                                <span class="ml-1 font-medium text-primary-600">{{ generationInfo.next_occurrence }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer with Actions -->
                    <div class="flex flex-wrap gap-2 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <!-- Gerar Agora - 3 states: normal (blue), alert (yellow), blocked (disabled) -->
                        <button 
                            v-if="buttonState !== 'blocked'"
                            @click="openGenerateConfirm(selectedRecurring); closeDetailModal()"
                            :class="[
                                'flex-1 min-w-[120px] btn-secondary',
                                buttonState === 'alert' 
                                    ? 'text-yellow-600 border-yellow-200 hover:bg-yellow-50' 
                                    : 'text-blue-600 border-blue-200 hover:bg-blue-50'
                            ]"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span v-if="buttonState === 'alert'">Gerar (⚠️ duplicata)</span>
                            <span v-else>Gerar Agora</span>
                        </button>
                        <!-- Blocked button with tooltip -->
                        <div v-else class="flex-1 min-w-[120px] relative group">
                            <button 
                                disabled
                                class="w-full btn-secondary opacity-50 cursor-not-allowed text-gray-400"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Gerar Agora
                            </button>
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                {{ blockedTooltip || 'Não é possível gerar agora' }}
                            </div>
                        </div>
                        <button 
                            v-if="selectedRecurring.status === 'ativa'"
                            @click="openPauseConfirm(selectedRecurring); closeDetailModal()"
                            class="flex-1 min-w-[120px] btn-secondary text-yellow-600 border-yellow-200 hover:bg-yellow-50"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pausar
                        </button>
                        <button 
                            v-if="selectedRecurring.status === 'pausada'"
                            @click="resumeRecurring(selectedRecurring); closeDetailModal()"
                            class="flex-1 min-w-[120px] btn-secondary text-green-600 border-green-200 hover:bg-green-50"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            </svg>
                            Retomar
                        </button>
                        <button 
                            @click="openEditModal(selectedRecurring); closeDetailModal()"
                            class="flex-1 min-w-[120px] btn-secondary"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar
                        </button>
                        <button 
                            v-if="selectedRecurring.status !== 'encerrada'"
                            @click="openEndConfirm(selectedRecurring); closeDetailModal()"
                            class="flex-1 min-w-[120px] btn-secondary text-red-600 border-red-200 hover:bg-red-50"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Encerrar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Duplicate Warning Modal -->
        <Teleport to="body">
            <div v-if="showDuplicateWarning" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4" @click.self="closeDuplicateWarning">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full animate-slide-up">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                ⚠️ Lançamento já existe neste período
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">
                                Já existe um lançamento gerado para esta recorrência no período atual.
                                Gerar outro lançamento pode causar duplicidade.
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg mb-4">
                        <p class="text-xs text-blue-800 dark:text-blue-300">
                            💡 Use apenas para ajustes manuais ou cobranças excepcionais.
                        </p>
                    </div>
                    
                    <div class="flex gap-3">
                        <button @click="closeDuplicateWarning" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button 
                            @click="confirmDuplicateGeneration" 
                            :disabled="confirmLoading"
                            class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                        >
                            <span v-if="confirmLoading" class="flex items-center justify-center">
                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Gerando...
                            </span>
                            <span v-else>Gerar mesmo assim</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRecurringStore } from '@/stores/recurring';
import { useAccountsStore } from '@/stores/accounts';
import { useCardsStore } from '@/stores/cards';
import { useCategoriesStore } from '@/stores/categories';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';

const recurringStore = useRecurringStore();
const accountsStore = useAccountsStore();
const cardsStore = useCardsStore();
const categoriesStore = useCategoriesStore();

const loading = ref(true);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const activeTab = ref('all');

// Confirmation modal state
const showConfirmModal = ref(false);
const confirmAction = ref('');
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmButtonText = ref('');
const confirmLoading = ref(false);
const confirmRecurring = ref(null);

// Detail modal state
const showDetailModal = ref(false);
const selectedRecurring = ref(null);
const detailLoading = ref(false);
const buttonState = ref('normal'); // 'normal', 'alert', 'blocked'
const blockedTooltip = ref(null);
const generationInfo = ref(null);

// Duplicate warning modal state
const showDuplicateWarning = ref(false);
const duplicateRecurring = ref(null);

const tabs = [
    { label: 'Todas', value: 'all' },
    { label: 'Ativas', value: 'ativa' },
    { label: 'Pausadas', value: 'pausada' },
    { label: 'Encerradas', value: 'encerrada' },
];

const defaultForm = {
    type: 'despesa',
    description: '',
    value: 0,
    payment_source: 'account',
    account_id: null,
    card_id: null,
    category_id: null,
    frequency: 'mensal',
    frequency_value: 1,
    start_date: new Date().toISOString().split('T')[0],
    end_date: null,
    notes: '',
};

const form = ref({ ...defaultForm });

const accounts = computed(() => accountsStore.accounts.filter(a => !a.archived_at));
const cards = computed(() => cardsStore.cards.filter(c => !c.archived_at));
const categories = computed(() => categoriesStore.categories);
const recurrings = computed(() => recurringStore.recurrings);

// Filter categories by type (despesa = despesa categories, receita = receita categories)
const filteredCategories = computed(() => {
    return categories.value.filter(cat => cat.type === form.value.type);
});

const filteredRecurrings = computed(() => {
    if (activeTab.value === 'all') return recurrings.value;
    return recurrings.value.filter(r => r.status === activeTab.value);
});

const frequencyUnit = computed(() => {
    switch (form.value.frequency) {
        case 'semanal': return 'semana(s)';
        case 'mensal': return 'mês(es)';
        case 'anual': return 'ano(s)';
        case 'personalizada': return 'dia(s)';
        default: return '';
    }
});

// Clear the other payment source when switching
watch(() => form.value.payment_source, (newVal) => {
    if (newVal === 'account') {
        form.value.card_id = null;
    } else {
        form.value.account_id = null;
    }
});

// Clear category when type changes (categories are filtered by type)
watch(() => form.value.type, () => {
    form.value.category_id = null;
});

function getTabCount(status) {
    if (status === 'all') return recurrings.value.length;
    return recurrings.value.filter(r => r.status === status).length;
}

function getStatusLabel(status) {
    const labels = {
        ativa: 'Ativa',
        pausada: 'Pausada',
        encerrada: 'Encerrada',
    };
    return labels[status] || status;
}

function getStatusBadgeClass(status) {
    const classes = {
        ativa: 'badge badge-green',
        pausada: 'badge badge-yellow',
        encerrada: 'badge badge-gray',
    };
    return classes[status] || 'badge';
}

function getFrequencyLabel(recurring) {
    const value = recurring.frequency_value;
    switch (recurring.frequency) {
        case 'semanal':
            return value === 1 ? 'Semanal' : `A cada ${value} semanas`;
        case 'mensal':
            return value === 1 ? 'Mensal' : `A cada ${value} meses`;
        case 'anual':
            return value === 1 ? 'Anual' : `A cada ${value} anos`;
        case 'personalizada':
            return value === 1 ? 'Diário' : `A cada ${value} dias`;
        default:
            return 'Mensal';
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    if (!date) return '-';
    try {
        // Handle both YYYY-MM-DD and ISO format (YYYY-MM-DDTHH:mm:ss)
        let dateStr = String(date);
        // Extract just the date part if it contains time
        if (dateStr.includes('T')) {
            dateStr = dateStr.split('T')[0];
        }
        // Split and parse as local date
        const parts = dateStr.split('-');
        if (parts.length !== 3) {
            return '-';
        }
        const localDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        if (isNaN(localDate.getTime())) {
            return '-';
        }
        return new Intl.DateTimeFormat('pt-BR').format(localDate);
    } catch (e) {
        return '-';
    }
}

function openCreateModal() {
    form.value = { ...defaultForm };
    isEditing.value = false;
    editingId.value = null;
    showModal.value = true;
}

function openEditModal(recurring) {
    form.value = {
        type: recurring.type,
        description: recurring.description,
        value: parseFloat(recurring.value),
        payment_source: recurring.card_id ? 'card' : 'account',
        account_id: recurring.account_id,
        card_id: recurring.card_id,
        category_id: recurring.category_id,
        frequency: recurring.frequency,
        frequency_value: recurring.frequency_value,
        start_date: recurring.start_date,
        end_date: recurring.end_date,
        notes: recurring.notes || '',
    };
    isEditing.value = true;
    editingId.value = recurring.id;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function submitForm() {
    submitting.value = true;
    try {
        // Prepare data - use card_id or account_id based on payment_source
        const data = {
            ...form.value,
            account_id: form.value.payment_source === 'account' ? form.value.account_id : null,
            card_id: form.value.payment_source === 'card' ? form.value.card_id : null,
            payment_method: form.value.payment_source === 'card' ? 'credito' : 'dinheiro',
        };
        delete data.payment_source;

        if (isEditing.value) {
            await recurringStore.updateRecurring(editingId.value, data);
        } else {
            await recurringStore.createRecurring(data);
        }
        closeModal();
    } catch (error) {
        // Error handled by store
    } finally {
        submitting.value = false;
    }
}

// Confirmation modal functions
function openGenerateConfirm(recurring) {
    confirmRecurring.value = recurring;
    confirmAction.value = 'generate';
    confirmTitle.value = 'Gerar Transação Agora';
    confirmMessage.value = `Deseja gerar uma transação de ${formatCurrency(recurring.value)} para "${recurring.description}" agora? A próxima ocorrência será atualizada.`;
    confirmButtonText.value = 'Gerar Agora';
    showConfirmModal.value = true;
}

function openPauseConfirm(recurring) {
    confirmRecurring.value = recurring;
    confirmAction.value = 'pause';
    confirmTitle.value = 'Pausar Recorrência';
    confirmMessage.value = `Pausar a recorrência "${recurring.description}"? Ela não gerará transações enquanto estiver pausada.`;
    confirmButtonText.value = 'Pausar';
    showConfirmModal.value = true;
}

function openEndConfirm(recurring) {
    confirmRecurring.value = recurring;
    confirmAction.value = 'end';
    confirmTitle.value = 'Encerrar Recorrência';
    confirmMessage.value = `Encerrar permanentemente a recorrência "${recurring.description}"? Esta ação não pode ser desfeita.`;
    confirmButtonText.value = 'Encerrar';
    showConfirmModal.value = true;
}

function closeConfirmModal() {
    showConfirmModal.value = false;
    confirmRecurring.value = null;
}

async function executeConfirmAction() {
    if (!confirmRecurring.value) return;
    
    confirmLoading.value = true;
    try {
        switch (confirmAction.value) {
            case 'generate':
                const result = await recurringStore.generateTransaction(confirmRecurring.value.id, false);
                if (result.requires_confirmation) {
                    // Need duplicate confirmation - show warning modal
                    closeConfirmModal();
                    duplicateRecurring.value = confirmRecurring.value;
                    showDuplicateWarning.value = true;
                    return;
                }
                break;
            case 'pause':
                await recurringStore.pauseRecurring(confirmRecurring.value.id);
                break;
            case 'end':
                await recurringStore.endRecurring(confirmRecurring.value.id);
                break;
        }
        closeConfirmModal();
    } catch (error) {
        // Error handled by store
    } finally {
        confirmLoading.value = false;
    }
}

// Handle duplicate warning confirmation
async function confirmDuplicateGeneration() {
    if (!duplicateRecurring.value) return;
    
    confirmLoading.value = true;
    try {
        await recurringStore.generateTransaction(duplicateRecurring.value.id, true);
        showDuplicateWarning.value = false;
        duplicateRecurring.value = null;
    } finally {
        confirmLoading.value = false;
    }
}

function closeDuplicateWarning() {
    showDuplicateWarning.value = false;
    duplicateRecurring.value = null;
}

async function resumeRecurring(recurring) {
    await recurringStore.resumeRecurring(recurring.id);
}

// Detail modal functions
async function openDetailModal(recurring) {
    selectedRecurring.value = recurring;
    showDetailModal.value = true;
    buttonState.value = 'normal';
    blockedTooltip.value = null;
    generationInfo.value = null;
    
    // Fetch full details with transactions and button state
    detailLoading.value = true;
    try {
        const result = await recurringStore.fetchRecurringDetails(recurring.id);
        if (result) {
            selectedRecurring.value = result.recurring;
            buttonState.value = result.button_state || 'normal';
            blockedTooltip.value = result.blocked_tooltip;
            generationInfo.value = result.generation_info;
        }
    } finally {
        detailLoading.value = false;
    }
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedRecurring.value = null;
}

function getNextOccurrenceText(recurring) {
    if (!recurring.next_occurrence) return '';
    const formatted = formatDate(recurring.next_occurrence);
    return `Próxima cobrança em ${formatted}`;
}

function getPaymentSourceText(recurring) {
    if (recurring.card_id && recurring.card) {
        return `Cartão: ${recurring.card.name}`;
    }
    if (recurring.account_id && recurring.account) {
        return `Conta: ${recurring.account.name}`;
    }
    return 'Não definida';
}

onMounted(async () => {
    loading.value = true;
    try {
        await Promise.all([
            recurringStore.fetchRecurrings(),
            accountsStore.fetchAccounts(),
            cardsStore.fetchCards(),
            categoriesStore.fetchCategories(),
        ]);
    } finally {
        loading.value = false;
    }
});
</script>
