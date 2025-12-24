<template>
    <div>
        <div class="mb-6">
            <RouterLink to="/transactions" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Voltar
            </RouterLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ isEditing ? 'Editar Lançamento' : 'Novo Lançamento' }}
            </h1>
        </div>

        <div v-if="hasPaidInstallments" class="p-4 mb-6 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 rounded-lg border border-yellow-200 dark:border-yellow-800 flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h3 class="font-bold">Atenção: Fatura Paga ou Fechada</h3>
                <p class="text-sm mt-1">
                    Esta transação pertence a uma fatura já processada. Ao editar valores, parcelas ou data, o sistema fará um 
                    <strong>estorno</strong> automático e criará um novo lançamento na fatura atual para corrigir a diferença. 
                    O histórico da fatura anterior será preservado.
                </p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="max-w-3xl">
            <!-- Section 1: Basic -->
            <div class="card mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informações Básicas</h2>
                
                <div class="space-y-4">
                    <!-- Type -->
                    <div>
                        <label class="label">Tipo de lançamento *</label>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="form.type = 'receita'"
                                :class="[
                                    'flex-1 py-3 px-4 rounded-lg border-2 font-medium transition-colors',
                                    form.type === 'receita'
                                        ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                ]"
                            >
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                                Receita
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'despesa'"
                                :class="[
                                    'flex-1 py-3 px-4 rounded-lg border-2 font-medium transition-colors',
                                    form.type === 'despesa'
                                        ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                ]"
                            >
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                                Despesa
                            </button>
                            <button
                                type="button"
                                @click="form.type = 'transferencia'"
                                :class="[
                                    'flex-1 py-3 px-4 rounded-lg border-2 font-medium transition-colors',
                                    form.type === 'transferencia'
                                        ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                ]"
                            >
                                <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                Transferência
                            </button>
                        </div>
                    </div>

                    <!-- Value -->
                    <div>
                        <label for="value" class="label">Valor *</label>
                        <MoneyInput v-model="form.value" input-class="text-2xl font-bold h-14" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="label">Descrição *</label>
                        <input
                            id="description"
                            v-model="form.description"
                            type="text"
                            required
                            class="input"
                            placeholder="Ex: Salário, Supermercado, Netflix..."
                        />
                    </div>

                    <!-- Date and Time -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="date" class="label">Data *</label>
                            <input
                                id="date"
                                v-model="form.date"
                                type="date"
                                required
                                class="input"
                            />
                        </div>
                        <div>
                            <label for="time" class="label">Hora</label>
                            <input
                                id="time"
                                v-model="form.time"
                                type="time"
                                class="input"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Account / Card -->
            <div class="card mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Conta / Cartão</h2>
                
                <div class="space-y-4">
                    <!-- Payment method for expenses -->
                    <div v-if="form.type === 'despesa'">
                        <label class="label">Forma de pagamento</label>
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="form.payment_method = 'dinheiro'; form.card_id = null"
                                :class="['flex-1 py-2 px-4 rounded-lg border-2 text-sm font-medium transition-colors', form.payment_method === 'dinheiro' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700']"
                            >
                                Dinheiro/PIX
                            </button>
                            <button
                                type="button"
                                @click="form.payment_method = 'debito'; form.card_id = null"
                                :class="['flex-1 py-2 px-4 rounded-lg border-2 text-sm font-medium transition-colors', form.payment_method === 'debito' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700']"
                            >
                                Débito
                            </button>
                            <button
                                type="button"
                                @click="form.payment_method = 'credito'"
                                :class="['flex-1 py-2 px-4 rounded-lg border-2 text-sm font-medium transition-colors', form.payment_method === 'credito' ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700']"
                            >
                                Crédito
                            </button>
                        </div>
                    </div>

                    <!-- Account selection -->
                    <div v-if="form.type !== 'credito' && form.payment_method !== 'credito' && form.payment_method !== 'debito'">
                        <label class="label">{{ form.type === 'transferencia' ? 'Conta de origem' : 'Conta' }}</label>
                        <select v-model="form.account_id" class="input">
                            <option value="">Selecione...</option>
                            <option v-for="account in activeAccounts" :key="account.id" :value="account.id">
                                {{ account.name }} - {{ formatCurrency(account.current_balance || account.initial_balance) }}
                            </option>
                        </select>
                    </div>

                    <!-- Destination account for transfer -->
                    <div v-if="form.type === 'transferencia'">
                        <label class="label">Conta de destino</label>
                        <select v-model="form.from_account_id" class="input">
                            <option value="">Selecione...</option>
                            <option
                                v-for="account in activeAccounts"
                                :key="account.id"
                                :value="account.id"
                                :disabled="account.id === form.account_id"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Card selection for credit/debit -->
                    <div v-if="form.payment_method === 'credito' || form.payment_method === 'debito'">
                        <label class="label">{{ form.payment_method === 'debito' ? 'Cartão de Débito' : 'Cartão de Crédito' }}</label>
                        <select v-model="form.card_id" class="input">
                            <option value="">Selecione...</option>
                            <option v-for="card in cardsStore.cards" :key="card.id" :value="card.id">
                                {{ card.name }} ({{ card.brand }})
                            </option>
                        </select>
                    </div>

                    <!-- Installments for credit -->
                    <div v-if="form.payment_method === 'credito' && form.card_id">
                        <label class="label">Parcelamento</label>
                        <select v-model="form.installments" class="input" @change="form.current_installment = 1">
                            <option :value="1">À vista</option>
                            <option v-for="n in 23" :key="n + 1" :value="n + 1">{{ n + 1 }}x de {{ formatCurrency((form.value || 0) / (n + 1)) }}</option>
                        </select>
                        
                        <!-- Parcelamento em andamento (somente para criação) -->
                        <div v-if="form.installments > 1 && !isEditing" class="mt-4">
                            <label class="label flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Parcelamento já em andamento?
                            </label>
                            <p class="text-xs text-gray-500 mb-2">
                                Se você está cadastrando uma compra que já começou a pagar, informe qual é a parcela atual.
                            </p>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Estou pagando a parcela</span>
                                <select v-model="form.current_installment" class="input w-20 text-center">
                                    <option v-for="n in form.installments" :key="n" :value="n">{{ n }}</option>
                                </select>
                                <span class="text-sm text-gray-600 dark:text-gray-400">de {{ form.installments }}</span>
                            </div>
                            
                            <div v-if="form.current_installment > 1" class="mt-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                                <p class="text-sm text-purple-800 dark:text-purple-200">
                                    <strong>Parcelas anteriores ({{ form.current_installment - 1 }}):</strong> Não serão criadas (consideradas históricas)
                                </p>
                                <p class="text-sm text-purple-800 dark:text-purple-200 mt-1">
                                    <strong>Parcelas a criar ({{ form.installments - form.current_installment + 1 }}):</strong> Da {{ form.current_installment }}ª até a {{ form.installments }}ª
                                </p>
                            </div>
                        </div>
                        
                        <div v-if="form.installments > 1 && form.current_installment === 1" class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                                Preview das parcelas:
                            </p>
                            <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                                <li v-for="n in Math.min(form.installments, 3)" :key="n">
                                    {{ n }}ª parcela: {{ formatCurrency((form.value || 0)/ form.installments) }}
                                </li>
                                <li v-if="form.installments > 3">...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Category -->
            <div class="card mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Categoria</h2>
                
                <div>
                    <select v-model="form.category_id" class="input">
                        <option value="">Selecione uma categoria...</option>
                        <optgroup v-if="form.type === 'receita' || form.type === 'transferencia'" label="Receitas">
                            <option v-for="category in incomeCategories" :key="category.id" :value="category.id">
                                {{ category.icon }} {{ category.name }}
                            </option>
                        </optgroup>
                        <optgroup v-if="form.type === 'despesa' || form.type === 'transferencia'" label="Despesas">
                            <option v-for="category in expenseCategories" :key="category.id" :value="category.id">
                                {{ category.icon }} {{ category.name }}
                            </option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Section 4: Notes -->
            <div class="card mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Observações</h2>
                
                <textarea
                    v-model="form.notes"
                    class="input"
                    rows="3"
                    placeholder="Notas adicionais (opcional)..."
                />
            </div>

            <!-- Section 5: Attachments -->
            <TransactionAttachments
                ref="attachmentsComponent"
                :transaction-id="isEditing ? route.params.id : null"
            />

            <!-- Section 6: Credit Purchase Summary (only for credit expenses) -->
            <div v-if="form.type === 'despesa' && form.payment_method === 'credito' && form.card_id && form.value > 0" class="card mb-6 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border-purple-200 dark:border-purple-800">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <h2 class="text-lg font-semibold text-purple-900 dark:text-purple-100">Resumo da Compra no Crédito</h2>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Cartão</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ selectedCardName }}</p>
                    </div>
                    <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Valor Total</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(form.value) }}</p>
                    </div>
                    <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Parcelas</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ form.installments || 1 }}x</p>
                    </div>
                    <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Valor da Parcela</p>
                        <p class="font-semibold text-purple-600 dark:text-purple-400">{{ formatCurrency((form.value || 0) / (form.installments || 1)) }}</p>
                    </div>
                </div>

                <!-- Invoice Preview -->
                <div class="bg-white/80 dark:bg-gray-800/80 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-purple-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ invoicePreviewText }}
                            </p>
                            <p v-if="form.current_installment > 1" class="text-sm text-purple-600 dark:text-purple-400 mt-1">
                                Lançando a partir da {{ form.current_installment }}ª parcela ({{ remainingInstallmentsCount }} parcelas restantes)
                            </p>
                            <p v-if="remainingInstallmentsCount > 1" class="text-sm text-gray-500 mt-1">
                                Primeira parcela na fatura de {{ invoicePreviewMonth }}. 
                                Última parcela na fatura de {{ lastInstallmentMonth }}.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 py-3" :disabled="loading">
                    <span v-if="loading">Salvando...</span>
                    <span v-else>{{ isEditing ? 'Salvar Alterações' : 'Criar Lançamento' }}</span>
                </button>
                <RouterLink to="/transactions" class="btn-secondary py-3">
                    Cancelar
                </RouterLink>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useTransactionsStore } from '@/stores/transactions';
import { useAccountsStore } from '@/stores/accounts';
import { useCardsStore } from '@/stores/cards';
import { useCategoriesStore } from '@/stores/categories';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import TransactionAttachments from '@/components/Transactions/TransactionAttachments.vue';

const route = useRoute();
const router = useRouter();
const transactionsStore = useTransactionsStore();
const accountsStore = useAccountsStore();
const cardsStore = useCardsStore();
const categoriesStore = useCategoriesStore();

const isEditing = computed(() => !!route.params.id);
const loading = ref(false);
const originalTransaction = ref(null);
const attachmentsComponent = ref(null);

const hasPaidInstallments = computed(() => {
    if (!originalTransaction.value || !originalTransaction.value.card_installments) return false;
    
    return originalTransaction.value.card_installments.some(inst => 
        inst.invoice && (inst.invoice.status === 'paga' || inst.invoice.status === 'fechada')
    );
});

// Usar data local correta (toISOString converte para UTC causando bug de -1 dia)
const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const nowTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

const form = reactive({
    type: 'despesa',
    value: 0,
    description: '',
    date: today,
    time: nowTime,
    account_id: '',
    from_account_id: '',
    card_id: '',
    category_id: '',
    payment_method: 'dinheiro',
    installments: 1,
    current_installment: 1, // Para parcelamentos em andamento
    notes: '',
});

// Filtrar categorias por tipo
const incomeCategories = computed(() => {
    return categoriesStore.categories.filter(c => c.type === 'receita');
});

const expenseCategories = computed(() => {
    return categoriesStore.categories.filter(c => c.type === 'despesa');
});

const activeAccounts = computed(() => {
    return accountsStore.accounts.filter(a => a.status === 'active' || (isEditing.value && (a.id === form.account_id || a.id === form.from_account_id)));
});

// Credit card purchase summary helpers
const selectedCard = computed(() => {
    if (!form.card_id) return null;
    return cardsStore.cards.find(c => c.id === form.card_id);
});

const selectedCardName = computed(() => {
    return selectedCard.value?.name || 'Cartão não selecionado';
});

// Calculate which invoice the purchase will be posted to
// When current_installment > 1, it means we're starting from a later installment
// so we should use the current open invoice instead of calculating from purchase date
const invoicePreviewMonth = computed(() => {
    if (!selectedCard.value || !form.date) return '';
    
    const closingDay = selectedCard.value.closing_day || 1;
    
    // If starting from a later installment, use today's date (current open invoice)
    // Otherwise, use the purchase date
    const referenceDate = form.current_installment > 1 
        ? new Date() 
        : new Date(form.date + 'T00:00:00');
    
    let invoiceMonth = referenceDate.getMonth();
    let invoiceYear = referenceDate.getFullYear();
    
    // If reference date is ON or AFTER closing day, goes to next month's invoice
    if (referenceDate.getDate() >= closingDay) {
        invoiceMonth++;
        if (invoiceMonth > 11) {
            invoiceMonth = 0;
            invoiceYear++;
        }
    }
    
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return `${months[invoiceMonth]}/${invoiceYear}`;
});

const invoicePreviewText = computed(() => {
    if (!invoicePreviewMonth.value) return 'Selecione um cartão e data para ver a fatura.';
    
    const remainingInstallments = (form.installments || 1) - (form.current_installment || 1) + 1;
    
    if (remainingInstallments > 1) {
        return `Esta compra será lançada a partir da fatura de ${invoicePreviewMonth.value}.`;
    }
    return `Esta compra será lançada na fatura de ${invoicePreviewMonth.value}.`;
});

const lastInstallmentMonth = computed(() => {
    const remainingInstallments = (form.installments || 1) - (form.current_installment || 1) + 1;
    if (!selectedCard.value || !form.date || remainingInstallments <= 1) return '';
    
    const closingDay = selectedCard.value.closing_day || 1;
    
    // If starting from a later installment, use today's date (current open invoice)
    const referenceDate = form.current_installment > 1 
        ? new Date() 
        : new Date(form.date + 'T00:00:00');
    
    let invoiceMonth = referenceDate.getMonth();
    let invoiceYear = referenceDate.getFullYear();
    
    // If reference date is ON or AFTER closing day, goes to next month's invoice
    if (referenceDate.getDate() >= closingDay) {
        invoiceMonth++;
        if (invoiceMonth > 11) {
            invoiceMonth = 0;
            invoiceYear++;
        }
    }
    
    // Add remaining installments (minus 1 because first is already counted)
    const totalMonthsToAdd = remainingInstallments - 1;
    invoiceMonth += totalMonthsToAdd;
    
    // Normalize months and years
    invoiceYear += Math.floor(invoiceMonth / 12);
    invoiceMonth = invoiceMonth % 12;
    
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return `${months[invoiceMonth]}/${invoiceYear}`;
});

const remainingInstallmentsCount = computed(() => {
    return (form.installments || 1) - (form.current_installment || 1) + 1;
});

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

async function handleSubmit() {
    loading.value = true;
    try {
        let result;
        if (isEditing.value) {
            result = await transactionsStore.updateTransaction(route.params.id, form);
        } else {
            result = await transactionsStore.createTransaction(form);
        }
        
        if (result.success) {
            if (attachmentsComponent.value && attachmentsComponent.value.hasQueuedFiles()) {
                const newId = isEditing.value ? route.params.id : result.data.id;
                await attachmentsComponent.value.uploadQueuedFiles(newId);
            }
            router.push('/transactions');
        }
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await Promise.all([
        accountsStore.fetchAccounts(),
        cardsStore.fetchCards(),
        categoriesStore.fetchCategories(),
    ]);
    
    if (isEditing.value) {
        loading.value = true;
        try {
            const transaction = await transactionsStore.fetchTransaction(route.params.id);
            if (transaction) {
                originalTransaction.value = transaction;
                form.type = transaction.type || 'despesa';
                form.value = parseFloat(transaction.value) || 0;
                form.description = transaction.description || '';
                form.date = transaction.date || today;
                form.time = transaction.time || nowTime;
                form.account_id = transaction.account_id || '';
                form.from_account_id = transaction.from_account_id || '';
                form.card_id = transaction.card_id || '';
                form.category_id = transaction.category_id || '';
                form.payment_method = transaction.payment_method || 'dinheiro';
                form.installments = transaction.installments || 1;
                form.notes = transaction.notes || '';
            }
        } finally {
            loading.value = false;
        }
    } else {
        // New transaction: read prefill values from URL query params
        const query = route.query;
        
        // Pre-fill type from URL (e.g., from "Depositar" button)
        if (query.type && ['receita', 'despesa', 'transferencia'].includes(query.type)) {
            form.type = query.type;
        }
        
        // Pre-fill account from URL
        if (query.account_id) {
            form.account_id = query.account_id;
        }
        
        // Pre-fill card from URL (if applicable)
        if (query.card_id) {
            form.card_id = query.card_id;
            form.payment_method = 'credito';
        }
    }
});
</script>
