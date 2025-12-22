<template>
    <div class="flex items-center justify-center gap-2">
        <!-- Previous button -->
        <button 
            @click="navigatePrevious"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            :title="mode === 'month' ? 'Mês anterior' : 'Ano anterior'"
        >
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Period display (clickable to toggle mode) -->
        <button 
            @click="toggleMode"
            class="px-4 py-2 text-lg font-semibold text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors min-w-[140px]"
        >
            {{ displayText }}
        </button>

        <!-- Next button -->
        <button 
            @click="navigateNext"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            :title="mode === 'month' ? 'Próximo mês' : 'Próximo ano'"
        >
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Calendar button (opens picker) -->
        <button 
            @click="showPicker = true"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            title="Selecionar período"
        >
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </button>

        <!-- Today button -->
        <button 
            @click="goToToday"
            class="px-3 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
            title="Ir para mês atual"
        >
            Hoje
        </button>

        <!-- Month/Year Picker Modal -->
        <Teleport to="body">
            <div v-if="showPicker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showPicker = false">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-sm animate-slide-up">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Selecionar Período</h3>
                        <button @click="showPicker = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Year selector -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ano</label>
                        <div class="flex items-center gap-2">
                            <button @click="pickerYear--" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <span class="flex-1 text-center text-lg font-semibold text-gray-900 dark:text-white">{{ pickerYear }}</span>
                            <button @click="pickerYear++" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Month grid -->
                    <div class="grid grid-cols-4 gap-2 mb-6">
                        <button
                            v-for="(name, index) in monthNames"
                            :key="index"
                            @click="selectMonth(index + 1)"
                            :class="[
                                'py-2 px-1 text-sm rounded-lg transition-colors',
                                pickerMonth === index + 1
                                    ? 'bg-primary-500 text-white'
                                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
                            ]"
                        >
                            {{ name }}
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button @click="showPicker = false" class="btn-secondary flex-1">
                            Cancelar
                        </button>
                        <button @click="applyPicker" class="btn-primary flex-1">
                            Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    month: {
        type: Number,
        required: true
    },
    year: {
        type: Number,
        required: true
    },
    mode: {
        type: String,
        default: 'month' // 'month' or 'year'
    }
});

const emit = defineEmits(['update:month', 'update:year', 'update:mode', 'change']);

const internalMode = ref(props.mode);
const showPicker = ref(false);
const pickerYear = ref(props.year);
const pickerMonth = ref(props.month);

const monthNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
const monthNamesFull = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

const displayText = computed(() => {
    if (internalMode.value === 'year') {
        return props.year.toString();
    }
    return `${monthNamesFull[props.month - 1]} ${props.year}`;
});

// Sync internal mode with prop
watch(() => props.mode, (newMode) => {
    internalMode.value = newMode;
});

watch(() => props.year, (newYear) => {
    pickerYear.value = newYear;
});

watch(() => props.month, (newMonth) => {
    pickerMonth.value = newMonth;
});

function toggleMode() {
    const newMode = internalMode.value === 'month' ? 'year' : 'month';
    internalMode.value = newMode;
    emit('update:mode', newMode);
    // Emit change with new mode to trigger reload
    emit('change', { month: props.month, year: props.year, mode: newMode });
}

function navigatePrevious() {
    if (internalMode.value === 'year') {
        emit('update:year', props.year - 1);
        emit('change', { month: props.month, year: props.year - 1, mode: 'year' });
    } else {
        let newMonth = props.month - 1;
        let newYear = props.year;
        if (newMonth < 1) {
            newMonth = 12;
            newYear--;
        }
        emit('update:month', newMonth);
        emit('update:year', newYear);
        emit('change', { month: newMonth, year: newYear, mode: 'month' });
    }
}

function navigateNext() {
    if (internalMode.value === 'year') {
        emit('update:year', props.year + 1);
        emit('change', { month: props.month, year: props.year + 1, mode: 'year' });
    } else {
        let newMonth = props.month + 1;
        let newYear = props.year;
        if (newMonth > 12) {
            newMonth = 1;
            newYear++;
        }
        emit('update:month', newMonth);
        emit('update:year', newYear);
        emit('change', { month: newMonth, year: newYear, mode: 'month' });
    }
}

function goToToday() {
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();
    internalMode.value = 'month';
    emit('update:month', currentMonth);
    emit('update:year', currentYear);
    emit('update:mode', 'month');
    emit('change', { month: currentMonth, year: currentYear, mode: 'month' });
}

function selectMonth(monthIndex) {
    pickerMonth.value = monthIndex;
}

function applyPicker() {
    internalMode.value = 'month';
    emit('update:month', pickerMonth.value);
    emit('update:year', pickerYear.value);
    emit('update:mode', 'month');
    emit('change', { month: pickerMonth.value, year: pickerYear.value, mode: 'month' });
    showPicker.value = false;
}
</script>
