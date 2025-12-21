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
                        <select v-model="form.installments" class="input">
                            <option :value="1">À vista</option>
                            <option v-for="n in 23" :key="n + 1" :value="n + 1">{{ n + 1 }}x de {{ formatCurrency((form.value || 0) / (n + 1)) }}</option>
                        </select>
                        
                        <div v-if="form.installments > 1" class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
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

const route = useRoute();
const router = useRouter();
const transactionsStore = useTransactionsStore();
const accountsStore = useAccountsStore();
const cardsStore = useCardsStore();
const categoriesStore = useCategoriesStore();

const isEditing = computed(() => !!route.params.id);
const loading = ref(false);
const originalTransaction = ref(null);

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
    }
});
</script>
