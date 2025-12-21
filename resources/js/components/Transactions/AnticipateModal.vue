<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg animate-slide-up">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Antecipar Parcelas</h3>
            
            <div v-if="loading" class="text-center py-8">
                <p class="text-gray-500">Carregando parcelas...</p>
            </div>

            <div v-else>
                <div class="mb-4">
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">
                        Selecione as parcelas que deseja antecipar para a fatura atual.
                    </p>
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-blue-800 dark:text-blue-300">
                            As parcelas selecionadas serão movidas para a <strong>fatura atual em aberto</strong>. Certifique-se de que a fatura atual ainda não foi fechada.
                        </p>
                    </div>
                </div>

                <!-- Installments List -->
                <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg mb-4">
                    <div v-if="availableInstallments.length === 0" class="p-4 text-center text-gray-500 text-sm">
                        Nenhuma parcela disponível para antecipação.
                    </div>
                    <div v-else>
                        <label 
                            v-for="inst in availableInstallments" 
                            :key="inst.id"
                            class="flex items-center justify-between p-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                        >
                            <div class="flex items-center gap-3">
                                <input 
                                    type="checkbox" 
                                    :value="inst.id" 
                                    v-model="selectedInstallments"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        Parcela {{ inst.installment_number }}/{{ inst.total_installments }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Vencimento original: {{ formatDate(inst.due_date) }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ formatCurrency(inst.value) }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Discount Input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Desconto (R$)
                    </label>
                    <input 
                        type="number" 
                        v-model="discount" 
                        step="0.01" 
                        min="0" 
                        class="input"
                        placeholder="0,00"
                    />
                </div>

                <!-- Summary -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Total Selecionado</span>
                        <span>{{ formatCurrency(selectedTotal) }}</span>
                    </div>
                     <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span>Desconto</span>
                        <span>- {{ formatCurrency(discount) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white text-base pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span>Total a Pagar</span>
                        <span>{{ formatCurrency(finalTotal) }}</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="$emit('close')" class="btn-secondary flex-1">
                        Cancelar
                    </button>
                    <button 
                        @click="handleConfirm" 
                        :disabled="selectedInstallments.length === 0 || submitting"
                        class="btn-primary flex-1 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ submitting ? 'Confirmando...' : 'Confirmar Antecipação' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTransactionsStore } from '@/stores/transactions';
import axios from 'axios';

const props = defineProps({
    transactionId: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['close', 'success']);

const transactionsStore = useTransactionsStore();
const loading = ref(true);
const submitting = ref(false);
const transactionData = ref(null);
const selectedInstallments = ref([]);
const discount = ref(0);

// Fetch transaction fresh data to ensure we have installments
onMounted(async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/transactions/${props.transactionId}`);
        transactionData.value = response.data.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const availableInstallments = computed(() => {
    if (!transactionData.value || !transactionData.value.card_installments) return [];
    
    // Filter: Only future installments (not the first one which is in current invoice)
    // Exclude: paid, estornada, antecipada, and installment_number === 1 (current invoice)
    return transactionData.value.card_installments.filter(i => 
        ['pendente', 'em_fatura'].includes(i.status) &&
        i.installment_number > 1 // Exclude first installment (current invoice)
    ).sort((a, b) => a.installment_number - b.installment_number);
});

const selectedTotal = computed(() => {
    if (!transactionData.value) return 0;
    return transactionData.value.card_installments
        .filter(i => selectedInstallments.value.includes(i.id))
        .reduce((sum, i) => sum + Number(i.value), 0);
});

const finalTotal = computed(() => {
    return Math.max(0, selectedTotal.value - discount.value);
});

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    if (!date) return '';
    const [year, month, day] = date.split('-');
    return `${day}/${month}/${year}`;
}

async function handleConfirm() {
    if (selectedInstallments.value.length === 0) return;
    
    submitting.value = true;
    const result = await transactionsStore.anticipateTransaction(
        props.transactionId,
        selectedInstallments.value,
        Number(discount.value)
    );
    
    if (result.success) {
        emit('success');
        emit('close');
    }
    submitting.value = false;
}
</script>
