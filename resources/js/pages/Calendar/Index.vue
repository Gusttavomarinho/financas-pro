<template>
    <div class="min-h-screen">
        <!-- Page header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Calendário Financeiro</h1>
                <p class="text-gray-500 dark:text-gray-400">Visualize seu fluxo de caixa ao longo do tempo</p>
            </div>
        </div>

        <!-- Month Navigation -->
        <div class="card mb-6">
            <div class="flex items-center justify-between">
                <button @click="previousMonth" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                
                <div class="text-center">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white capitalize">
                        {{ currentMonthLabel }}
                    </h2>
                    <button @click="goToToday" class="text-sm text-primary-600 hover:underline">
                        Ir para hoje
                    </button>
                </div>
                
                <button @click="nextMonth" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
        </div>

        <!-- Calendar Grid -->
        <div v-else class="card p-0 overflow-hidden">
            <!-- Week days header -->
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                <div v-for="day in weekDays" :key="day" class="py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ day }}
                </div>
            </div>

            <!-- Days grid -->
            <div class="grid grid-cols-7">
                <div 
                    v-for="(day, index) in calendarDays" 
                    :key="index"
                    :class="[
                        'min-h-[120px] border-b border-r border-gray-200 dark:border-gray-700 p-2',
                        'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer',
                        !day.isCurrentMonth ? 'bg-gray-50/50 dark:bg-gray-800/30' : '',
                        day.isToday ? 'ring-2 ring-inset ring-primary-500' : ''
                    ]"
                    @click="selectDay(day)"
                >
                    <!-- Day number -->
                    <div class="flex items-center justify-between mb-1">
                        <span :class="[
                            'text-sm font-medium',
                            day.isToday ? 'bg-primary-500 text-white rounded-full w-7 h-7 flex items-center justify-center' : '',
                            !day.isCurrentMonth ? 'text-gray-400' : 'text-gray-700 dark:text-gray-300'
                        ]">
                            {{ day.dayNumber }}
                        </span>
                        <span v-if="getDayBalance(day.date) !== 0" :class="[
                            'text-xs font-medium',
                            getDayBalance(day.date) > 0 ? 'text-green-600' : 'text-red-600'
                        ]">
                            {{ formatCurrency(getDayBalance(day.date), true) }}
                        </span>
                    </div>

                    <!-- Events -->
                    <div class="space-y-1 overflow-hidden max-h-[80px]">
                        <div 
                            v-for="event in getDayEvents(day.date).slice(0, 3)" 
                            :key="event.id"
                            :class="[
                                'text-xs px-1.5 py-0.5 rounded truncate cursor-pointer',
                                getEventClass(event)
                            ]"
                            @click.stop="openEventDetail(event)"
                            :title="event.description"
                        >
                            <span v-if="event.status === 'previsto'" class="opacity-70">⏳</span>
                            {{ event.description }}
                        </div>
                        <div 
                            v-if="getDayEvents(day.date).length > 3" 
                            class="text-xs text-gray-500 dark:text-gray-400 pl-1"
                        >
                            +{{ getDayEvents(day.date).length - 3 }} mais
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="card mt-6">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Legenda</h3>
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-green-100 dark:bg-green-900/30 border border-green-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Receita</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-red-100 dark:bg-red-900/30 border border-red-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Despesa</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-purple-100 dark:bg-purple-900/30 border border-purple-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Fatura</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-blue-100 dark:bg-blue-900/30 border border-blue-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Recorrência</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="opacity-70">⏳</span>
                    <span class="text-gray-600 dark:text-gray-400">Previsto</span>
                </div>
            </div>
        </div>

        <!-- Day Detail Modal -->
        <Teleport to="body">
            <div v-if="showDayModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeDayModal">
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg max-h-[80vh] overflow-hidden shadow-2xl animate-slide-up">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ selectedDayLabel }}
                        </h2>
                        <button @click="closeDayModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-6 overflow-y-auto max-h-[60vh]">
                        <!-- Day Summary -->
                        <div v-if="selectedDayEvents.length > 0" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Resumo do dia</p>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">💰 Entradas</p>
                                    <p class="text-sm font-bold text-green-600">{{ formatCurrency(selectedDayIncome) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">💸 Saídas</p>
                                    <p class="text-sm font-bold text-red-600">{{ formatCurrency(selectedDayExpenses) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">🔁 Saldo</p>
                                    <p :class="['text-sm font-bold', selectedDayBalance >= 0 ? 'text-green-600' : 'text-red-600']">
                                        {{ formatCurrency(selectedDayBalance, true) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedDayEvents.length === 0" class="text-center py-8 text-gray-500">
                            Nenhum evento neste dia
                        </div>
                        <div v-else class="space-y-3">
                            <div 
                                v-for="event in selectedDayEvents" 
                                :key="event.id"
                                :class="[
                                    'p-4 rounded-xl border',
                                    getEventCardClass(event)
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', getEventIconBg(event)]">
                                            <svg v-if="event.operation === 'receita'" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                            </svg>
                                            <svg v-else class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ event.description }}</p>
                                            <p class="text-xs text-gray-500">
                                                <span class="capitalize">{{ getEventTypeLabel(event.type) }}</span>
                                                <span v-if="event.category"> · {{ event.category }}</span>
                                                <span v-if="event.status === 'previsto'" class="text-amber-600"> · ⏳ Previsto</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div :class="[
                                        'text-right font-bold',
                                        event.operation === 'receita' ? 'text-green-600' : 'text-red-600'
                                    ]">
                                        {{ event.operation === 'receita' ? '+' : '-' }}{{ formatCurrency(event.value) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const loading = ref(true);
const events = ref([]);
const currentDate = ref(new Date());
const showDayModal = ref(false);
const selectedDay = ref(null);

const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

const currentMonthLabel = computed(() => {
    return currentDate.value.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    const days = [];
    
    // Days from previous month
    const startDayOfWeek = firstDay.getDay();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({
            date: formatDateKey(date),
            dayNumber: date.getDate(),
            isCurrentMonth: false,
            isToday: isToday(date)
        });
    }
    
    // Days of current month
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = new Date(year, month, d);
        days.push({
            date: formatDateKey(date),
            dayNumber: d,
            isCurrentMonth: true,
            isToday: isToday(date)
        });
    }
    
    // Days from next month to fill grid
    const remaining = 42 - days.length; // 6 rows * 7 days
    for (let i = 1; i <= remaining; i++) {
        const date = new Date(year, month + 1, i);
        days.push({
            date: formatDateKey(date),
            dayNumber: i,
            isCurrentMonth: false,
            isToday: isToday(date)
        });
    }
    
    return days;
});

const selectedDayEvents = computed(() => {
    if (!selectedDay.value) return [];
    return getDayEvents(selectedDay.value.date);
});

const selectedDayLabel = computed(() => {
    if (!selectedDay.value) return '';
    const [year, month, day] = selectedDay.value.date.split('-');
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
});

const selectedDayIncome = computed(() => {
    return selectedDayEvents.value
        .filter(e => e.operation === 'receita')
        .reduce((sum, e) => sum + parseFloat(e.value), 0);
});

const selectedDayExpenses = computed(() => {
    return selectedDayEvents.value
        .filter(e => e.operation === 'despesa')
        .reduce((sum, e) => sum + parseFloat(e.value), 0);
});

const selectedDayBalance = computed(() => {
    return selectedDayIncome.value - selectedDayExpenses.value;
});

function formatDateKey(date) {
    return date.toISOString().split('T')[0];
}

function isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() && 
           date.getMonth() === today.getMonth() && 
           date.getFullYear() === today.getFullYear();
}

function getDayEvents(dateKey) {
    return events.value.filter(e => e.date === dateKey);
}

function getDayBalance(dateKey) {
    const dayEvents = getDayEvents(dateKey);
    let balance = 0;
    dayEvents.forEach(e => {
        if (e.operation === 'receita') {
            balance += e.value;
        } else {
            balance -= e.value;
        }
    });
    return balance;
}

function getEventClass(event) {
    if (event.type === 'invoice') {
        return 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200';
    }
    if (event.type === 'recurring') {
        return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200';
    }
    if (event.operation === 'receita') {
        return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200';
    }
    return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200';
}

function getEventCardClass(event) {
    if (event.type === 'invoice') {
        return 'border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20';
    }
    if (event.type === 'recurring') {
        return 'border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20';
    }
    if (event.operation === 'receita') {
        return 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20';
    }
    return 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20';
}

function getEventIconBg(event) {
    if (event.operation === 'receita') {
        return 'bg-green-100 dark:bg-green-900/30';
    }
    return 'bg-red-100 dark:bg-red-900/30';
}

function getEventTypeLabel(type) {
    const labels = {
        transaction: 'Transação',
        recurring: 'Recorrência',
        installment: 'Parcela',
        invoice: 'Fatura'
    };
    return labels[type] || type;
}

function formatCurrency(value, signed = false) {
    const formatted = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(Math.abs(value));
    
    if (signed && value !== 0) {
        return value > 0 ? `+${formatted}` : `-${formatted}`;
    }
    return formatted;
}

function previousMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
}

function nextMonth() {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
}

function goToToday() {
    currentDate.value = new Date();
}

function selectDay(day) {
    selectedDay.value = day;
    showDayModal.value = true;
}

function closeDayModal() {
    showDayModal.value = false;
    selectedDay.value = null;
}

function openEventDetail(event) {
    // TODO: Navigate to transaction or recurring detail
    console.log('Open event detail:', event);
}

async function fetchEvents() {
    loading.value = true;
    try {
        const year = currentDate.value.getFullYear();
        const month = currentDate.value.getMonth();
        
        // Fetch 6 weeks to cover calendar grid
        const start = new Date(year, month, 1);
        start.setDate(start.getDate() - 7);
        
        const end = new Date(year, month + 1, 0);
        end.setDate(end.getDate() + 14);
        
        const response = await axios.get('/api/calendar', {
            params: {
                start: formatDateKey(start),
                end: formatDateKey(end)
            }
        });
        
        events.value = response.data.events;
    } catch (error) {
        console.error('Erro ao carregar calendário:', error);
    } finally {
        loading.value = false;
    }
}

watch(currentDate, () => {
    fetchEvents();
});

onMounted(() => {
    fetchEvents();
});
</script>
