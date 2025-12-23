<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cartões</h1>
                <p class="text-gray-500 dark:text-gray-400">Gerencie seus cartões de crédito e débito</p>
            </div>
            <RouterLink to="/cards/create" class="btn-primary">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Novo Cartão
            </RouterLink>
        </div>

        <!-- Dismissable info banner -->
        <DismissableBanner storage-key="cards-info" color="purple">
            Visualize seus cartões de crédito e débito. Acompanhe faturas, limites e datas de vencimento em um só lugar.
        </DismissableBanner>

        <!-- Cards grid -->
        <div v-if="cardsStore.loading" class="text-center py-12">
            <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div v-else-if="cardsStore.cards.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="card in cardsStore.cards"
                :key="card.id"
                class="relative rounded-2xl p-6 overflow-hidden cursor-pointer transform transition-all"
                :class="[
                    card.status === 'arquivado' 
                        ? 'opacity-50 grayscale' 
                        : 'hover:scale-[1.02] hover:shadow-xl'
                ]"
                :style="{ 
                    background: `linear-gradient(135deg, ${card.color || '#6366f1'}, ${adjustColor(card.color || '#6366f1')})`,
                    color: getTextColor(card.color || '#6366f1')
                }"
                @click="openCardDetail(card)"
            >
                <!-- Archived badge -->
                <div v-if="card.status === 'arquivado'" class="absolute top-4 left-4 flex items-center gap-2">
                    <span class="badge badge-gray">Arquivado</span>
                    <button 
                        @click.stop="handleUnarchive(card)"
                        class="badge-green cursor-pointer hover:opacity-80"
                    >
                        Reativar
                    </button>
                </div>

                <!-- Card brand -->
                <div class="absolute top-4 right-4">
                    <span class="text-sm font-medium uppercase opacity-80">{{ card.brand }}</span>
                </div>

                <!-- Card name and holder -->
                <div class="mb-4" :class="{ 'mt-6': card.status === 'arquivado' }">
                    <p class="text-sm opacity-80">{{ card.account?.bank || card.account?.name || '' }}</p>
                    <h3 class="text-xl font-bold">{{ card.name }}</h3>
                </div>

                <!-- Card number -->
                <div class="mb-3">
                    <p class="font-mono text-lg tracking-wider">•••• •••• •••• {{ card.last_4_digits }}</p>
                </div>

                <!-- Closing and Due days - NEW -->
                <div class="flex items-center gap-4 mb-3 text-sm opacity-90">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Fecha dia {{ card.closing_day }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Vence dia {{ card.due_day }}</span>
                    </div>
                </div>
                
                <!-- Due Date Warning -->
                <div class="mb-3">
                    <span :class="['text-xs uppercase tracking-wider', getDueWarningClass(getDaysUntilDue(card.due_day))]">
                        {{ getDueWarningText(getDaysUntilDue(card.due_day)) }}
                    </span>
                </div>

                <!-- Card details -->
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs opacity-60">TITULAR</p>
                        <p class="text-sm font-medium">{{ card.holder_name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs opacity-60">VALIDADE</p>
                        <p class="text-sm font-medium">{{ card.valid_thru }}</p>
                    </div>
                </div>

                <!-- Limit bar -->
                <div class="mt-4 pt-4 border-t border-white/20">
                    <div class="flex justify-between text-sm mb-2">
                        <span>Limite usado</span>
                        <span>{{ formatCurrency(card.used_limit || 0) }} / {{ formatCurrency(card.credit_limit) }}</span>
                    </div>
                    <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-white rounded-full transition-all"
                            :style="{ width: `${Math.min((card.used_limit || 0) / card.credit_limit * 100, 100)}%` }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="card text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum cartão cadastrado</h3>
            <p class="text-gray-500 mb-4">Adicione seu primeiro cartão de crédito ou débito</p>
            <RouterLink to="/cards/create" class="btn-primary">
                Adicionar Cartão
            </RouterLink>
        </div>

        <!-- Card Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedCard" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto animate-slide-up">
                    <!-- Close button -->
                    <div class="flex justify-end mb-4">
                        <button @click="showDetailModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Card preview -->
                    <div 
                        class="rounded-xl p-5 mb-6"
                        :style="{ 
                            background: `linear-gradient(135deg, ${selectedCard.color || '#6366f1'}, ${adjustColor(selectedCard.color || '#6366f1')})`,
                            color: getTextColor(selectedCard.color || '#6366f1')
                        }"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm opacity-80">{{ selectedCard.account?.bank || selectedCard.account?.name || '' }}</p>
                                <h3 class="text-lg font-bold">{{ selectedCard.name }}</h3>
                            </div>
                            <span class="text-sm font-medium uppercase opacity-80">{{ selectedCard.brand }}</span>
                        </div>
                        <p class="font-mono tracking-wider mb-4">•••• •••• •••• {{ selectedCard.last_4_digits }}</p>
                        <div class="flex justify-between text-sm">
                            <div>
                                <p class="text-xs opacity-60">TITULAR</p>
                                <p>{{ selectedCard.holder_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs opacity-60">VALIDADE</p>
                                <p>{{ selectedCard.valid_thru }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card info -->
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Limite de crédito</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedCard.credit_limit) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Limite usado</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(selectedCard.used_limit || 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Limite disponível</span>
                            <span class="font-semibold text-green-600">{{ formatCurrency(selectedCard.credit_limit - (selectedCard.used_limit || 0)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dia de fechamento</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Dia {{ selectedCard.closing_day }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dia de vencimento</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Dia {{ selectedCard.due_day }}</span>
                        </div>
                        <div v-if="selectedCard.account" class="flex justify-between">
                            <span class="text-gray-500">Conta vinculada</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ selectedCard.account.name }}</span>
                        </div>
                    </div>

                    <!-- Upcoming invoices -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Próximas Faturas</h3>
                        <div v-if="loadingInvoices" class="text-center py-4">
                            <svg class="animate-spin h-5 w-5 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div v-else-if="invoices.length" class="space-y-2">
                            <div
                                v-for="invoice in invoices.slice(0, 3)"
                                :key="invoice.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatMonth(invoice.reference_month) }}</p>
                                    <span :class="['badge', getStatusBadgeClass(invoice.status)]">{{ getStatusLabel(invoice.status) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(Math.max(0, invoice.total_value - invoice.paid_value)) }}
                                    </span>
                                    <p v-if="invoice.paid_value > 0" class="text-xs text-green-600 font-medium">
                                        Pago: {{ formatCurrency(invoice.paid_value) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500 text-sm">
                            Nenhuma fatura encontrada
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center justify-between">
                            <span>Últimos Lançamentos (30 dias)</span>
                            <RouterLink 
                                :to="`/transactions?card_id=${selectedCard.id}`"
                                class="text-xs text-primary-600 hover:text-primary-700"
                                @click="showDetailModal = false"
                            >
                                Ver todos →
                            </RouterLink>
                        </h3>
                        <div v-if="loadingTransactions" class="text-center py-4">
                            <svg class="animate-spin h-5 w-5 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div v-else-if="recentTransactions.length" class="space-y-2">
                            <div
                                v-for="tx in recentTransactions.slice(0, 5)"
                                :key="tx.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tx.description }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatDate(tx.date) }}
                                        <span v-if="tx.total_installments > 1" class="text-purple-600">
                                            • {{ tx.total_installments }}x
                                        </span>
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                    -{{ formatCurrency(tx.value) }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-gray-500 text-sm">
                            Nenhum lançamento nos últimos 30 dias
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Histórico de Ações</h3>
                        <Timeline entityType="Card" :entityId="selectedCard.id" />
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <!-- Archived card: only show reactivate -->
                        <template v-if="selectedCard.status === 'arquivado'">
                            <div class="text-center text-sm text-gray-500 mb-2 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                Este cartão está arquivado e não pode ser editado.
                            </div>
                            <button @click="handleUnarchiveFromModal" class="btn-success w-full justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Reativar Cartão
                            </button>
                        </template>
                        
                        <!-- Active card: show all actions -->
                        <template v-else>
                            <RouterLink 
                                :to="`/cards/${selectedCard.id}/invoice`" 
                                class="btn-primary w-full justify-center"
                                @click="showDetailModal = false"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Ver Fatura
                            </RouterLink>
                            <RouterLink 
                                :to="`/cards/${selectedCard.id}/edit`" 
                                class="btn-secondary w-full justify-center"
                                @click="showDetailModal = false"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar Cartão
                            </RouterLink>
                            <button @click="confirmDelete" class="btn-danger w-full justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Excluir/Arquivar Cartão
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm mx-4 w-full animate-slide-up">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Excluir Cartão</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Tem certeza que deseja excluir o cartão <strong>{{ selectedCard?.name }}</strong>?
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';
import { useCardsStore } from '@/stores/cards';
import axios from 'axios';
import Timeline from '@/components/Common/Timeline.vue';

const cardsStore = useCardsStore();
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedCard = ref(null);
const recentTransactions = ref([]);
const invoices = ref([]);
const loadingTransactions = ref(false);
const loadingInvoices = ref(false);

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

function getStatusLabel(status) {
    const labels = {
        aberta: 'Aberta',
        fechada: 'Fechada',
        parcialmente_paga: 'Parcial',
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

function adjustColor(hex) {
    const num = parseInt(hex.replace('#', ''), 16);
    const amt = -40;
    const R = Math.max(0, Math.min(255, (num >> 16) + amt));
    const G = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amt));
    const B = Math.max(0, Math.min(255, (num & 0x0000FF) + amt));
    return `#${(0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1)}`;
}

function getTextColor(hex) {
    if (!hex) return 'white';
    const num = parseInt(hex.replace('#', ''), 16);
    const R = (num >> 16) & 0xFF;
    const G = (num >> 8) & 0xFF;
    const B = num & 0xFF;
    
    // Perceived brightness (YIQ)
    const brightness = (R * 299 + G * 587 + B * 114) / 1000;
    return brightness > 140 ? '#1f2937' : 'white'; // Gray-800 or White
}

async function fetchRecentData(cardId) {
    loadingTransactions.value = true;
    loadingInvoices.value = true;
    
    try {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        const [txResponse, invoiceResponse] = await Promise.all([
            axios.get('/api/transactions', {
                params: {
                    card_id: cardId,
                    date_from: thirtyDaysAgo.toISOString().split('T')[0],
                    per_page: 10,
                },
            }),
            axios.get(`/api/cards/${cardId}/invoices`, {
                params: {
                    upcoming: true,
                    sort_dir: 'asc'
                }
            }),
        ]);
        
        recentTransactions.value = txResponse.data.data || [];
        invoices.value = invoiceResponse.data.data || [];
    } catch (error) {
        recentTransactions.value = [];
        invoices.value = [];
    } finally {
        loadingTransactions.value = false;
        loadingInvoices.value = false;
    }
}

async function openCardDetail(card) {
    selectedCard.value = card;
    showDetailModal.value = true;
    await fetchRecentData(card.id);
}

function confirmDelete() {
    showDetailModal.value = false;
    showDeleteModal.value = true;
}

async function handleDelete() {
    if (selectedCard.value) {
        await cardsStore.deleteCard(selectedCard.value.id);
        showDeleteModal.value = false;
        selectedCard.value = null;
    }
}

async function handleUnarchive(card) {
    await cardsStore.unarchiveCard(card.id);
}

async function handleUnarchiveFromModal() {
    if (selectedCard.value) {
        await cardsStore.unarchiveCard(selectedCard.value.id);
        showDetailModal.value = false;
        selectedCard.value = null;
    }
}

function getDaysUntilDue(input) {
    if (!input) return null;
    
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();
    
    const utcToday = Date.UTC(currentYear, currentMonth, currentDay);
    
    let utcDue;

    // Caso 1: String "YYYY-MM-DD" ou ISO
    if (input.toString().includes('-')) {
        const cleanDate = input.toString().split('T')[0];
        const [dueYear, dueMonth, dueDay] = cleanDate.split('-').map(Number);
        utcDue = Date.UTC(dueYear, dueMonth - 1, dueDay);
    } 
    // Caso 2: Apenas dia (int) - Calcular próximo vencimento
    else {
        const dueDay = parseInt(input);
        
        let targetYear = currentYear;
        let targetMonth = currentMonth;
        
        // Se o dia de vencimento é menor ou igual a hoje, o próximo é mês que vem
        // Ex: Hoje 21, Vencimento 9 -> Já passou -> Mês que vem
        // Ex: Hoje 21, Vencimento 21 -> É hoje! (Se considerarmos vencimento até 23:59). 
        // Mas a lógica 'Dias Até' geralmente considera 0 se é hoje.
        
        if (dueDay < currentDay) {
             targetMonth++;
        }
        
        // Ajuste de virada de ano
        if (targetMonth > 11) {
            targetMonth = 0;
            targetYear++;
        }
        
        utcDue = Date.UTC(targetYear, targetMonth, dueDay);
    }
    
    const diffMs = utcDue - utcToday;
    return Math.round(diffMs / (1000 * 60 * 60 * 24));
}

function getDueWarningClass(days) {
    if (days === null) return '';
    if (days < 0) return 'text-red-300 font-bold'; // Vencida
    if (days <= 3) return 'text-red-300 font-bold animate-pulse'; // Crítico
    if (days <= 7) return 'text-yellow-300 font-bold'; // Alerta
    return 'opacity-90'; // Normal
}

function getDueWarningText(days) {
    if (days === null) return '';
    if (days < 0) return `Venceu há ${Math.abs(days)} dias`;
    if (days === 0) return 'Vence hoje!';
    if (days === 1) return 'Vence amanhã';
    return `Vence em ${days} dias`;
}

onMounted(() => {
    cardsStore.fetchCards();
});
</script>
