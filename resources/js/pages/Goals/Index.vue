<template>
    <div>
        <!-- Page header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Objetivos</h1>
                    <p class="text-gray-500 dark:text-gray-400">Acompanhe suas metas financeiras</p>
                </div>
                <button @click="openCreateModal" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Novo Objetivo
                </button>
            </div>
        </div>

        <!-- Dismissable info banner -->
        <DismissableBanner storage-key="goals-info" color="emerald">
            <strong>Objetivos são controle interno.</strong>
            Depositar ou sacar aqui <strong>não afeta</strong> o saldo real das suas contas.
            Use para acompanhar metas pessoais como reserva de emergência, viagens, etc.
        </DismissableBanner>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
        </div>

        <!-- Empty state -->
        <div v-else-if="goals.length === 0" class="card text-center py-12">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum objetivo definido</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Crie seu primeiro objetivo financeiro!</p>
            <button @click="openCreateModal" class="btn btn-primary">
                Criar Primeiro Objetivo
            </button>
        </div>

        <!-- Goals grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
                v-for="goal in goals" 
                :key="goal.id"
                class="card hover:shadow-lg transition-shadow relative overflow-hidden"
            >
                <!-- Status ribbon (only for completed or cancelled goals) -->
                <div 
                    v-if="goal.status === 'concluido' || goal.status === 'cancelado'"
                    :class="[
                        'absolute top-0 right-0 px-2 py-1 text-xs font-bold text-white',
                        goal.status === 'concluido' ? 'bg-green-500' : 'bg-gray-500'
                    ]"
                    style="transform: translateX(30%) rotate(45deg); transform-origin: left bottom; width: 100px; text-align: center;"
                >
                    {{ goal.status === 'concluido' ? '✓ Concluído' : 'Cancelado' }}
                </div>

                <!-- Header -->
                <div class="flex items-center gap-3 mb-4">
                    <div 
                        class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                        :style="{ backgroundColor: goal.color + '20' }"
                    >
                        {{ goal.icon }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ goal.name }}</h3>
                        <p v-if="goal.target_date" class="text-xs text-gray-500">
                            Meta: {{ formatDate(goal.target_date) }}
                        </p>
                    </div>
                </div>

                <!-- Progress -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500">{{ formatCurrency(goal.current_value) }}</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ formatCurrency(goal.target_value) }}</span>
                    </div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div 
                            class="h-full transition-all duration-500 ease-out rounded-full"
                            :style="{ 
                                width: goal.progress_percentage + '%',
                                backgroundColor: goal.color
                            }"
                        ></div>
                    </div>
                    <div class="flex justify-between text-xs mt-1">
                        <span class="text-gray-500">{{ goal.progress_percentage }}%</span>
                        <span class="text-gray-500">Falta: {{ formatCurrency(goal.remaining_value) }}</span>
                    </div>
                </div>

                <!-- Description -->
                <p v-if="goal.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">
                    {{ goal.description }}
                </p>

                <!-- Actions -->
                <div class="flex flex-wrap gap-2">
                    <template v-if="goal.status === 'em_andamento'">
                        <button 
                            @click="openDepositModal(goal)"
                            class="btn btn-sm bg-green-500 text-white hover:bg-green-600"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Depositar
                        </button>
                        <button 
                            v-if="goal.current_value > 0"
                            @click="openWithdrawModal(goal)"
                            class="btn btn-sm bg-orange-500 text-white hover:bg-orange-600"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                            Sacar
                        </button>
                    </template>
                    <button 
                        @click="openEditModal(goal)"
                        class="btn btn-sm btn-secondary"
                    >
                        Editar
                    </button>
                    <button 
                        v-if="goal.status === 'em_andamento'"
                        @click="confirmCancel(goal)"
                        class="btn btn-sm text-gray-500 hover:text-red-600"
                    >
                        Cancelar
                    </button>
                    <button 
                        v-if="goal.status === 'cancelado'"
                        @click="handleReactivate(goal)"
                        class="btn btn-sm text-green-600 hover:text-green-700"
                    >
                        Reativar
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showFormModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ isEditing ? 'Editar Objetivo' : 'Novo Objetivo' }}
                    </h2>
                    <button @click="closeFormModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveGoal" class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                        <input v-model="form.name" type="text" class="input w-full" placeholder="Ex: Reserva de emergência" required />
                    </div>

                    <!-- Icon & Color -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ícone</label>
                            <select v-model="form.icon" class="input w-full text-2xl">
                                <option value="🎯">🎯 Alvo</option>
                                <option value="💰">💰 Dinheiro</option>
                                <option value="🏠">🏠 Casa</option>
                                <option value="🚗">🚗 Carro</option>
                                <option value="✈️">✈️ Viagem</option>
                                <option value="💻">💻 Tecnologia</option>
                                <option value="📚">📚 Educação</option>
                                <option value="🏋️">🏋️ Saúde</option>
                                <option value="🎁">🎁 Presente</option>
                                <option value="⭐">⭐ Estrela</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cor</label>
                            <input v-model="form.color" type="color" class="w-full h-10 rounded-lg cursor-pointer" />
                        </div>
                    </div>

                    <!-- Target value -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor da meta *</label>
                        <MoneyInput v-model="form.target_value" />
                    </div>

                    <!-- Initial value (only for new) -->
                    <div v-if="!isEditing">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor inicial</label>
                        <MoneyInput v-model="form.current_value" />
                    </div>

                    <!-- Target date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data limite (opcional)</label>
                        <input v-model="form.target_date" type="date" class="input w-full" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                        <textarea v-model="form.description" class="input w-full" rows="2" placeholder="Opcional"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="closeFormModal" class="btn btn-secondary flex-1">Cancelar</button>
                        <button type="submit" class="btn btn-primary flex-1" :disabled="saving">
                            {{ saving ? 'Salvando...' : (isEditing ? 'Salvar' : 'Criar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Deposit/Withdraw Modal -->
        <div v-if="showActionModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="text-center mb-6">
                    <div 
                        class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl"
                        :style="{ backgroundColor: selectedGoal?.color + '20' }"
                    >
                        {{ selectedGoal?.icon }}
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ actionType === 'deposit' ? 'Depositar em' : 'Sacar de' }}
                    </h3>
                    <p class="text-gray-500">{{ selectedGoal?.name }}</p>
                </div>

                <form @submit.prevent="executeAction">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor</label>
                        <MoneyInput v-model="actionAmount" autofocus />
                        <p v-if="actionType === 'withdraw'" class="text-xs text-gray-500 mt-1">
                            Disponível: {{ formatCurrency(selectedGoal?.current_value || 0) }}
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="closeActionModal" class="btn btn-secondary flex-1">Cancelar</button>
                        <button 
                            type="submit" 
                            :class="[
                                'flex-1',
                                actionType === 'deposit' ? 'btn bg-green-500 text-white hover:bg-green-600' : 'btn bg-orange-500 text-white hover:bg-orange-600'
                            ]"
                            :disabled="actionLoading || !actionAmount"
                        >
                            {{ actionLoading ? 'Processando...' : (actionType === 'deposit' ? 'Depositar' : 'Sacar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Cancelar Objetivo</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Deseja cancelar "<strong>{{ selectedGoal?.name }}</strong>"?
                        Você pode reativar depois.
                    </p>
                    <div class="flex gap-3">
                        <button @click="showCancelModal = false" class="btn btn-secondary flex-1">Voltar</button>
                        <button @click="handleCancel" class="btn bg-red-600 text-white hover:bg-red-700 flex-1">
                            Cancelar Objetivo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGoalsStore } from '@/stores/goals';
import MoneyInput from '@/components/Common/MoneyInput.vue';
import DismissableBanner from '@/components/Common/DismissableBanner.vue';

const goalsStore = useGoalsStore();

const showFormModal = ref(false);
const showActionModal = ref(false);
const showCancelModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const actionLoading = ref(false);
const selectedGoal = ref(null);
const actionType = ref('deposit');
const actionAmount = ref(0);

const form = ref({
    name: '',
    description: '',
    icon: '🎯',
    color: '#6366F1',
    target_value: 0,
    current_value: 0,
    target_date: '',
});

const loading = computed(() => goalsStore.loading);
const goals = computed(() => goalsStore.goals);

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value || 0);
}

function formatDate(date) {
    if (!date) return '';
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
            return '';
        }
        const localDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        if (isNaN(localDate.getTime())) {
            return '';
        }
        return new Intl.DateTimeFormat('pt-BR').format(localDate);
    } catch (e) {
        return '';
    }
}

function openCreateModal() {
    form.value = {
        name: '',
        description: '',
        icon: '🎯',
        color: '#6366F1',
        target_value: 0,
        current_value: 0,
        target_date: '',
    };
    isEditing.value = false;
    showFormModal.value = true;
}

function openEditModal(goal) {
    form.value = {
        name: goal.name,
        description: goal.description || '',
        icon: goal.icon,
        color: goal.color,
        target_value: goal.target_value,
        target_date: goal.target_date || '',
    };
    selectedGoal.value = goal;
    isEditing.value = true;
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
}

function openDepositModal(goal) {
    selectedGoal.value = goal;
    actionType.value = 'deposit';
    actionAmount.value = 0;
    showActionModal.value = true;
}

function openWithdrawModal(goal) {
    selectedGoal.value = goal;
    actionType.value = 'withdraw';
    actionAmount.value = 0;
    showActionModal.value = true;
}

function closeActionModal() {
    showActionModal.value = false;
    actionAmount.value = 0;
}

function confirmCancel(goal) {
    selectedGoal.value = goal;
    showCancelModal.value = true;
}

async function saveGoal() {
    saving.value = true;
    try {
        if (isEditing.value) {
            await goalsStore.updateGoal(selectedGoal.value.id, form.value);
        } else {
            await goalsStore.createGoal(form.value);
        }
        closeFormModal();
    } catch (error) {
        // Error handled by store
    } finally {
        saving.value = false;
    }
}

async function executeAction() {
    if (!actionAmount.value || actionAmount.value <= 0) return;
    
    actionLoading.value = true;
    try {
        if (actionType.value === 'deposit') {
            await goalsStore.deposit(selectedGoal.value.id, actionAmount.value);
        } else {
            await goalsStore.withdraw(selectedGoal.value.id, actionAmount.value);
        }
        closeActionModal();
    } catch (error) {
        // Error handled by store
    } finally {
        actionLoading.value = false;
    }
}

async function handleCancel() {
    await goalsStore.cancel(selectedGoal.value.id);
    showCancelModal.value = false;
}

async function handleReactivate(goal) {
    await goalsStore.reactivate(goal.id);
}

onMounted(() => {
    goalsStore.fetchGoals();
});
</script>
