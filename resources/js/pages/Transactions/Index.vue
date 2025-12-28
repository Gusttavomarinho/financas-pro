<template>
    <div>
        <!-- Header Mobile Stacked -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lançamentos</h1>
                <p class="text-gray-500 dark:text-gray-400">Todas as suas transações</p>
            </div>
            <!-- Actions Container with improved spacing -->
            <div class="flex items-center gap-3 w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0 scrollbar-hide">
                <!-- Export Button (Triggers Modal) -->
                <button @click="showExportModal = true" class="btn-secondary flex items-center gap-2 flex-shrink-0 whitespace-nowrap" :disabled="exporting">
                    <svg v-if="exporting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Exportar
                </button>

                <RouterLink to="/transactions/create" class="btn-primary flex-none whitespace-nowrap mr-1">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Novo Lançamento
                </RouterLink>
            </div>
        </div>


        <!-- Filters -->
        <!-- Filters -->
        <div class="card mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <div>
                    <label class="label">Tipo</label>
                    <select v-model="transactionsStore.filters.type" class="input w-full">
                        <option value="">Todos</option>
                        <option value="receita">Receitas</option>
                        <option value="despesa">Despesas</option>
                        <option value="transferencia">Transferências</option>
                    </select>
                </div>
                <div>
                    <label class="label">Categoria</label>
                    <select v-model="transactionsStore.filters.category_id" class="input w-full">
                        <option value="">Todas as categorias</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.icon }} {{ category.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="label">Conta</label>
                    <select v-model="transactionsStore.filters.account_id" class="input w-full">
                        <option value="">Todas as contas</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.name }}{{ account.status === 'archived' ? ' (arquivada)' : '' }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="label">Cartão</label>
                    <select v-model="transactionsStore.filters.card_id" class="input w-full">
                        <option value="">Todos os cartões</option>
                        <option v-for="card in cards" :key="card.id" :value="card.id">
                            {{ card.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="label">Data inicial</label>
                    <input type="date" v-model="transactionsStore.filters.date_from" class="input w-full" />
                </div>
                <div>
                    <label class="label">Data final</label>
                    <input type="date" v-model="transactionsStore.filters.date_to" class="input w-full" />
                </div>
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-6">
                    <label class="label">Buscar</label>
                    <input
                        type="text"
                        v-model="transactionsStore.filters.search"
                        class="input w-full"
                        placeholder="Descrição..."
                    />
                </div>
            </div>
            <div v-if="hasActiveFilters" class="mt-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Filtros ativos</span>
                    <button @click="clearFilters" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Limpar filtros
                    </button>
                </div>
                
                <!-- Period presets -->
                <div class="flex gap-2 text-sm">
                    <button @click="setPeriod('this_month')" class="text-gray-500 hover:text-primary-600">Este Mês</button>
                    <button @click="setPeriod('last_30')" class="text-gray-500 hover:text-primary-600">Últimos 30 dias</button>
                    <button @click="setPeriod('this_year')" class="text-gray-500 hover:text-primary-600">Este Ano</button>
                </div>
            </div>
        </div>

        <!-- Transactions list -->
        <div v-if="transactionsStore.loading" class="text-center py-12">
            <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div v-else-if="transactionsStore.transactions.length" class="card overflow-hidden max-w-[90vw] sm:max-w-full mx-auto">
            <div class="overflow-x-auto w-full">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Data</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Descrição</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Categoria</th>
                            <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Conta/Cartão</th>
                            <th class="text-right py-3 px-4 text-sm font-medium text-gray-500">Valor</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="transaction in transactionsStore.transactions"
                            :key="transaction.id"
                            class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer"
                            @click="openTransactionDetail(transaction)"
                        >
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatDate(transaction.date) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div :class="getIconClass(transaction)">
                                        <svg v-if="transaction.type === 'transferencia'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        <svg v-else-if="transaction.type === 'receita'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ transaction.description }}</p>
                                        <p v-if="transaction.type === 'transferencia'" class="text-xs text-blue-600 dark:text-blue-400">
                                            {{ transaction.from_account?.name || 'Conta' }} → {{ transaction.account?.name || 'Destino' }}
                                        </p>
                                        <p v-else-if="transaction.total_installments > 1" class="text-xs text-purple-600 dark:text-purple-400">
                                            {{ transaction.total_installments }}x de {{ formatCurrency(transaction.installment_value || transaction.value / transaction.total_installments) }}
                                        </p>
                                        <span v-if="transaction.recurring_transaction_id" class="inline-flex items-center gap-1 text-xs text-cyan-600 dark:text-cyan-400">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Recorrência
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ transaction.category?.name || '-' }}
                            </td>
                            <td class="py-3 px-4 text-sm">
                                <template v-if="transaction.type === 'transferencia'">
                                    <span class="inline-flex items-center gap-1.5 text-blue-600" title="Transferência entre contas">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        Transferência
                                    </span>
                                </template>
                                <template v-else-if="transaction.card">
                                    <span 
                                        class="inline-flex items-center gap-1.5 text-gray-700 dark:text-gray-300"
                                        :title="`Cartão: ${transaction.card.name}`"
                                    >
                                        <!-- Card icon (credit card) -->
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        {{ transaction.card.name }}
                                    </span>
                                </template>
                                <template v-else-if="transaction.account">
                                    <span 
                                        class="inline-flex items-center gap-1.5"
                                        :class="{ 'text-gray-400': transaction.account.status === 'archived' }"
                                        :title="`Conta: ${transaction.account.name}${transaction.account.status === 'archived' ? ' (arquivada)' : ''}`"
                                    >
                                        <!-- Account icon (bank) -->
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                        </svg>
                                        {{ transaction.account.name }}
                                        <span v-if="transaction.account.status === 'archived'" class="text-xs">(arq.)</span>
                                    </span>
                                </template>
                                <template v-else>-</template>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span :class="getValueClass(transaction)">
                                    {{ getValuePrefix(transaction) }}{{ formatCurrency(transaction.value) }}
                                </span>
                            </td>
                            <td class="py-3 px-4" @click.stop>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="router.push(`/transactions/${transaction.id}/edit`)"
                                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="Editar"
                                    >
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openDuplicateConfirm(transaction)"
                                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="Duplicar"
                                    >
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="transaction.payment_method === 'credito'"
                                        @click="openRefundModal(transaction)"
                                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="Estornar"
                                    >
                                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="transaction.payment_method === 'credito' && transaction.total_installments > 1"
                                        @click="openAnticipateModal(transaction)"
                                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="Antecipar Parcelas"
                                    >
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openDeleteConfirm(transaction)"
                                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="Excluir"
                                    >
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="transactionsStore.pagination.last_page > 1" class="mt-4 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Mostrando {{ transactionsStore.transactions.length }} de {{ transactionsStore.pagination.total }} resultados
                </p>
                <div class="flex gap-2">
                    <button
                        @click="transactionsStore.fetchTransactions(transactionsStore.pagination.current_page - 1)"
                        :disabled="transactionsStore.pagination.current_page === 1"
                        class="btn-secondary"
                    >
                        Anterior
                    </button>
                    <button
                        @click="transactionsStore.fetchTransactions(transactionsStore.pagination.current_page + 1)"
                        :disabled="transactionsStore.pagination.current_page === transactionsStore.pagination.last_page"
                        class="btn-secondary"
                    >
                        Próximo
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="card text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum lançamento encontrado</h3>
            <p class="text-gray-500 mb-4">Comece registrando seus gastos e receitas</p>
            <RouterLink to="/transactions/create" class="btn-primary">
                Novo Lançamento
            </RouterLink>
        </div>

        <!-- Transaction Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedTransaction" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
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
                            <div :class="getIconClass(selectedTransaction)" class="w-16 h-16 mx-auto mb-3">
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
                            <p class="text-3xl font-bold" :class="getValueClass(selectedTransaction)">
                                {{ getValuePrefix(selectedTransaction) }}{{ formatCurrency(selectedTransaction.value) }}
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
                            <div v-if="selectedTransaction.type === 'transferencia'" class="flex justify-between">
                                <span class="text-gray-500">De → Para</span>
                                <span class="font-medium text-blue-600">
                                    {{ selectedTransaction.from_account?.name }} → {{ selectedTransaction.account?.name }}
                                </span>
                            </div>
                            <div v-else-if="selectedTransaction.account" class="flex justify-between">
                                <span class="text-gray-500">Conta</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ selectedTransaction.account.name }}
                                    <span v-if="selectedTransaction.account.status === 'archived'" class="text-xs text-gray-400">(arquivada)</span>
                                </span>
                            </div>
                            <div v-if="selectedTransaction.card" class="flex justify-between">
                                <span class="text-gray-500">Cartão</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ selectedTransaction.card.name }}</span>
                            </div>
                        </div>

                        <!-- Installments section -->
                        <div v-if="selectedTransaction.total_installments > 1" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">
                                Parcelamento ({{ selectedTransaction.total_installments }}x)
                            </h4>
                            <div v-if="selectedTransaction.card_installments?.length" class="space-y-2">
                                <div
                                    v-for="installment in selectedTransaction.card_installments"
                                    :key="installment.id"
                                    class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            Parcela {{ installment.installment_number }}/{{ installment.total_installments }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ installment.invoice ? `Fatura ${formatMonth(installment.invoice.reference_month)}` : 'Sem fatura' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900 dark:text-white">
                                            {{ formatCurrency(installment.value) }}
                                        </p>
                                        <span :class="getInstallmentStatusClass(installment.status)">
                                            {{ getInstallmentStatusLabel(installment.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4 text-gray-500">
                                {{ selectedTransaction.total_installments }}x de {{ formatCurrency(selectedTransaction.installment_value || selectedTransaction.value / selectedTransaction.total_installments) }}
                            </div>
                            </div>


                        <!-- Attachments section -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Anexos</h4>
                            <TransactionAttachments 
                                :transaction-id="selectedTransaction.id"
                            />
                        </div>

                        <!-- Timeline -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Histórico de Ações</h4>
                            <Timeline entityType="Transaction" :entityId="selectedTransaction.id" />
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-6 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <RouterLink 
                                :to="`/transactions/${selectedTransaction.id}/edit`" 
                                class="btn-primary justify-center"
                                @click="showDetailModal = false"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar
                            </RouterLink>
                            <button @click="openDuplicateFromModal" class="btn-secondary justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Duplicar
                            </button>
                        </div>
                        <div v-if="selectedTransaction.payment_method === 'credito'" class="grid grid-cols-2 gap-3">
                            <button @click="openRefundFromModal" class="btn-outline justify-center text-orange-600 border-orange-600 hover:bg-orange-50">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Estornar
                            </button>
                            <button 
                                v-if="selectedTransaction.total_installments > 1"
                                @click="openAnticipateFromModal" 
                                class="btn-outline justify-center text-purple-600 border-purple-600 hover:bg-purple-50"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Antecipar
                            </button>
                        </div>
                        <button @click="openDeleteFromModal" class="btn-danger w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Excluir Lançamento
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Duplicate Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDuplicateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full animate-slide-up">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Duplicar Lançamento</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Deseja duplicar o lançamento <strong>"{{ transactionToAction?.description }}"</strong>?
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDuplicateModal = false" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="handleDuplicate" class="btn-primary flex-1">
                            Duplicar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Refund Modal with Partial Option -->
        <Teleport to="body">
            <div v-if="showRefundModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full animate-slide-up">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Estornar Lançamento</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        <strong>"{{ transactionToAction?.description }}"</strong>
                        <span class="block text-sm mt-1">Valor total: {{ formatCurrency(transactionToAction?.value) }}</span>
                        <span v-if="transactionToAction?.total_installments > 1" class="block text-sm">
                            Parcelado em {{ transactionToAction?.total_installments }}x
                        </span>
                    </p>

                    <!-- Refund type selection -->
                    <div class="space-y-3 mb-4">
                        <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors"
                            :class="refundType === 'cancel' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700'"
                            @click="refundType = 'cancel'"
                        >
                            <input type="radio" v-model="refundType" value="cancel" class="w-4 h-4 mt-1" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Cancelamento Total</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Cancela a transação e remove todas as parcelas futuras.
                                    <br>Use para: compra duplicada, erro imediato.
                                </p>
                            </div>
                        </label>
                        <label 
                            class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors"
                            :class="refundType === 'credit' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700'"
                            @click="refundType = 'credit'; refundValue = transactionToAction?.value"
                        >
                            <input type="radio" v-model="refundType" value="credit" class="w-4 h-4 mt-1" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Estorno em Valor (Crédito)</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Mantém transação e parcelas, cria crédito na fatura atual.
                                    <br>Use para: devolução parcial, reembolso.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Credit refund value input -->
                    <div v-if="refundType === 'credit'" class="mb-4">
                        <label class="label">Valor do Crédito (R$)</label>
                        <input 
                            type="number" 
                            v-model.number="refundValue" 
                            :max="transactionToAction?.value"
                            min="0.01"
                            step="0.01"
                            class="input"
                            placeholder="0,00"
                        />
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg mt-3 flex items-start gap-2">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                O crédito será lançado na <strong>fatura atual</strong>.
                                As parcelas futuras permanecerão inalteradas.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="showRefundModal = false" class="btn-secondary flex-1">
                            Voltar
                        </button>
                        <button @click="handleRefund" class="btn-danger flex-1">
                            {{ refundType === 'cancel' ? 'Cancelar Compra' : `Creditar ${formatCurrency(refundValue)}` }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full animate-slide-up">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Excluir Lançamento</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Tem certeza que deseja excluir o lançamento <strong>"{{ transactionToAction?.description }}"</strong>?
                        Esta ação não pode ser desfeita.
                    </p>
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="handleDelete" class="btn-danger flex-1">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Anticipate Modal -->
        <AnticipateModal 
            v-if="showAnticipateModal && transactionToAnticipate"
            :transactionId="transactionToAnticipate.id"
            @close="showAnticipateModal = false; transactionToAnticipate = null"
            @success="transactionsStore.fetchTransactions()"
        />
        <!-- Export Modal -->
        <Teleport to="body">
            <div v-if="showExportModal" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showExportModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                        Exportar Lançamentos
                                    </h3>
                                    <div class="mt-4 space-y-3">
                                        <button @click="exportPdf(); showExportModal = false" class="w-full flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <span class="text-2xl mr-3">📄</span>
                                            <div class="text-left">
                                                <div class="font-medium text-gray-900 dark:text-white">Exportar PDF</div>
                                                <div class="text-sm text-gray-500">Lista detalhada em formato PDF</div>
                                            </div>
                                        </button>
                                        
                                        <button @click="exportCsv(); showExportModal = false" class="w-full flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <span class="text-2xl mr-3">📊</span>
                                            <div class="text-left">
                                                <div class="font-medium text-gray-900 dark:text-white">Exportar Excel/CSV</div>
                                                <div class="text-sm text-gray-500">Planilha para análise de dados</div>
                                            </div>
                                        </button>

                                        <button @click="exportSummaryPdf(); showExportModal = false" class="w-full flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <span class="text-2xl mr-3">📈</span>
                                            <div class="text-left">
                                                <div class="font-medium text-gray-900 dark:text-white">Resumo Financeiro</div>
                                                <div class="text-sm text-gray-500">Relatório consolidado</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="showExportModal = false">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useTransactionsStore } from '@/stores/transactions';
import { useAccountsStore } from '@/stores/accounts';
import { useCardsStore } from '@/stores/cards';
import Timeline from '@/components/Common/Timeline.vue';
import AnticipateModal from '@/components/Transactions/AnticipateModal.vue';
import TransactionAttachments from '@/components/Transactions/TransactionAttachments.vue';

const route = useRoute();
const router = useRouter();
const transactionsStore = useTransactionsStore();
const accountsStore = useAccountsStore();
const cardsStore = useCardsStore();

const showDetailModal = ref(false);
const showDuplicateModal = ref(false);
const showRefundModal = ref(false);
const showDeleteModal = ref(false);
const selectedTransaction = ref(null);
const transactionToAction = ref(null);
const refundType = ref('full');
const keepInstallments = ref(1);
const refundValue = ref(0);
const showAnticipateModal = ref(false);
const transactionToAnticipate = ref(null);
const showExportModal = ref(false);
const exporting = ref(false);
const exportDropdown = ref(null);

import axios from 'axios';

import { useCategoriesStore } from '@/stores/categories';
import { useFilters } from '@/composables/useFilters';

const accounts = computed(() => accountsStore.accounts);
const cards = computed(() => cardsStore.cards);
const categories = computed(() => categoriesStore.categories);

const hasActiveFilters = computed(() => {
    const f = transactionsStore.filters;
    return f.type || f.category_id || f.account_id || f.card_id || f.date_from || f.date_to || f.search;
});

function clearFilters() {
    transactionsStore.clearFilters();
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    if (!date) return '';
    // Tratar data YYYY-MM-DD como local
    const [year, month, day] = date.split('T')[0].split('-');
    return `${day}/${month}/${year}`;
}

function formatMonth(yearMonth) {
    if (!yearMonth) return '';
    const [year, month] = yearMonth.split('-');
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return `${months[parseInt(month) - 1]}/${year}`;
}

function getIconClass(transaction) {
    if (transaction.type === 'transferencia') {
        return 'w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 flex items-center justify-center';
    } else if (transaction.type === 'receita') {
        return 'w-8 h-8 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 flex items-center justify-center';
    } else {
        return 'w-8 h-8 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center';
    }
}

function getValueClass(transaction) {
    if (transaction.type === 'transferencia') {
        return 'font-semibold text-blue-600 dark:text-blue-400';
    } else if (transaction.type === 'receita') {
        return 'font-semibold text-green-600 dark:text-green-400';
    } else {
        return 'font-semibold text-red-600 dark:text-red-400';
    }
}

function getValuePrefix(transaction) {
    if (transaction.type === 'transferencia') return '';
    return transaction.type === 'receita' ? '+' : '-';
}

function getInstallmentStatusClass(status) {
    const classes = {
        pendente: 'text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600',
        em_fatura: 'text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-600',
        paga: 'text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-600',
        estornada: 'text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-600',
        antecipada: 'text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-600',
    };
    return classes[status] || classes.pendente;
}

function getInstallmentStatusLabel(status) {
    const labels = {
        pendente: 'Pendente',
        em_fatura: 'Na Fatura',
        paga: 'Paga',
        estornada: 'Estornada',
        antecipada: 'Antecipada',
    };
    return labels[status] || status;
}

function openTransactionDetail(transaction) {
    selectedTransaction.value = transaction;
    showDetailModal.value = true;
}

function openDuplicateConfirm(transaction) {
    transactionToAction.value = transaction;
    showDuplicateModal.value = true;
}

function openRefundModal(transaction) {
    transactionToAction.value = transaction;
    refundType.value = 'cancel'; // Default to cancel
    refundValue.value = transaction.value;
    showRefundModal.value = true;
}

function openDeleteConfirm(transaction) {
    transactionToAction.value = transaction;
    showDeleteModal.value = true;
}

function openAnticipateModal(transaction) {
    transactionToAnticipate.value = transaction;
    showAnticipateModal.value = true;
}

// Helper functions to open action modals from detail modal
function openDuplicateFromModal() {
    transactionToAction.value = selectedTransaction.value;
    showDetailModal.value = false;
    showDuplicateModal.value = true;
}

function openRefundFromModal() {
    transactionToAction.value = selectedTransaction.value;
    refundType.value = 'cancel';
    refundValue.value = selectedTransaction.value.value;
    showDetailModal.value = false;
    showRefundModal.value = true;
}

function openAnticipateFromModal() {
    transactionToAnticipate.value = selectedTransaction.value;
    showDetailModal.value = false;
    showAnticipateModal.value = true;
}

function openDeleteFromModal() {
    transactionToAction.value = selectedTransaction.value;
    showDetailModal.value = false;
    showDeleteModal.value = true;
}

async function handleDuplicate() {
    if (transactionToAction.value) {
        await transactionsStore.duplicateTransaction(transactionToAction.value.id);
        showDuplicateModal.value = false;
        transactionToAction.value = null;
    }
}

async function handleRefund() {
    if (transactionToAction.value) {
        if (refundType.value === 'cancel') {
            // Cancelamento Total: usa refund existente que cancela tudo
            await transactionsStore.refundTransaction(transactionToAction.value.id);
        } else {
            // Estorno por Valor (Crédito): cria ajuste contábil
            await transactionsStore.refundByValue(transactionToAction.value.id, refundValue.value);
        }
        showRefundModal.value = false;
        transactionToAction.value = null;
        refundValue.value = 0;
    }
}

async function handleDelete() {
    if (transactionToAction.value) {
        await transactionsStore.deleteTransaction(transactionToAction.value.id);
        showDeleteModal.value = false;
        transactionToAction.value = null;
    }
}

const categoriesStore = useCategoriesStore();
const { isInitialized } = useFilters(
    transactionsStore.filters,
    () => transactionsStore.fetchTransactions(1),
    {
        type: '',
        category_id: '',
        account_id: '',
        card_id: '',
        date_from: '',
        date_to: '',
        search: '',
    }
);

onMounted(async () => {
    await Promise.all([
        accountsStore.fetchAccounts(),
        cardsStore.fetchCards(),
        categoriesStore.fetchCategories(),
    ]);
    
    // Initial fetch is handled by useFilters onMounted
    
    // Check if we should auto-open a transaction detail modal
    const showDetailId = route.query.showDetail;
    if (showDetailId) {
        // Wait a bit for transactions to load, then find and open the transaction
        setTimeout(async () => {
            // Try to find the transaction in the loaded list
            let transaction = transactionsStore.transactions.find(t => t.id === parseInt(showDetailId));
            if (!transaction) {
                // If not found in list, fetch it directly
                try {
                    const response = await axios.get(`/api/transactions/${showDetailId}`);
                    transaction = response.data.data;
                } catch (e) {
                    console.error('Could not load transaction details:', e);
                    return;
                }
            }
            if (transaction) {
                openTransactionDetail(transaction);
                // Clean up URL
                router.replace({ query: { ...route.query, showDetail: undefined } });
            }
        }, 500);
    }
});

// Helper for filtering by period
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

    const formatDate = (date) => date.toLocaleDateString('sv-SE');

    transactionsStore.setFilters({
        date_from: formatDate(start),
        date_to: formatDate(end),
    });
}

// Build query string from current filters
function buildExportQueryString() {
    const f = transactionsStore.filters;
    const params = new URLSearchParams();
    if (f.date_from) params.append('start_date', f.date_from);
    if (f.date_to) params.append('end_date', f.date_to);
    if (f.type) params.append('type', f.type);
    if (f.account_id) params.append('account_id', f.account_id);
    if (f.category_id) params.append('category_id', f.category_id);
    if (f.search) params.append('search', f.search);
    return params.toString();
}

// Export functions
async function exportPdf() {
    showExportMenu.value = false;
    if (transactionsStore.transactions.length === 0) {
        alert('Nenhuma transação encontrada para exportar.');
        return;
    }
    exporting.value = true;
    try {
        const response = await axios.get(`/api/reports/transactions/pdf?${buildExportQueryString()}`, {
            responseType: 'blob'
        });
        downloadBlob(response.data, 'transacoes.pdf', 'application/pdf');
    } catch (err) {
        console.error('Export PDF error:', err);
        alert(err.response?.data?.message || 'Erro ao exportar PDF');
    } finally {
        exporting.value = false;
    }
}

async function exportCsv() {
    showExportMenu.value = false;
    if (transactionsStore.transactions.length === 0) {
        alert('Nenhuma transação encontrada para exportar.');
        return;
    }
    exporting.value = true;
    try {
        const response = await axios.get(`/api/reports/transactions/csv?${buildExportQueryString()}`, {
            responseType: 'blob'
        });
        downloadBlob(response.data, 'transacoes.csv', 'text/csv');
    } catch (err) {
        console.error('Export CSV error:', err);
        alert(err.response?.data?.message || 'Erro ao exportar CSV');
    } finally {
        exporting.value = false;
    }
}

async function exportSummaryPdf() {
    showExportMenu.value = false;
    exporting.value = true;
    try {
        const response = await axios.get(`/api/reports/summary/pdf?${buildExportQueryString()}`, {
            responseType: 'blob'
        });
        downloadBlob(response.data, 'resumo_financeiro.pdf', 'application/pdf');
    } catch (err) {
        console.error('Export Summary error:', err);
        alert(err.response?.data?.message || 'Erro ao exportar resumo');
    } finally {
        exporting.value = false;
    }
}

function downloadBlob(blob, filename, type) {
    const url = window.URL.createObjectURL(new Blob([blob], { type }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

// Close export dropdown on click outside
import { onMounted as onMountedHook, onUnmounted } from 'vue';

function handleClickOutside(event) {
    if (exportDropdown.value && !exportDropdown.value.contains(event.target)) {
        showExportMenu.value = false;
    }
}

onMountedHook(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});


</script>
