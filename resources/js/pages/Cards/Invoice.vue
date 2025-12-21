<template>
    <div>
        <div class="mb-6">
            <RouterLink to="/cards" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Voltar para Cartões
            </RouterLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fatura do Cartão</h1>
        </div>

        <div v-if="cardsStore.loading" class="text-center py-12">
            <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <template v-else-if="selectedInvoice">
            <!-- Invoice summary -->
            <div class="card mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span :class="['badge', getStatusBadgeClass(selectedInvoice.status)]">{{ getStatusLabel(selectedInvoice.status) }}</span>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-2">
                            Fatura de {{ formatMonth(selectedInvoice.due_date) }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Período: {{ formatDate(selectedInvoice.period_start) }} - {{ formatDate(selectedInvoice.period_end) }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Vencimento: {{ formatDate(selectedInvoice.due_date) }}
                            <span v-if="selectedInvoice.status !== 'paga' && selectedInvoice.status !== 'fechada'" 
                                  :class="['ml-2 text-xs uppercase font-bold', getDueWarningClass(getDaysUntilDue(selectedInvoice.due_date))]">
                                {{ getDueWarningText(getDaysUntilDue(selectedInvoice.due_date)) }}
                            </span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Valor total</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(selectedInvoice.total_value) }}
                        </p>
                        <p v-if="selectedInvoice.paid_value > 0" class="text-sm text-green-600">
                            Pago: {{ formatCurrency(selectedInvoice.paid_value) }}
                        </p>
                    </div>
                </div>

                <div v-if="canPay" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button @click="showPayModal = true" class="btn-primary">
                        Pagar Fatura
                    </button>
                </div>
                <div v-else-if="selectedInvoice.status !== 'paga' && remainingValue <= 0" 
                     class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500">Não há valor a pagar nesta fatura.</p>
                </div>
            </div>

            <!-- Invoice items (parcelas) -->
            <div class="card mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Lançamentos da Fatura</h3>
                
                <div v-if="selectedInvoice.items?.length" class="space-y-3">
                    <div
                        v-for="item in selectedInvoice.items"
                        :key="item.id"
                        class="flex items-center justify-between p-4 rounded-lg"
                        :class="item.value < 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-800/50'"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Icon: green for credit/refund, red for debit -->
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center"
                                :class="item.value < 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
                            >
                                <svg v-if="item.value < 0" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ item.description || 'Compra' }}
                                    <span v-if="item.value < 0" class="text-xs text-green-600 dark:text-green-400 ml-1">(Crédito)</span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span v-if="item.total_installments > 1">
                                        Parcela {{ item.installment_number }}/{{ item.total_installments }} •
                                    </span>
                                    <span v-if="item.status === 'antecipada'" class="text-purple-600 dark:text-purple-400 font-medium">
                                        Antecipada •
                                    </span>
                                    {{ formatDate(item.date) }}
                                    <span v-if="item.category" class="text-xs text-gray-400">
                                        • {{ item.category.name }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span 
                                class="font-semibold"
                                :class="item.value < 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'"
                            >
                                {{ formatCurrency(item.value) }}
                            </span>
                            <p class="text-xs" :class="getInstallmentStatusClass(item.status)">
                                {{ getInstallmentStatusLabel(item.status) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-8 text-gray-500">
                    Nenhum lançamento nesta fatura
                </div>
            </div>

            <!-- Invoice history / navigation -->
            <div class="card">
                <template v-if="cardsStore.invoices.length">
                    <!-- Actionable Invoices -->
                    <div v-if="actionableInvoices.length" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">A Pagar</h3>
                        <div class="space-y-2">
                             <div
                                v-for="invoice in actionableInvoices"
                                :key="invoice.id"
                                class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-colors"
                                :class="[
                                    invoice.id === selectedInvoice.id 
                                        ? 'bg-primary-50 border-2 border-primary-500 dark:bg-primary-900/30' 
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 border border-gray-100 dark:border-gray-700'
                                ]"
                                @click="selectInvoice(invoice)"
                            >
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatMonth(invoice.reference_month) }}</p>
                                    <span :class="['badge', getStatusBadgeClass(invoice.status)]">{{ getStatusLabel(invoice.status) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold text-gray-900 dark:text-white animate-pulse">
                                        {{ formatCurrency(invoice.total_value) }}
                                    </span>
                                    <p v-if="invoice.remaining_value > 0" class="text-xs text-orange-600 font-bold">
                                        Restante: {{ formatCurrency(invoice.remaining_value) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Open/Future Invoices -->
                    <div v-if="openInvoices.length" class="mb-6">
                         <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Em Aberto / Futuras</h3>
                         <div class="space-y-2">
                             <div
                                v-for="invoice in openInvoices"
                                :key="invoice.id"
                                class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-colors"
                                :class="[
                                    invoice.id === selectedInvoice.id 
                                        ? 'bg-primary-50 border-2 border-primary-500 dark:bg-primary-900/30' 
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 border-l-4 border-l-blue-400 pl-3'
                                ]"
                                @click="selectInvoice(invoice)"
                            >
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatMonth(invoice.reference_month) }}</p>
                                    <span :class="['badge', getStatusBadgeClass(invoice.status)]">{{ getStatusLabel(invoice.status) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(invoice.total_value) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paid Invoices -->
                    <div v-if="paidInvoices.length">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Histórico (Pagas)</h3>
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                             <div
                                v-for="invoice in paidInvoices"
                                :key="invoice.id"
                                class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-colors opacity-75 hover:opacity-100"
                                :class="[
                                    invoice.id === selectedInvoice.id 
                                        ? 'bg-primary-50 border-2 border-primary-500 dark:bg-primary-900/30 opacity-100' 
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 border border-gray-100 dark:border-gray-700'
                                ]"
                                @click="selectInvoice(invoice)"
                            >
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatMonth(invoice.reference_month) }}</p>
                                    <span :class="['badge', getStatusBadgeClass(invoice.status)]">{{ getStatusLabel(invoice.status) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold text-gray-500 dark:text-gray-400 decoration-gray-400">
                                        {{ formatCurrency(invoice.total_value) }}
                                    </span>
                                    <p class="text-xs text-green-600">
                                        Pago em dia
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="text-center py-8 text-gray-500">
                    Nenhuma fatura encontrada.
                </div>
            </div>
        </template>

        <!-- Pay modal -->
        <Teleport to="body">
            <div v-if="showPayModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Pagar Fatura</h3>
                    
                    <div class="space-y-4">
                        <!-- Payment type selection -->
                        <div>
                            <label class="label">Opção de pagamento</label>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="selectFullPayment"
                                    :class="[
                                        'flex-1 py-3 px-4 rounded-lg border-2 text-sm font-medium transition-colors text-center',
                                        isFullPayment 
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' 
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <div class="font-bold">Valor Total</div>
                                    <div class="text-xs mt-1">{{ formatCurrency(remainingValue) }}</div>
                                </button>
                                <button
                                    type="button"
                                    @click="selectPartialPayment"
                                    :class="[
                                        'flex-1 py-3 px-4 rounded-lg border-2 text-sm font-medium transition-colors text-center',
                                        !isFullPayment 
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' 
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                    ]"
                                >
                                    <div class="font-bold">Outro Valor</div>
                                    <div class="text-xs mt-1">Escolher valor</div>
                                </button>
                            </div>
                        </div>

                        <!-- Custom amount input -->
                        <div v-if="!isFullPayment">
                            <label class="label">Valor a pagar</label>
                            <MoneyInput v-model="paymentAmount" />
                        </div>

                        <div>
                            <label class="label">Pagar com</label>
                            <select v-model="paymentAccountId" class="input">
                                <option value="">Selecione uma conta...</option>
                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.name }} - {{ formatCurrency(account.current_balance) }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button 
                            @click="handlePayInvoice" 
                            class="btn-primary flex-1"
                            :disabled="!canConfirmPayment"
                        >
                            Confirmar Pagamento
                        </button>
                        <button @click="showPayModal = false" class="btn-secondary">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { useCardsStore } from '@/stores/cards';
import { useAccountsStore } from '@/stores/accounts';
import MoneyInput from '@/components/Common/MoneyInput.vue';

const route = useRoute();
const cardsStore = useCardsStore();
const accountsStore = useAccountsStore();

const showPayModal = ref(false);
const paymentAmount = ref(0);
const paymentAccountId = ref('');
const isFullPayment = ref(true);
const selectedInvoice = ref(null);

const accounts = computed(() => accountsStore.accounts);

const actionableInvoices = computed(() => {
    return cardsStore.invoices.filter(i => ['vencida', 'fechada', 'parcialmente_paga'].includes(i.status));
});

const openInvoices = computed(() => {
    return cardsStore.invoices.filter(i => i.status === 'aberta');
});

const paidInvoices = computed(() => {
    return cardsStore.invoices.filter(i => i.status === 'paga');
});

const remainingValue = computed(() => {
    if (!selectedInvoice.value) return 0;
    return selectedInvoice.value.total_value - selectedInvoice.value.paid_value;
});

const canPay = computed(() => {
    return selectedInvoice.value?.status !== 'paga' && remainingValue.value > 0;
});

const canConfirmPayment = computed(() => {
    const amount = isFullPayment.value ? remainingValue.value : paymentAmount.value;
    return paymentAccountId.value && amount > 0;
});

function getStatusLabel(status) {
    const labels = {
        aberta: 'Fatura Aberta',
        fechada: 'Aguardando Pagamento',
        parcialmente_paga: 'Parcialmente Paga',
        paga: 'Paga',
        vencida: 'Vencida',
    };
    return labels[status] || status;
}

function getStatusBadgeClass(status) {
    const classes = {
        aberta: 'badge-info',
        fechada: 'badge-warning',
        parcialmente_paga: 'badge-warning',
        paga: 'badge-success',
        vencida: 'badge-danger',
    };
    return classes[status] || '';
}

function getInstallmentStatusClass(status) {
    const classes = {
        pendente: 'text-gray-500',
        em_fatura: 'text-blue-600',
        paga: 'text-green-600',
        estornada: 'text-red-600',
        antecipada: 'text-purple-600',
    };
    return classes[status] || 'text-gray-500';
}

function getInstallmentStatusLabel(status) {
    const labels = {
        pendente: 'Pendente',
        em_fatura: 'Na Fatura',
        paga: 'Pago',
        estornada: 'Estornado',
        antecipada: 'Antecipado',
    };
    return labels[status] || status;
}

function selectFullPayment() {
    isFullPayment.value = true;
    paymentAmount.value = remainingValue.value;
}

function selectPartialPayment() {
    isFullPayment.value = false;
    paymentAmount.value = remainingValue.value;
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    if (!date) return '';
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

function formatMonth(dateStr) {
    if (!dateStr) return '';
    // Aceita YYYY-MM ou YYYY-MM-DD
    const date = new Date(dateStr);
    // Ajuste de timezone simples para exibição correta do mês
    const month = date.getUTCMonth(); 
    const year = date.getUTCFullYear();
    
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return `${months[month]} ${year}`;
}

function selectInvoice(invoice) {
    selectedInvoice.value = invoice;
    paymentAmount.value = invoice.total_value - invoice.paid_value;
    isFullPayment.value = true;
}

async function handlePayInvoice() {
    const amount = isFullPayment.value ? remainingValue.value : paymentAmount.value;
    
    if (!paymentAccountId.value || amount <= 0) return;
    
    await cardsStore.payInvoice(route.params.id, {
        amount: amount,
        account_id: paymentAccountId.value,
    });
    
    showPayModal.value = false;
    await cardsStore.fetchCurrentInvoice(route.params.id);
    await cardsStore.fetchInvoices(route.params.id);
    await accountsStore.fetchAccounts();
    
    // Update selected invoice
    if (selectedInvoice.value) {
        const updated = cardsStore.invoices.find(i => i.id === selectedInvoice.value.id);
        if (updated) {
            selectedInvoice.value = updated;
        } else {
            selectedInvoice.value = cardsStore.currentInvoice;
        }
    }
}




function getDaysUntilDue(dateStr) {
    if (!dateStr) return null;
    const now = new Date();
    
    // Extrair componentes locais
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();
    
    // UTC Today
    const utcToday = Date.UTC(currentYear, currentMonth, currentDay);
    
    // Parse Input (YYYY-MM-DD)
    const cleanDate = dateStr.toString().split('T')[0];
    const [dueYear, dueMonth, dueDay] = cleanDate.split('-').map(Number);
    
    // UTC Due
    const utcDue = Date.UTC(dueYear, dueMonth - 1, dueDay);
    
    const diffMs = utcDue - utcToday;
    return Math.round(diffMs / (1000 * 60 * 60 * 24));
}

function getDueWarningClass(days) {
    if (days === null) return '';
    if (days < 0) return 'text-red-600 font-bold'; // Vencida
    if (days <= 3) return 'text-red-600 font-bold animate-pulse'; // Crítico
    if (days <= 7) return 'text-yellow-600 font-bold'; // Alerta
    return 'text-primary-600 font-medium'; // Normal
}

function getDueWarningText(days) {
    if (days === null) return '';
    if (days < 0) return `Venceu há ${Math.abs(days)} dias`;
    if (days === 0) return 'Vence hoje!';
    if (days === 1) return 'Vence amanhã';
    return `Vence em ${days} dias`;
}

onMounted(async () => {
    await cardsStore.fetchCurrentInvoice(route.params.id);
    await cardsStore.fetchInvoices(route.params.id);
    await accountsStore.fetchAccounts();
    
    // Set current invoice as selected
    if (cardsStore.currentInvoice) {
        selectedInvoice.value = cardsStore.currentInvoice;
        paymentAmount.value = remainingValue.value;
    }
});
</script>
