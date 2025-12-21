<template>
    <div class="timeline">
        <div v-if="loading" class="text-center py-4">
            <svg class="animate-spin h-5 w-5 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        
        <div v-else-if="logs.length === 0" class="text-center py-4 text-gray-500">
            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">Nenhum histórico encontrado</p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="log in logs"
                :key="log.id"
                class="timeline-item animate-fade-in"
            >
                <div class="flex items-start gap-3">
                    <div :class="getActionIconClass(log.action)" class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg v-if="log.action === 'create'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <svg v-else-if="log.action === 'update'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <svg v-else-if="log.action === 'delete' || log.action === 'archive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <svg v-else-if="log.action === 'refund'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        <svg v-else-if="log.action === 'pay_invoice'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ getActionLabel(log.action) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ formatDateTime(log.created_at) }}
                        </p>
                        <div v-if="log.data && Object.keys(log.data).length" class="mt-2 p-2 rounded bg-gray-50 dark:bg-gray-800/50 text-xs">
                            <div v-for="(value, key) in getDisplayData(log.data)" :key="key" class="flex gap-2">
                                <span class="text-gray-500">{{ formatKey(key) }}:</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ formatValue(value) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    entityType: {
        type: String,
        required: true, // Account, Card, Transaction, etc.
    },
    entityId: {
        type: [Number, String],
        required: true,
    },
});

const logs = ref([]);
const loading = ref(false);

async function fetchLogs() {
    if (!props.entityId) return;
    
    loading.value = true;
    try {
        const response = await axios.get('/api/audit-logs', {
            params: {
                entity_type: props.entityType,
                entity_id: props.entityId,
            },
        });
        logs.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching audit logs:', error);
        logs.value = [];
    } finally {
        loading.value = false;
    }
}

function getActionIconClass(action) {
    const classes = {
        create: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        update: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        delete: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        archive: 'bg-gray-100 text-gray-600 dark:bg-gray-900/30 dark:text-gray-400',
        refund: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
        pay_invoice: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
        duplicate: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400',
    };
    return classes[action] || 'bg-gray-100 text-gray-600';
}

function getActionLabel(action) {
    const labels = {
        create: 'Criado',
        update: 'Atualizado',
        delete: 'Excluído',
        archive: 'Arquivado',
        refund: 'Estornado',
        partial_refund: 'Estorno Parcial',
        pay_invoice: 'Fatura Paga',
        duplicate: 'Duplicado',
        create_credit_purchase: 'Compra no Crédito',
        create_transfer: 'Transferência',
        create_installment: 'Compra Parcelada',
    };
    return labels[action] || action;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function formatKey(key) {
    const labels = {
        amount: 'Valor',
        account_id: 'Conta',
        installments: 'Parcelas',
        keep_installments: 'Parcelas mantidas',
        original_id: 'ID Original',
    };
    return labels[key] || key;
}

function formatValue(value) {
    if (typeof value === 'number') {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(value);
    }
    return String(value);
}

function getDisplayData(data) {
    if (!data) return {};
    // Filtrar apenas dados relevantes para exibição
    const { old, new: newData, ...rest } = data;
    return rest;
}

onMounted(fetchLogs);

watch(() => props.entityId, fetchLogs);
</script>
